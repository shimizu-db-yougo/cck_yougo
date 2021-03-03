<?php
namespace CCK\ClientBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use ZipArchive;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use CCK\CommonBundle\Entity\Upload;

/**
 * upload controller.
 * アップロードコントローラー
 *
 */
class UploadController extends BaseController {

	/**
	 * @Route("/csv/upload/index/{status}", name="client.csv.import")
	 * @Template()
	 */
	public function indexAction(Request $request, $status) {
		// session
		$session = $request->getSession();

		// get user information
		$user = $this->getUser();

		$cur_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findBy(array(
				'deleteFlag' => FALSE
		));
		$ver_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findBy(array(
				'deleteFlag' => FALSE
		));

		if($status == 200){
			$status_message = "csvファイルの取込みが完了しました。";
		}elseif($status == 201){
			$status_message = "csvファイルの取込みが完了しましたが、エラーがありますのでログ出力ボタンからダウンロードしてください。";
		}elseif($status == 'default'){
			$status_message = "";
		}else{
			$status_message = "csvファイルの取込みに失敗しました。ファイルを確認して下さい。";
		}

		$upload_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Upload')->getUploadList();

		return array(
				'currentUser' => ['user_id' => $this->getUser()->getUserId(), 'name' => $this->getUser()->getName()],
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
				'return_message' => $status_message,
				'upload_list' => $upload_list,
				'status' => $status,
		);
	}

	/**
	 * @Route("/nombre/upload", name="client.nombre.upload")
	 * @Method("POST|GET")
	 */
	public function nombreUploadAction(Request $request){
		$user = $this->getUser();

		// postでもらうべきのリストを取得
		$targets = $this->container->getParameter('api.upload.nombre');

		// postで来たデータの中にあるべきものが全部あるか確認
		$params = $this->checkParams($targets,$request);

		// real path
		$archivePath = $this->container->getParameter('archive')['dir_path'];
		$webpath = $request->getSchemeAndHttpHost() . '/' . $this->container->getParameter('archive')['link'];
		$tempPath = $this->container->getParameter('temp');
		$images = array();
		$arr_response = array();

		foreach ($params['image'] as $key => $file){
			if($file == '') continue;
			$images[$key] = $this->tempUploads($file, $archivePath, $tempPath);
		}

		//localeの変更
		setlocale(LC_ALL, 'ja_JP.UTF-8');

		//実際のフォルダーにアップロードする。
		$files = array();
		$files = $this->openDir($tempPath, '', $files);

		// localeを戻す
		setlocale(LC_ALL, 'C');

		if(count($files) == 0){
			return $this->redirect($this->generateUrl('client.csv.import', array('status' => 451)));
		}

		$this->RemoveLog("nombre_check.log");
		$status = 200;
		try {
			// 初期化
			$this->clearDirectory($archivePath . $params['curriculum'] . '_' . $params['version']);

			foreach ($files as $el_file_list){
				$filename = $this->uploads($el_file_list['name'], $archivePath . $params['curriculum'] . '_' . $params['version'] . '/', $tempPath . '/');
			}

			if(!$this->checkFileType($archivePath . $params['curriculum'] . '_' . $params['version'] . '/' . $filename)){
				// ログ出力
				$this->OutputLog("ERROR", "nombre_check.log", "ファイル形式を確認してください。CSVファイルのみ取込可能です。");
				$status = 201;
				return $this->redirect($this->generateUrl('client.csv.import', array('status' => $status)));
			}

		} catch(\Exception $e){
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());
			return $this->redirect($this->generateUrl('client.csv.import', array('status' => 452)));
		}

		// テンポラリフォルダ内のファイルを削除
		$this->clearDirectory($tempPath);


		$em = $this->get('doctrine.orm.entity_manager');

