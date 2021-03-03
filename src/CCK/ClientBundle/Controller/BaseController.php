<?php
namespace CCK\ClientBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use CCK\CommonBundle\Entity\MainTerm;
use CCK\CommonBundle\Entity\SubTerm;
use CCK\CommonBundle\Entity\Synonym;
use CCK\CommonBundle\Entity\Refer;
use CCK\CommonBundle\Entity\ExplainIndex;
use CCK\CommonBundle\Entity\Center;
use CCK\CommonBundle\Entity\Header;
use CCK\CommonBundle\Entity\Vacant;

class BaseController extends Controller {

	/**
	 * @var unknown
	 */
	private $filesystem;

	/**
	 * @var unknown
	 */
	private $tempPath;

	/**
	 * @var unknown
	 */
	private $logger;

	/**
	 * @var session key
	 */
	const SES_SEARCH_HAN_KEY = "ses_search_han_key";

	/**
	 * 基本的なpostデータを取得
	 *
	 * @return multitype:
	 */
	public function init(Request $request){
		// requestからすべてのpostデータ取得
		$params = $request->request->all();

		// イメージ関連するものがあればimageに全部入れる
		if(0 < count($request->files->all())){
			foreach ($request->files->all() as $key => $file){
				$params[$key] = $file;
			}
		}

		return $params;
	}

	/**
	 * postデータ確認
	 *
	 * @param array $targetKeys
	 * @throws ParameterNotFoundException
	 * @return multitype:Ambigous <>
	 */
	public function checkParams(array $targetKeys,Request $request){
		// requestのデータ全て取得
		$params = $this->init($request);
		$result = [];

		foreach ($targetKeys as $keys => $target){

			if($target == 'image'){
				$flag = false;
				foreach ($params as $k => $v){
					if(preg_match('/image/i', $k)){
						$flag = true;
						$result['image'][$k] = $v;
						unset($params[$k]);
					}
				}

				if(!$flag){
					$response = $this->responseForm(999, 'error.notexist.parameter', true, array('%parameter%' => $target));
					return $response;
				}
			} else {
				// 存在確認
				if(isset($params[$target])){
					$key = $target;
					$result[$key] = $params[$target];
					// 必要ではないパラメーターが含まれないようにparamsをunsetする。もし 必要ではないパラメーターがあれば、下のif文で処理する。
					unset($params[$target]);
				} else {
					$response = $this->responseForm(999, 'error.notexist.parameter', true, array('%parameter%' => $target));
					return $response;
				}
			}
		}

		// 必要ではないパラメーターが含まれている場合
		if(0 != count($params)){
			$response = $this->responseForm(999, 'error.remind.parameter');
			return $response;
		}

		return $result;
	}

	/**
	 * @param Request $request
	 * @return multitype:\Symfony\Component\HttpFoundation\File\File
	 */
	public function getFiles(Request $request){
		$files = $request->files->all();
		$filelists = $this->findFile($files);

		return $filelists;
	}

	/**
	 * @param unknown $files
	 * @return multitype:\Symfony\Component\HttpFoundation\File\File
	 */
	private function findFile($files){
		$filelists = array();
		foreach ($files as $key => $file){
			if(is_array($file)){
				$filelists = array_merge($filelists, $this->findFile($file));
			}else{
				if(!$file instanceof File) continue;
				$filelists[$key] = $file;
				unset($files[$key]);
			}
		}

		return $filelists;
	}

	/**
	 * tempにアップ
	 *
	 * @param File $file
	 */
	public function tempUploads(UploadedFile $file, $realPath, $tempPath, $name_chenged = ''){
		if($file->getSize() == 0) return;

		$fileName='';

		while(true){
			try {

				if($name_chenged == ''){
					$fileName = $file->getClientOriginalName();
				}else{
					$fileName = $name_chenged;
				}

				$file->move(substr($tempPath,0,strlen($tempPath)-1) , $fileName);
				break;
			} catch(\Exception $e){
			}
		}
		return $fileName;
	}

