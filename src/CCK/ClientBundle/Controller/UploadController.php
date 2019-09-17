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
		}elseif($status == 'default'){
			$status_message = "";
		}elseif($status == 454){
			$status_message = "テキストファイルのエンコード形式を確認してください。取込可能な形式はUTF-8です。";
		}elseif($status == 455){
			$status_message = "ファイル形式を確認してください。CSVファイルのみ取込可能です。";
		}elseif(strpos($status,'456') !== false){
			$status_message = "用語IDがマッチしていません。:" . explode(':',$status)[1];
		}elseif($status == 457){
			$status_message = "CSVファイルの項目数が異なっています。項目数：5";
		}else{
			$status_message = "csvファイルの取込みに失敗しました。ファイルを確認して下さい。";
		}

		return array(
				'currentUser' => ['user_id' => $this->getUser()->getUserId(), 'name' => $this->getUser()->getName()],
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
				'return_message' => $status_message,
		);
	}

	/**
	 * @Route("/nombre/upload", name="client.nombre.upload")
	 * @Method("POST|GET")
	 */
	public function nombreUploadAction(Request $request){
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

		try {
			// 初期化
			$this->clearDirectory($archivePath . $params['curriculum'] . '_' . $params['version']);

			foreach ($files as $el_file_list){
				$filename = $this->uploads($el_file_list['name'], $archivePath . $params['curriculum'] . '_' . $params['version'] . '/', $tempPath . '/');
			}

			if(!$this->checkFileType($archivePath . $params['curriculum'] . '_' . $params['version'] . '/' . $filename)){
				return $this->redirect($this->generateUrl('client.csv.import', array('status' => 455)));
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

					// 項目名を保存
					if($lineCnt == 1) {
						//エンコードチェック
						if (mb_detect_encoding($line, "UTF-8") === false){
							fclose($filePointer);
							return $this->redirect($this->generateUrl('client.csv.import', array('status' => 454)));
						}

						continue;
					}

					// 項目数チェック
					if(count($data) != 5){
						return $this->redirect($this->generateUrl('client.csv.import', array('status' => 457)));
					}

					// db connect and Transaction start
					$em->getConnection()->beginTransaction();

					try {

						// 用語ID存在チェック
						$termID = $data[3];
						$is_term = true;
						$term = false;
						if(substr($termID, 0, 1) == 'M'){
							$termID = ltrim(substr($termID, 1), '0');

							$term = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:MainTerm')->findOneBy(array(
									'termId' => $termID,
									'deleteFlag' => FALSE
							));
						}elseif(substr($termID, 0, 1) == 'S'){
							$termID = ltrim(substr($termID, 1), '0');

							$term = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:SubTerm')->findOneBy(array(
									'id' => $termID,
									'deleteFlag' => FALSE
							));
						}elseif(substr($termID, 0, 1) == 'D'){
							$termID = ltrim(substr($termID, 1), '0');

							$term = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Synonym')->findOneBy(array(
									'id' => $termID,
									'deleteFlag' => FALSE
							));
						}elseif(substr($termID, 0, 1) == 'K'){
							$termID = ltrim(substr($termID, 1), '0');

							$term = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:ExplainIndex')->findOneBy(array(
									'id' => $termID,
									'deleteFlag' => FALSE
							));
						}elseif(strpos($termID, '.') !== false){
							// 画像ファイル名
							$term = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:MainTerm')->findOneBy(array(
									'illustFilename' => $termID,
									'deleteFlag' => FALSE
							));

							$is_term = false;
						}

						if($term){
							if($is_term){
								$term->setNombre($data[0]);
							}else{
								$term->setIllustNombre($data[0]);
							}
						}else{
							$em->getConnection()->rollback();
							$em->close();

							return $this->redirect($this->generateUrl('client.csv.import', array('status' => '456:'.$data[3])));
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
			}
		}

		$em->close();

		return $this->redirect($this->generateUrl('client.csv.import', array('status' => 200)));

	}

	private function checkFileType($filename){
		$this->get('logger')->error("ファイル名：".$filename);
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
}