		// ノンブル更新
		$updatefile = array();
		$updatefile = $this->openDir($archivePath . $params['curriculum'] . '_' . $params['version'], '', $updatefile);
		foreach ($updatefile as $el_file_list){
			if(strcasecmp(pathinfo($el_file_list['name'], PATHINFO_EXTENSION), 'csv') == 0){
				$filePointer = fopen($el_file_list['realpath'], 'r');

				$lineCnt = 0;

				while($line = fgets($filePointer)){
					$lineCnt++;

					// 改行コード削除
					$line = str_replace("\n","",$line);
					$line = str_replace("\r","",$line);
					$line = str_replace("\r\n","",$line);

					// 項目ごとの"xxx"からダブルクォーテーション削除
					$line = str_replace('"','',$line);

					$data = explode("	",$line);

					//ヘッダーはスキップ
					if($lineCnt == 1) {
						continue;
					}

					if($lineCnt > 1) {
						//エンコードチェック
						if (mb_detect_encoding($data[2], "UTF-8") === false){
							fclose($filePointer);
							$this->OutputLog("ERROR0", "nombre_check.log", "テキストファイルのエンコード形式を確認してください。取込可能な形式はUTF-8です。");
							$status = 201;
							return $this->redirect($this->generateUrl('client.csv.import', array('status' => $status)));
						}
					}

					// 項目数チェック
					if(count($data) != 5){
						$this->OutputLog("ERROR1", "nombre_check.log", "[".$data[2]."]の項目数が異なっています。項目数：5");
						$status = 201;
					}

					// ノンブル空欄チェック
					if(($data[0] == "")||($data[1] == "")){
						$this->OutputLog("ERROR2", "nombre_check.log", "[".$data[2]."]のノンブルが登録されていません");
						$status = 201;
					}

					// db connect and Transaction start
					$em->getConnection()->beginTransaction();

					try {

						// 用語ID存在チェック
						$termID = $data[3];
						$is_term = true;
						$term = false;
						$term_db = "";

						if(strpos($termID, '.') !== false){
							// 画像ファイル名
							$term = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:MainTerm')->findOneBy(array(
									'illustFilename' => $termID,
									'deleteFlag' => FALSE
							));

							$is_term = false;

						}elseif(substr($termID, 0, 1) == 'M'){
							$termID = ltrim(substr($termID, 1), '0');

							$term = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:MainTerm')->findOneBy(array(
									'termId' => $termID,
									'deleteFlag' => FALSE
							));

							if($term){
								$term_db = $term->getMainTerm();
							}

						}elseif(substr($termID, 0, 1) == 'S'){
							$termID = ltrim(substr($termID, 1), '0');

							$term = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:SubTerm')->findOneBy(array(
									'id' => $termID,
									'deleteFlag' => FALSE
							));

							if($term){
								$term_db = $term->getSubTerm();
							}

						}elseif(substr($termID, 0, 1) == 'D'){
							$termID = ltrim(substr($termID, 1), '0');

							$term = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Synonym')->findOneBy(array(
									'id' => $termID,
									'deleteFlag' => FALSE
							));

							if($term){
								$term_db = $term->getTerm();
							}

						}elseif(substr($termID, 0, 1) == 'K'){
							$termID = ltrim(substr($termID, 1), '0');

							$term = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:ExplainIndex')->findOneBy(array(
									'id' => $termID,
									'deleteFlag' => FALSE
							));

							if($term){
								$term_db = $term->getIndexTerm();
							}
						}

						if($term){
							if($is_term){
								$term->setNombre($data[0]);
							}else{
								$term->setIllustNombre($data[0]);
							}
						}else{
							// CSVの用語IDがDBに登録された用語IDと一致しない
							$this->OutputLog("ERROR3", "nombre_check.log", "用語IDがマッチしていません。:" . $termID);
							$status = 201;
						}
						// CSVの用語とDBに登録されたID,用語と一致しない
						if($is_term){
							if($data[2] != $term_db){
								$this->OutputLog("ERROR4", "nombre_check.log", "[".$data[2]."]が登録されているID・用語と一致しません。");
								$status = 201;
							}
						}

						$em->flush();
						$em->getConnection()->commit();

					} catch (\Exception $e){
						// もし、DBに登録失敗した場合rollbackする
						$em->getConnection()->rollback();
						$em->close();

						// log
						$this->get('logger')->error($e->getMessage());
						$this->get('logger')->error($e->getTraceAsString());

						return $this->redirect($this->generateUrl('client.csv.import', array('status' => 453)));
					}
				}
				// ファイルをクローズする
				fclose($filePointer);
			}else{
				$this->OutputLog("ERROR5", "nombre_check.log", "ファイル形式を確認してください。CSVファイルのみ取込可能です。");
				$status = 201;
				break;
			}
		}

		// インポート履歴
		$this->registerUploadHistory($em,$params['version'],$user->getUserId(),$files);

		$em->close();

		return $this->redirect($this->generateUrl('client.csv.import', array('status' => $status)));

	}

	/**
	 * @Route("/log/download", name="client.log.download")
	 * @Method("POST|GET")
	 */
	public function logDownloadAction(Request $request){
		return $this->DownloadLog("nombre_check.log");
	}

	private function checkFileType($filename){
		//ファイル形式チェック
		$file_data = file_get_contents($filename);
		//MIMEタイプの取得
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime_type = finfo_buffer($finfo, $file_data);
		finfo_close($finfo);

		if ($mime_type != 'text/plain'){
			return false;
		}
		return true;
	}

	private function registerUploadHistory($em, $versionId,$userid,$files){
		try {
			foreach($files as $ele_file){
				$em->getConnection()->beginTransaction();
				$entity = new Upload();
				$entity->setVersionId($versionId);
				$entity->setUserId($userid);
				$entity->setFileName($ele_file['name']);
				$entity->setCreateDate(new \DateTime());

				$em->persist($entity);

				$em->flush();
				$em->getConnection()->commit();
			}
		} catch (\Exception $e){
			// もし、DBに登録失敗した場合rollbackする
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return false;
		}
	}

}