	/**
	 * アップロード
	 *
	 * @param File $file
	 */
	public function uploads($fileName, $realPath, $tempPath){

		try {
			if (!is_dir($realPath)) {
				mkdir($realPath, 0777, True);
				chmod($realPath, 0777);
			}

			rename($tempPath . $fileName , $realPath . $fileName);
		} catch(\Exception $e){

		}
		return $fileName;
	}

	/**
	 * ディレクトリコピー
	 *
	 * @param unknown $originPath
	 * @param unknown $destPath
	 * @return boolean
	 */
	public function copyDirectory($originPath, $destPath, $is_diversion = null){
		try {
			if (!is_dir($destPath)) {
				mkdir($destPath);
			}

			if (is_dir($originPath)) {
				if ($dh = opendir($originPath)) {
					while (($file = readdir($dh)) !== false) {
						if ($file == "." || $file == "..") {
							continue;
						}
						if (is_dir($originPath . "/" . $file)) {
							$this->copyDirectory($originPath . "/" . $file, $destPath . "/" . $file, $is_diversion);
						}else{
							if($is_diversion){
								$arrOriginPath = explode("/", $originPath);
								$arrDestPath = explode("/", $destPath);

								$originGenkoID = $arrOriginPath[count($arrOriginPath)-2];
								$destGenkoID = $arrDestPath[count($arrDestPath)-2];

								copy($originPath . "/" . $file, $destPath . "/" . mb_ereg_replace($originGenkoID, $destGenkoID, $file));
							}else{
								copy($originPath . "/" . $file, $destPath . "/" . $file);
							}
						}
					}
					closedir($dh);
				}
			}
		} catch(\Exception $e){
			return false;
		}

		return true;
	}

	/**
	 * ディレクトリクリア
	 *
	 * @param unknown $destPath
	 * @return boolean
	 */
	public function clearDirectory($destPath, $is_subdir = false){
		try {
			if (!is_dir($destPath)) {
				return false;
			}else{
				if ($dh = opendir($destPath)) {
					while (($file = readdir($dh)) !== false) {
						if ($file == "." || $file == "..") {
							continue;
						}
						if (is_dir($destPath . "/" . $file)) {
							$this->clearDirectory($destPath . "/" . $file, true);
						}else{
							unlink($destPath . "/" . $file);
						}
					}
					closedir($dh);
					if($is_subdir) rmdir($destPath);
				}
			}
		} catch(\Exception $e){
			return false;
		}

		return true;
	}

	public function clearDirectoryPerCount($destPath, $max_cnt, $extention){
		try {
			if (!is_dir($destPath)) {
				return false;
			}else{
				$file_list = array();
				if ($dh = opendir($destPath)) {
					while (($file = readdir($dh)) !== false) {
						if ($file == "." || $file == "..") {
							continue;
						}
						if(strcasecmp(pathinfo($file, PATHINFO_EXTENSION), $extention) == 0){
							array_push($file_list, $file);
						}
					}

					rsort($file_list);

					$file_cnt = 1;
					foreach ($file_list as $file_element){
						if($file_cnt >= $max_cnt){
							unlink($destPath . "/" . $file_element);
						}
						$file_cnt += 1;
					}

					closedir($dh);
				}
			}
		} catch(\Exception $e){
			return false;
		}

		return true;
	}


	/**
	 * ディレクトリ内のファイルリスト取得
	 *
	 * @param unknown $path
	 * @param unknown $webpath
	 * @param unknown $images
	 * @return array
	 */
	public function openDir($path, $webpath, $images){
		if (!is_dir($path)) {
			mkdir($path, 0777, True);
			chmod($path, 0777);
		}
		// open directory
		$dir_handle = opendir($path);

		// directory files
		while(($file = readdir($dir_handle)) !== false)
		{
			// . and .. pass target
			if($file == "." || $file == "..") {
				continue;
			}
			$realpath = realpath($path . '/' . $file);
			$src = $webpath . '/' . $file;
			$images[] = array(
					'realpath' => $realpath,
					'webpath' => $src,
					'name' => $file
			);
		}
		closedir($dir_handle);

		return $images;
	}

