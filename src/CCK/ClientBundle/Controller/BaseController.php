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
		if(preg_match("/MSIE/i", $ua) || preg_match("/Windows/i", $ua)){
			mb_convert_variables('sjis-win', 'UTF-8', $value);
		}
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

	/**
	 * session data remove
	 */
	protected function searchSessionRemove($request){
		$session = $request->getSession();
		$session->remove(self::SES_SEARCH_HAN_KEY);
	}

}