	/**
	 * 基本的なresponseの形を作成
	 *
	 * @param number $resultCode
	 * @param string $error
	 * @param string $trans
	 * @param array $transOptions
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function responseForm($resultCode = 200, $error = null, $trans = true, array $transOptions = array()){
		// エラーメッセージ設定
		$error = (is_null($error)) ? 'error.failed' : $error;
		// result codeが0の場合、問題ない前提なのでerrorは空にする
		if($resultCode == 200) $error = '';
		$content = array(
				'result' => array(
						'result_code' => $resultCode,
						// 基本的にエラーメッセージはtransを利用してCTECommonBundleのmessage.ja.ymlで管理する
						'err' => $this->get('translator')->trans($error, $transOptions)
				)
		);
		// もし、translatorを使用したくない場合の処理
		if(!$trans) $content['result']['err'] = $error;

		return $this->responseResult($content);
	}

	/**
	 * 基本response以外に追加したいデータ
	 *
	 * @param unknown $response
	 * @param array $options
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function responseOptions(&$response, array $options){
		// 既にresponseに入っているものを取得
		$content = json_decode($response->getContent(), true);
		// 追加データを入れる
		$content = array_merge($content, $options);
		$response = $this->responseResult($content);
		return $response;
	}

	/**
	 * responseをjson形にして返す
	 *
	 * @param unknown $content
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function responseResult($content){
		return new JsonResponse($content);
	}

	/**
	 * @param unknown $query
	 * @param number $num
	 * @param number $paging
	 * @return unknown
	 */
	public function createPagination($request, $query, $paging = 5, $tpage = 1){
		$paginator  = $this->get('knp_paginator');
		$pagination = $paginator->paginate(
				$query,
				$request->query->getInt('page', $tpage),
				$paging/*limit per page*/
		);

		return $pagination;
	}

	protected function encoding(array $value, Request $request, $mac_sjis = false){
		$ua = $request->server->get('HTTP_USER_AGENT');
		if($mac_sjis){
			if(preg_match("/Mac/i", $ua) || preg_match("/mac/i", $ua)){
				mb_convert_variables('sjis-win', 'UTF-8', $value);
			}
		}
		return $value;
	}

	/**
	 * MacExcelで分割された全角文字の濁点／半濁点を統合する
	 * @param string $string
	 * @return string
	 */
	static public function normalizeUtf8MacFileName($string)
	{
		$newString = '';
		$beforeChar = '';
		//基本的に一文字前の文字を一文字ずつ繋げていくので、文字数よりも一回ループが多い
		for ($i = 0; $i <= mb_strlen($string, 'UTF-8'); $i++) {
			$nowChar = mb_substr($string, $i, 1, 'UTF-8');
			if ($nowChar == hex2bin('e38299')) { //Macの濁点
				$retChar = self::macConvertKana($beforeChar, false);
				$substituteChar = 'e3829b'; //Windowsの全角濁点
				goto convPoint;
			} elseif ($nowChar == hex2bin('e3829a')) { //Macの半濁点
				$retChar = self::macConvertKana($beforeChar, true);
				$substituteChar = 'e3829c'; //Windowsの全角半濁点

				convPoint: //濁点または半濁点があった場合の処理
				if ($retChar) { //前の文字と合体可能の場合
					$newString .= $retChar;
					$beforeChar = '';
				} else { //前の文字と合体不可能の場合
					$newString .= $beforeChar;
					$beforeChar = hex2bin($substituteChar); //Windowsの全角濁点／半濁点に置換
				}
			} else { //濁点／半濁点以外はそのままスルー
				$newString .= $beforeChar;
				$beforeChar = $nowChar;
			}
		}
		return $newString;
	}

	/**
	 * 一文字渡された文字に対し、濁点付き、半濁点付きの文字を返す
	 * @param string $char
	 * @param boolean $half
	 * @return string
	 */
	static public function macConvertKana($char, $half = false)
	{
		$retChar = '';
		if ($char) {
			//濁点の対応表
			$fullTable = array(
					'か' => 'が','き' => 'ぎ','く' => 'ぐ','け' => 'げ','こ' => 'ご',
					'さ' => 'ざ','し' => 'じ','す' => 'ず','せ' => 'ぜ','そ' => 'ぞ',
					'た' => 'だ','ち' => 'ぢ','つ' => 'づ','て' => 'で','と' => 'ど',
					'は' => 'ば','ひ' => 'び','ふ' => 'ぶ','へ' => 'べ','ほ' => 'ぼ',
					'ゝ' => 'ゞ',
					'カ' => 'ガ','キ' => 'ギ','ク' => 'グ','ケ' => 'ゲ','コ' => 'ゴ',
					'サ' => 'ザ','シ' => 'ジ','ス' => 'ズ','セ' => 'ゼ','ソ' => 'ゾ',
					'タ' => 'ダ','チ' => 'ヂ','ツ' => 'ヅ','テ' => 'デ','ト' => 'ド',
					'ハ' => 'バ','ヒ' => 'ビ','フ' => 'ブ','ヘ' => 'ベ','ホ' => 'ボ',
					'ウ' => 'ヴ','ヽ' => 'ヾ',
			);
			//半濁点の対応表
			$halfTable = array(
					'は' => 'ぱ','ひ' => 'ぴ','ふ' => 'ぷ','へ' => 'ぺ','ほ' => 'ぽ',
					'ハ' => 'パ','ヒ' => 'ピ','フ' => 'プ','ヘ' => 'ペ','ホ' => 'ポ',
			);
			//どちらの対応表を使うか
			if ($half) {
				$targetArray = $halfTable;
			} else {
				$targetArray = $fullTable;
			}
			//対応表に合致するか
			if (isset($targetArray[$char])) {
				$retChar = $targetArray[$char];
			}
		}
		return $retChar;
	}

	public function copyHeader($em, $entityHeader, $newCurId){

		$entityNewHeader = new Header();

		$em->getConnection()->beginTransaction();

		try{
			$entityNewHeader->setVersionId($newCurId);

			$entityNewHeader->setHeaderId($entityHeader->getHeaderId());
			$entityNewHeader->setHen($entityHeader->getHen());
			$entityNewHeader->setSho($entityHeader->getSho());
			$entityNewHeader->setDai($entityHeader->getDai());
			$entityNewHeader->setChu($entityHeader->getChu());
			$entityNewHeader->setKo($entityHeader->getKo());
			$entityNewHeader->setName($entityHeader->getName());
			$entityNewHeader->setSort($entityHeader->getSort());
			$entityNewHeader->setDeleteFlag(false);

			$em->persist($entityNewHeader);
			$em->flush();
			$em->getConnection()->commit();
		} catch (\Exception $e){
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
	}

	public function copyMainTerm($em, $entityMain, $newCurId = null){

		$entityNewMain = new MainTerm();

		// 用語IDの発番
		$maxTermIDRec = $em->getRepository('CCKCommonBundle:MainTerm')->getNewTermID();

		$em->getConnection()->beginTransaction();

		try{
			$newTermId = (int)$maxTermIDRec[0]['term_id'] + 1;

			$entityNewMain->setTermId($newTermId);

			if($newCurId){
				$entityNewMain->setCurriculumId($newCurId);
			}else{
				$entityNewMain->setCurriculumId($entityMain->getCurriculumId());
			}

			$entityNewMain->setHeaderId($entityMain->getHeaderId());
			$entityNewMain->setPrintOrder($entityMain->getPrintOrder());
			$entityNewMain->setMainTerm($entityMain->getMainTerm());
			$entityNewMain->setRedLetter($entityMain->getRedLetter());
			$entityNewMain->setTextFrequency($entityMain->getTextFrequency());
			$entityNewMain->setCenterFrequency($entityMain->getCenterFrequency());
			$entityNewMain->setNewsExam($entityMain->getNewsExam());
			$entityNewMain->setDelimiter($entityMain->getDelimiter());
			$entityNewMain->setWesternLanguage($entityMain->getWesternLanguage());
			$entityNewMain->setBirthYear($entityMain->getBirthYear());
			$entityNewMain->setKana($entityMain->getKana());
			$entityNewMain->setIndexAddLetter($entityMain->getIndexAddLetter());
			$entityNewMain->setIndexKana($entityMain->getIndexKana());
			$entityNewMain->setIndexOriginal($entityMain->getIndexOriginal());
			$entityNewMain->setIndexOriginalKana($entityMain->getIndexOriginalKana());
			$entityNewMain->setIndexAbbreviation($entityMain->getIndexAbbreviation());
			$entityNewMain->setNombre($entityMain->getNombre());
			$entityNewMain->setTermExplain($entityMain->getTermExplain());
			$entityNewMain->setHandover($entityMain->getHandover());
			$entityNewMain->setIllustFilename($entityMain->getIllustFilename());
			$entityNewMain->setIllustCaption($entityMain->getIllustCaption());
			$entityNewMain->setIllustKana($entityMain->getIllustKana());
			$entityNewMain->setIllustNombre($entityMain->getIllustNombre());
			$entityNewMain->setUserId($entityMain->getUserId());
			$entityNewMain->setDeleteFlag(false);

			$em->persist($entityNewMain);
			$em->flush();
			$em->getConnection()->commit();
		} catch (\Exception $e){
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}

		unset($entityNewMain);

		return $newTermId;
	}

	public function copyExpTerm($em, $entityExp, $newTermId){

		$em->getConnection()->beginTransaction();

		try{
			foreach($entityExp as $entityExpRec){
				$entityNewExp = new ExplainIndex();

				$entityNewExp->setMainTermId($newTermId);
				$entityNewExp->setIndexTerm($entityExpRec['indexTerm']);
				$entityNewExp->setIndexAddLetter($entityExpRec['indexAddLetter']);
				$entityNewExp->setIndexKana($entityExpRec['indexKana']);
				$entityNewExp->setNombre($entityExpRec['nombre']);
				$entityNewExp->setDeleteFlag(false);

				$em->persist($entityNewExp);
				unset($entityNewExp);
			}

			$em->flush();
			$em->getConnection()->commit();
		} catch (\Exception $e){
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
	}

	public function copySubTerm($em, $entitySub, $newTermId){

		$em->getConnection()->beginTransaction();

		try{
			$arr_rtn_id = array();
			foreach($entitySub as $entitySubRec){
				$entityNewSub = new SubTerm();

				$entityNewSub->setMainTermId($newTermId);
				$entityNewSub->setSubTerm($entitySubRec['sub_term']);
				$entityNewSub->setRedLetter($entitySubRec['red_letter']);
				$entityNewSub->setTextFrequency($entitySubRec['text_frequency']);
				$entityNewSub->setCenterFrequency($entitySubRec['center_frequency']);
				$entityNewSub->setNewsExam($entitySubRec['news_exam']);
				$entityNewSub->setDelimiter($entitySubRec['delimiter']);
				$entityNewSub->setKana($entitySubRec['kana']);
				$entityNewSub->setDelimiterKana($entitySubRec['delimiter_kana']);
				$entityNewSub->setIndexAddLetter($entitySubRec['index_add_letter']);
				$entityNewSub->setIndexKana($entitySubRec['index_kana']);
				$entityNewSub->setNombre($entitySubRec['nombre']);
				$entityNewSub->setDeleteFlag(false);

				$em->persist($entityNewSub);
				$em->flush();

				array_push($arr_rtn_id, $entityNewSub->getId());
				unset($entityNewSub);
			}

			$em->getConnection()->commit();
		} catch (\Exception $e){
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
		return $arr_rtn_id;
	}

	public function copySynTerm($em, $entitySyn, $newTermId){

		$em->getConnection()->beginTransaction();

		try{
			$arr_rtn_id = array();
			foreach($entitySyn as $entitySynRec){
				$entityNewSyn = new Synonym();

				$entityNewSyn->setMainTermId($newTermId);
				$entityNewSyn->setTerm($entitySynRec['term']);
				$entityNewSyn->setRedLetter($entitySynRec['red_letter']);
				$entityNewSyn->setSynonymId($entitySynRec['synonym_id']);
				$entityNewSyn->setTextFrequency($entitySynRec['text_frequency']);
				$entityNewSyn->setCenterFrequency($entitySynRec['center_frequency']);
				$entityNewSyn->setNewsExam($entitySynRec['news_exam']);
				$entityNewSyn->setDelimiter($entitySynRec['delimiter']);
				$entityNewSyn->setIndexAddLetter($entitySynRec['index_add_letter']);
				$entityNewSyn->setIndexKana($entitySynRec['index_kana']);
				$entityNewSyn->setNombre($entitySynRec['nombre']);
				$entityNewSyn->setDeleteFlag(false);

				$em->persist($entityNewSyn);
				$em->flush();

				array_push($arr_rtn_id, $entityNewSyn->getId());
				unset($entityNewSyn);
			}

			$em->getConnection()->commit();
		} catch (\Exception $e){
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
		return $arr_rtn_id;
	}

	public function copyRefTerm($em, $entityRef, $newTermId){

		$em->getConnection()->beginTransaction();

		try{
			foreach($entityRef as $entityRefRec){
				$entityNewRef = new Refer();

				$entityNewRef->setMainTermId($newTermId);
				$entityNewRef->setReferTermId($entityRefRec['refer_term_id']);
				$entityNewRef->setNombre($entityRefRec['nombre']);
				$entityNewRef->setDeleteFlag(false);

				$em->persist($entityNewRef);
				unset($entityNewRef);
			}
			$em->flush();
			$em->getConnection()->commit();
		} catch (\Exception $e){
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
	}

	public function copyCenterData($em, $entityCenter, $newTermId, $newSubId, $newSynId){

		$em->getConnection()->beginTransaction();

		try{
			$idx = 0;
			$idx_sub = 0;
			$idx_syn = 0;

			$this->get('logger')->error("***copy start***");
			foreach($entityCenter as $entityCenterRec){
				$entityNewCenter = new Center();
				$idx++;

				$this->get('logger')->error("***idx***".$idx);

				$entityNewCenter->setMainTermId($newTermId);
				if($entityCenterRec->getYougoFlag() == 1){
					$entityNewCenter->setSubTermId(0);

					if($idx == 10){$idx = 0;}
				}elseif($entityCenterRec->getYougoFlag() == 2){
					$this->get('logger')->error("***idx_sub***".$idx_sub);
					$this->get('logger')->error("***newSubId***".$newSubId[$idx_sub].":".$idx_sub);
					$entityNewCenter->setSubTermId($newSubId[$idx_sub]);

					if($idx == 10){
						$idx = 0;
						$idx_sub++;
					}

				}else{
					$this->get('logger')->error("***newSynId***".$newSynId[$idx_syn].":".$idx_syn);
					$entityNewCenter->setSubTermId($newSynId[$idx_syn]);

					if($idx == 10){
						$idx = 0;
						$idx_syn++;
					}

				}
				$entityNewCenter->setYougoFlag($entityCenterRec->getYougoFlag());
				$entityNewCenter->setYear($entityCenterRec->getYear());
				$entityNewCenter->setMainExam($entityCenterRec->getMainExam());
				$entityNewCenter->setSubExam($entityCenterRec->getSubExam());
				$entityNewCenter->setDeleteFlag(false);

				$em->persist($entityNewCenter);
				$em->flush();
			}

			$em->getConnection()->commit();
		} catch (\Exception $e){
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
	}

	public function copyCenterDataByYear($em, $entityCenter, $newTermId, $newSubId, $newSynId, $wkYear){

		$em->getConnection()->beginTransaction();

		try{
			$idx = 0;
			$idx_sub = 0;
			$idx_syn = 0;

			$wkSubTermId = 0;
			$wkYougoFlag = 1;

			$wkStartYear = $wkYear;
			$wkEndYear = $wkYear + 10;

			foreach($entityCenter as $entityCenterRec){
				$idx++;

				if($idx > 10){
					// DBから10件読み込んだ後、対象年がある場合は、初期データを登録する
					for($i = $wkYear; $wkYear < $wkEndYear; $wkYear++){
						$entityNewCenter = new Center();

						$entityNewCenter->setMainTermId($newTermId);

						$entityNewCenter->setSubTermId($wkSubTermId);
						$entityNewCenter->setYougoFlag($wkYougoFlag);

						$entityNewCenter->setYear($wkYear);
						$entityNewCenter->setMainExam(0);
						$entityNewCenter->setSubExam(0);
						$entityNewCenter->setDeleteFlag(false);

						$em->persist($entityNewCenter);
						unset($entityNewCenter);
					}

					$wkYear = $wkStartYear;
					$idx = 1;

				}

				if($idx < 11){
					if($wkYear > $entityCenterRec->getYear()){
						// DBの実施年より対象年が大きい場合、スキップ
					}else{
						// DBの実施年と対象年が等しい場合、元データを複製する
						$entityNewCenter = new Center();

						$entityNewCenter->setMainTermId($newTermId);
						if($entityCenterRec->getYougoFlag() == 1){
							$entityNewCenter->setSubTermId(0);
							$wkSubTermId = null;

						}elseif($entityCenterRec->getYougoFlag() == 2){
							$entityNewCenter->setSubTermId($newSubId[$idx_sub]);
							$wkSubTermId = $newSubId[$idx_sub];

							if($idx == 10){
								$idx_sub++;
							}

						}else{
							$entityNewCenter->setSubTermId($newSynId[$idx_syn]);
							$wkSubTermId = $newSynId[$idx_syn];

							if($idx == 10){
								$idx_syn++;
							}

						}
						$entityNewCenter->setYougoFlag($entityCenterRec->getYougoFlag());
						$wkYougoFlag = $entityCenterRec->getYougoFlag();
						$entityNewCenter->setYear($entityCenterRec->getYear());
						$entityNewCenter->setMainExam($entityCenterRec->getMainExam());
						$entityNewCenter->setSubExam($entityCenterRec->getSubExam());
						$entityNewCenter->setDeleteFlag(false);

						$em->persist($entityNewCenter);
						unset($entityNewCenter);
						$wkYear++;
					}
				}
			}

			// DBから10件読み込んだ後、対象年がある場合は、初期データを登録する
			for($i = $wkYear; $wkYear < $wkEndYear; $wkYear++){
				$entityNewCenter = new Center();

				$entityNewCenter->setMainTermId($newTermId);

				$entityNewCenter->setSubTermId($wkSubTermId);
				$entityNewCenter->setYougoFlag($wkYougoFlag);

				$entityNewCenter->setYear($wkYear);
				$entityNewCenter->setMainExam(0);
				$entityNewCenter->setSubExam(0);
				$entityNewCenter->setDeleteFlag(false);

				$em->persist($entityNewCenter);
				unset($entityNewCenter);
			}

			$em->flush();
			$em->getConnection()->commit();
		} catch (\Exception $e){
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
	}

	/**
	 * @param unknown $yougoid
	 * @param unknown $userid
	 * @return boolean
	 */
	public function openGenko($yougoid, $userid){
		// 用語ID存在チェック
		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Vacant')->findBy(array(
				'termId' =>$yougoid,
				'userId' =>$userid,
				'deleteFlag' => FALSE
		));
		if($entity){
			return true;
		}

		try {
			$em = $this->get('doctrine.orm.entity_manager');
			$em->getConnection()->beginTransaction();
			$entity = new Vacant();
			$entity->setTermId($yougoid);
			$entity->setUserId($userid);
			$entity->setStartDate(new \DateTime());
			$entity->setCreateDate(new \DateTime());

			$em->persist($entity);

			$em->flush();
			$em->getConnection()->commit();
		} catch (\Exception $e){
			// もし、DBに登録失敗した場合rollbackする
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return false;
		}
		return true;
	}

	/**
	 * @param unknown $yougoid
	 * @param unknown $userid
	 * @return boolean
	 */
	public function closeGenko($userid){

		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Vacant')->findBy(array(
				'userId' =>$userid,
				'deleteFlag' => FALSE
		));
		if(!$entity){
			return true;
		}

		foreach($entity as $entity_el){
			$entity_el->setEndDate(new \DateTime());
			$entity_el->setDeleteFlag(true);
			$entity_el->setModifyDate(new \DateTime());
			$entity_el->setDeleteDate(new \DateTime());

			// transaction
			$em = $this->get('doctrine.orm.entity_manager');
			$em->getConnection()->beginTransaction();

			try {
				// 登録
				$em->flush();
				// 実行
				$em->getConnection()->commit();
			} catch(\Exception $e){
				// もし、DBに登録失敗した場合rollbackする
				$em->getConnection()->rollback();
				$em->close();

				// log
				$this->get('logger')->error($e->getMessage());
				$this->get('logger')->error($e->getTraceAsString());

				return false;
			}
		}

		return true;
	}

	/**
	 * session data remove
	 */
	protected function searchSessionRemove($request){
		$session = $request->getSession();
		$session->remove(self::SES_SEARCH_HAN_KEY);
	}

	protected function OutputLog($type, $file, $log) {
		$year = date ( "Y" );
		$month = date ( "m" );
		$day = date ( "d" );
		$hour = date ( "H" );

		// タイムゾーンの設定（タイムゾーンを設定しないとログ書き出し時にエラーとなる）
		date_default_timezone_set ( 'Asia/Tokyo' );

		// ログ出力先の決定
		$logName = $file;
		$logDir = "./app/logs/" . "$year/$month/$day/";
		$error_log = $logDir . $logName;

		// ディレクトリ作成
		if (! is_dir ( $logDir )) {
			// ディレクトリが存在しない場合作成
			mkdir ( $logDir, 0777, True );
			// umaskを考慮して再度777に変更
			chmod ( $logDir, 0777 );
		}

		// ファイル作成
		if (! file_exists ( $error_log )) {
			// ファイルが存在しない場合作成
			touch ( $error_log );
			// 権限変更
			chmod ( $error_log, 0666 );
		}

		// ログファイル出力
		$fp = fopen($error_log, "a");
		fwrite($fp, "[" . date("Y-m-d H:i:s") . "] [$type] $log" ."\n");
		fclose($fp);
	}

	protected function DownloadLog($logName){
		$year = date ( "Y" );
		$month = date ( "m" );
		$day = date ( "d" );
		$hour = date ( "H" );

		// ログ出力先の決定
		$logDir = "./app/logs/" . "$year/$month/$day/";
		$error_log = $logDir . $logName;

		// ダウンロードファイル名設定
		$finename = "ノンブル取込みエラー_".$year.$month.$day.".log";

		// 取込データエラーログ
		$response = new BinaryFileResponse($error_log);

		$response->setStatusCode(200);
		$response->headers->set('Content-Encoding', 'UTF-8');
		$response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
		$response->headers->set('Content-Transfer-Encoding', 'binary');
		$response->headers->set('Content-Type', 'application/force-download');
		$response->headers->set('Content-Type', 'application/octet-stream');
		$response->headers->set('Content-Disposition', 'attachment; filename='. $finename);

		return $response;
	}

	protected function RemoveLog($file) {
		$year = date ( "Y" );
		$month = date ( "m" );
		$day = date ( "d" );
		$hour = date ( "H" );

		// ログ出力先の決定
		$logName = $file;
		$logDir = "./app/logs/" . "$year/$month/$day/";
		$error_log = $logDir . $logName;

		// ディレクトリ作成
		if (! is_dir ( $logDir )) {
			// ディレクトリが存在しない場合作成
			mkdir ( $logDir, 0777, True );
			// umaskを考慮して再度777に変更
			chmod ( $logDir, 0777 );
		}

		// ファイル削除
		if (file_exists ( $error_log )) {
			unlink ( $error_log );
		}

	}

}