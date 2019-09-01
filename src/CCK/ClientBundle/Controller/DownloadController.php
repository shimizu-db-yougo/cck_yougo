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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use ZipArchive;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * download controller.
 * ダウンロードコントローラー
 *
 */
class DownloadController extends BaseController {

	private $termCsvHeaderSorting = [
			'term_id',
			'cur_name',
			'hen',
			'sho',
			'dai',
			'chu',
			'ko',
			'header_position',
			'print_order',
			'main_term',
			'red_letter',
			'text_frequency',
			'center_frequency',
			'news_exam',
			'delimiter',
			'western_language',
			'birth_year',
			'kana',
			'index_add_letter',
			'index_kana',
			'index_original',
			'index_original_kana',
			'index_abbreviation',
			'nombre',
			'term_explain',
			'exp_term',
			'exp_index_kana',
			'exp_index_add_letter',
			'exp_nombre',
			'sub_id',
			'sub_term',
			'sub_red_letter',
			'sub_kana',
			'sub_text_frequency',
			'sub_center_frequency',
			'sub_news_exam',
			'sub_delimiter',
			'sub_delimiter_kana',
			'sub_index_add_letter',
			'sub_index_kana',
			'sub_nombre',
			'syn_id',
			'syn_synonym_id',
			'syn_term',
			'syn_red_letter',
			'syn_kana',
			'syn_text_frequency',
			'syn_center_frequency',
			'syn_news_exam',
			'syn_delimiter',
			'syn_index_add_letter',
			'syn_index_kana',
			'syn_nombre',
			'ref_hen',
			'ref_sho',
			'ref_dai',
			'ref_chu',
			'ref_ko',
			'ref_refer_term_id',
			'ref_main_term',
			'ref_nombre',
			'illust_filename',
			'illust_caption',
			'illust_kana',
			'illust_nombre',
			'handover'
	];

	/**
	 * @Route("/csv/download/index", name="client.csv.export")
	 * @Template()
	 */
	public function indexAction(Request $request) {
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
		$hen_list = array();
		if(isset($version)){
			$hen_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
					'versionId' => $version,
					'headerId' => '1',
					'deleteFlag' => FALSE
			));
		}
		$sho_list = array();
		if((isset($version))&&(isset($hen))){
			$sho_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
					'versionId' => $version,
					'hen' => $hen,
					'headerId' => '2',
					'deleteFlag' => FALSE
			));
		}
	
		return array(
				'currentUser' => ['user_id' => $this->getUser()->getUserId(), 'name' => $this->getUser()->getName()],
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
				'hen_list' => $hen_list,
				'sho_list' => $sho_list,
		);
	}
	
	/**
	 * @Route("/typesetting/download", name="client.typesetting.download")
	 * @Method("POST|GET")
	 */
	public function typesettingDownloadAction(Request $request){
		//$zip = new ZipArchive();
		$tmpFilePath = tempnam(sys_get_temp_dir(), 'tmp');
		// 作業ファイルをオープン
		//$result = $zip->open($tmpFilePath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);

		/*if($result !== true) {
			$response = $this->responseForm('404', 'HTTP/1.1 404 Not Found');
			return $response;
		}*/

		if(!$request->query->has('curriculum')){
			$response = $this->responseForm('404', 'HTTP/1.1 404 Not Found');
			return $response;
		}

		$em = $this->getDoctrine()->getManager();
		// 教科名の取得
		$curriculumId = $request->query->get('curriculum');
		$versionId = $request->query->get('version');

		$entityCurriculum = $em->getRepository('CCKCommonBundle:Curriculum')->getCurriculumVersionList($versionId);

		$cur_name = '';
		if($entityCurriculum){
			$cur_name = $entityCurriculum[0]['cur_name'] . '_' . $entityCurriculum[0]['name'];
		}

		// 本文/索引の区分
		$type = $request->query->get('type');
		if($type == '0'){
			$type_name = '本文';
		}else{
			$type_name = '索引';
		}

		// 見出しID(編、章)
		$hen = $request->query->get('hen');
		$sho = $request->query->get('sho');

		// 用語ID
		$term_id = $request->query->get('term_id');

		// ファイル名の生成
		$outFileName = $cur_name . '_' . $type_name . '_' . date('YmdHis') . ".csv";

		$path = $this->container->getParameter('archive')['dir_path'];
		$webpath = $request->getSchemeAndHttpHost() . '/' . $this->container->getParameter('archive')['link'];

		/*print("curriculumId".$curriculumId);
		print("versionId".$versionId);
		print("type:".$type);
		print("hen:".$hen);
		print("sho:".$sho);
		print("term_id:".$term_id);
		exit();*/
		
		$entity = $em->getRepository('CCKCommonBundle:MainTerm')->getMainTermList($versionId,$term_id,$hen,$sho);

		// 原稿データCSV生成
		if($entity){
			$body_list = $this->constructManuscriptCSV($term_id, $request, $entity, $outFileName);
		}else{
			$body_list = false;
		}

		if($body_list === false){
			$response = $this->responseForm('204', 'HTTP/1.1 204 No Content');
			return $response;
		}

		// response
		$response = new StreamedResponse(function() use($body_list) {
			$handle = fopen('php://output', 'w+');

			foreach ($body_list as $value) {
				$value = str_replace("\n", chr(10), $value);
				fputs($handle, implode('	', $value)."\n");
			}

			fclose($handle);
		});

		$response->setStatusCode(200);
		$response->headers->set('Content-Encoding', 'UTF-8');
		$response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
		$response->headers->set('Content-Transfer-Encoding', 'binary');
		$response->headers->set('Content-Type', 'application/force-download');
		$response->headers->set('Content-Type', 'application/octet-stream');
		$response->headers->set('Content-Disposition', 'attachment; filename='. $outFileName);

		// CSVファイル
		//$return_obj = $this->collectFileData($path, $webpath, 'CSV', $return_obj[0]);

		//$zip = $return_obj[0];

		// ストリームを閉じる
		//$zip->close();

		/*$response = new BinaryFileResponse($tmpFilePath);
		$response->headers->set('Content-Length', filesize($tmpFilePath));
		$response->headers->set('Content-type', 'application/octet-stream');
		$response->headers->set('Access-Control-Allow-Origin', '*');
		BinaryFileResponse::trustXSendfileTypeHeader();
		$response->setContentDisposition(
				ResponseHeaderBag::DISPOSITION_ATTACHMENT,
				$outFileName
		);*/

		return $response;

	}

	// 自動組版に使用するファイルの収集
	private function collectFileData($path, $webpath, $dirname, $zip){
		$files = array();
		$files = $this->openDir($path . '/' . $dirname, $webpath . '/' . $dirname, $files);

		foreach ($files as $el_file_list){
			// zipファイルに追加（繰り返せば複数ファイル追加可能）
			$zip->addFromString($el_file_list['name'] , file_get_contents($el_file_list['realpath']));
		}

		return array($zip, $files);
	}

	// 原稿データCSV生成
	private function constructManuscriptCSV($genkoId, $request, $entity, $outFileName) {
		$em = $this->getDoctrine()->getManager();

		$type = $request->query->get('type');
		
		$body_list = [];
		foreach($entity as $mainTermRec){

			$entity_exp = $em->getRepository('CCKCommonBundle:ExplainIndex')->getExplainTerms($mainTermRec['term_id']);
			$entity_sub = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSubterm($mainTermRec['term_id']);
			$entity_syn = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSynonym($mainTermRec['term_id']);
			$entity_ref = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfRefer($mainTermRec['term_id']);

			// body
			$body = $this->encoding($this->generateBody($mainTermRec,$entity_exp, $entity_sub, $entity_syn, $entity_ref, $type), $request);
			array_push($body_list,$body);
		}

		return $body_list;

	}

	/**
	 * @param  array $header
	 * @return array $trans
	 */
	private function generateHeader($header_base, $header_content, $kikaku_id, $page){
		// trans service
		$translator = $this->get('translator');

		// データを一つにまとめないといけないものは別で作業する
		// それ以外は翻訳する
		$result = [];
		// 基本情報
		foreach ($header_base as $key => $value) {
			$result[$key] = $translator->trans('csv.genko.' . $key);
		}

		// 本文情報
		foreach ($header_content as $key => $value) {
			$result[$key] = $translator->trans('csv.genko.' . $key);
		}

		//TODO:台割情報　要検討
		$result['lr'] = $translator->trans('csv.genko.lr');
		$result['master_page'] = $translator->trans('csv.genko.master_page');
		// 診療科目などはデータを結合しないといけないので別で生成する
		$result['shinryo_kamoku'] = $translator->trans('csv.genko.shinryo_kamoku');
		$result['shinryo_naiyo'] = $translator->trans('csv.genko.shinryo_naiyo');

		for($idx=1;$idx<=4;$idx++){
			$result['doctor_name' . $idx] = $translator->trans('csv.genko.doctor_name' . $idx);
			$result['doctor_post' . $idx] = $translator->trans('csv.genko.doctor_post' . $idx);
			$result['doctor_roma' . $idx] = $translator->trans('csv.genko.doctor_roma' . $idx);
		}

		$result['telno'] = $translator->trans('csv.genko.telno');
		$result['adress'] = $translator->trans('csv.genko.adress');
		$result['parking'] = $translator->trans('csv.genko.parking');
		$result['kyushinbi'] = $translator->trans('csv.genko.kyushinbi');

		for($idx=1;$idx<=3;$idx++){
			$result['shinryo_jikan' . $idx] = $translator->trans('csv.genko.shinryo_jikan' . $idx);
			foreach ($this->dayOfWeek as $youbi){
				$result['shinryo_jikan' . '_' . $youbi . $idx] = $translator->trans('csv.genko.shinryo_jikancsv.genko.' . $youbi . $idx);
			}
		}

		for($idx=1;$idx<=7;$idx++){
			$result['icon' . $idx] = $translator->trans('csv.genko.icon' . $idx);
		}

		$result['image_main'] = $translator->trans('csv.genko.image_main');
		$result['image_data'] = $translator->trans('csv.genko.image_data');

		$result['honmon_text'] = $translator->trans('csv.genko.honmon_text');
		$result['station'] = $translator->trans('csv.genko.station');

		$result['map'] = $translator->trans('csv.genko.map');
		$result['link'] = $translator->trans('csv.genko.link');
		$result['han'] = $translator->trans('csv.genko.han');
		$result['year'] = $translator->trans('csv.genko.year');

		// 企画、ページ数に応じて出力項目を変える
		if($kikaku_id == 'kikaku.area'){
			if($page == '2'){
				$kikaku_page = 1;
			}else{
				$kikaku_page = 2;
			}
		}else{
			if($page == '2'){
				$kikaku_page = 3;
			}else{
				$kikaku_page = 4;
			}
		}

		// 順番を決める
		$trans = [];
		foreach($this->area_featureCsvHeaderSorting as $sort){
			if(!isset($result[$sort[0]])) continue;
			if($sort[$kikaku_page] == false) continue;
			$trans[] = $result[$sort[0]];
		}

		return $trans;
	}

	/**
	 * @param  array $coupons
	 * @return array $body
	 */
	private function generateBody($main, $expterm, $subterm, $synterm, $refterm, $type){
		$body = [];
		$result = [];

		$em = $this->getDoctrine()->getManager();
		$translator = $this->get('translator');

		$search_newline = '/\r\n|\r|\n/';

		// 主用語
		$this->replaceMainField($main);
		
		$main['nombre'] = (($type == '1') ? $main['nombre'] : '');

		// 解説内索引用語
		$exp = [];
		$exp['exp_term_id'] = "";
		$exp['exp_term'] = "";
		$exp['exp_index_kana'] = "";
		$exp['exp_index_add_letter'] = "";
		$exp['exp_nombre'] = "";

		if($expterm){
			foreach ($expterm as $exptermRec) {
				$exptermRec['id'] = 'K'.str_pad($exptermRec['id'], 5, 0, STR_PAD_LEFT);
				
				$exp['exp_term_id'] .= $exptermRec['id'] . '\v';
				$exp['exp_term'] .= $exptermRec['indexTerm'] . '\v';
				$exp['exp_index_kana'] .= $exptermRec['indexKana'] . '\v';
				$exp['exp_index_add_letter'] .= $exptermRec['indexAddLetter'] . '\v';
				$exp['exp_nombre'] .= (($type == '1') ? $exptermRec['nombre'] : '') . '\v';
			}
			foreach ($exp as $key => $val) {
				$exp[$key] = mb_substr($val,0,mb_strlen($val)-2);
			}
		}
		
		// サブ用語
		$sub = [];
		$sub['sub_id'] = "";
		$sub['sub_term'] = "";
		$sub['sub_red_letter'] = "";
		$sub['sub_kana'] = "";
		$sub['sub_text_frequency'] = "";
		$sub['sub_center_frequency'] = "";
		$sub['sub_news_exam'] = "";
		$sub['sub_delimiter'] = "";
		$sub['sub_delimiter_kana'] = "";
		$sub['sub_index_add_letter'] = "";
		$sub['sub_index_kana'] = "";
		$sub['sub_nombre'] = "";

		if($subterm){
			foreach ($subterm as $subtermRec) {
				$this->replaceSubField($subtermRec);

				$sub['sub_id'] .= $subtermRec['id'] . '\v';
				$sub['sub_term'] .= $subtermRec['sub_term'] . '\v';
				$sub['sub_red_letter'] .= $subtermRec['red_letter'] . '\v';
				$sub['sub_kana'] .= $subtermRec['kana'] . '\v';
				$sub['sub_text_frequency'] .= $subtermRec['text_frequency'] . '\v';
				$sub['sub_center_frequency'] .= $subtermRec['center_frequency'] . '\v';
				$sub['sub_news_exam'] .= $subtermRec['news_exam'] . '\v';
				$sub['sub_delimiter'] .= $subtermRec['delimiter'] . '\v';
				$sub['sub_delimiter_kana'] .= $subtermRec['delimiter_kana'] . '\v';
				$sub['sub_index_add_letter'] .= $subtermRec['index_add_letter'] . '\v';
				$sub['sub_index_kana'] .= $subtermRec['index_kana'] . '\v';
				$sub['sub_nombre'] .= (($type == '1') ? $subtermRec['nombre'] : '') . '\v';
			}
			foreach ($sub as $key => $val) {
				$sub[$key] = mb_substr($val,0,mb_strlen($val)-2);
			}
		}

		// 同対類用語
		$syn = [];
		$syn['syn_id'] = "";
		$syn['syn_synonym_id'] = "";
		$syn['syn_term'] = "";
		$syn['syn_red_letter'] = "";
		$syn['syn_kana'] = "";
		$syn['syn_text_frequency'] = "";
		$syn['syn_center_frequency'] = "";
		$syn['syn_news_exam'] = "";
		$syn['syn_delimiter'] = "";
		$syn['syn_index_add_letter'] = "";
		$syn['syn_index_kana'] = "";
		$syn['syn_nombre'] = "";

		if($synterm){
			foreach ($synterm as $syntermRec) {
				$this->replaceSynField($syntermRec);

				$syn['syn_id'] .= $syntermRec['id'] . '\v';
				$syn['syn_synonym_id'] .= $syntermRec['synonym_id'] . '\v';
				$syn['syn_term'] .= $syntermRec['term'] . '\v';
				$syn['syn_red_letter'] .= $syntermRec['red_letter'] . '\v';
				$syn['syn_kana'] .= "" . '\v';
				$syn['syn_text_frequency'] .= $syntermRec['text_frequency'] . '\v';
				$syn['syn_center_frequency'] .= $syntermRec['center_frequency'] . '\v';
				$syn['syn_news_exam'] .= $syntermRec['news_exam'] . '\v';
				$syn['syn_delimiter'] .= $syntermRec['delimiter'] . '\v';
				$syn['syn_index_add_letter'] .= $syntermRec['index_add_letter'] . '\v';
				$syn['syn_index_kana'] .= $syntermRec['index_kana'] . '\v';
				$syn['syn_nombre'] .= (($type == '1') ? $syntermRec['nombre'] : '') . '\v';
			}
			foreach ($syn as $key => $val) {
				$syn[$key] = mb_substr($val,0,mb_strlen($val)-2);
			}
		}

		// 指矢印参照
		$ref = [];
		$ref['ref_hen'] = "";
		$ref['ref_sho'] = "";
		$ref['ref_dai'] = "";
		$ref['ref_chu'] = "";
		$ref['ref_ko'] = "";
		$ref['ref_refer_term_id'] = "";
		$ref['ref_main_term'] = "";
		$ref['ref_nombre'] = "";

		if($refterm){
			foreach ($refterm as $reftermRec) {
				$reftermRec['refer_term_id'] = 'M'.str_pad($reftermRec['refer_term_id'], 5, 0, STR_PAD_LEFT);
				$this->getHeaderName($reftermRec);

				$ref['ref_hen'] .= $reftermRec['hen'] . '\v';
				$ref['ref_sho'] .= $reftermRec['sho'] . '\v';
				$ref['ref_dai'] .= $reftermRec['dai'] . '\v';
				$ref['ref_chu'] .= $reftermRec['chu'] . '\v';
				$ref['ref_ko'] .= $reftermRec['ko'] . '\v';
				$ref['ref_refer_term_id'] .= $reftermRec['refer_term_id'] . '\v';
				$ref['ref_main_term'] .= $reftermRec['main_term'] . '\v';
				$ref['ref_nombre'] .= (($type == '1') ? $reftermRec['nombre'] : '') . '\v';
			}
			foreach ($ref as $key => $val) {
				$ref[$key] = mb_substr($val,0,mb_strlen($val)-2);
			}
		}

		$result = array_merge($main,$exp,$sub,$syn,$ref);

		// 順番を決める
		$trans = [];
		foreach($this->termCsvHeaderSorting as $sort){
			if(!array_key_exists($sort, $result)) continue;
			$trans[] = trim($result[$sort]);
		}
		//$body[] = $trans;

		//print_r($trans);
		//exit();

		return $trans;
	}

	private function replaceMainField(&$main){
		$main['header_position'] = "";
		// 主用語　見出し名称の取得
		$this->getHeaderName($main);

		$main['term_id'] = 'M'.str_pad($main['term_id'], 5, 0, STR_PAD_LEFT);

		if($main['red_letter'] == '1'){
			$main['red_letter'] = '●';
		}else{
			$main['red_letter'] = '';
		}

		if($main['text_frequency'] >= 6){
			$main['text_frequency'] = 'A';
		}elseif(($main['text_frequency'] >= 3)&&($main['text_frequency'] <= 5)){
			$main['text_frequency'] = 'B';
		}elseif(($main['text_frequency'] >= 1)&&($main['text_frequency'] <= 2)){
			$main['text_frequency'] = 'C';
		}else{
			$main['text_frequency'] = '';
		}

		if($main['center_frequency'] == 0){
			$main['center_frequency'] = '';
		}
		
		if($main['news_exam'] == '1'){
			$main['news_exam'] = 'N';
		}else{
			$main['news_exam'] = '';
		}

		if($main['delimiter'] == '0'){
			$main['delimiter'] = '';
		}elseif($main['delimiter'] == '1'){
			$main['delimiter'] = 'と';
		}elseif($main['delimiter'] == '2'){
			$main['delimiter'] = '，';
		}elseif($main['delimiter'] == '3'){
			$main['delimiter'] = '・';
		}elseif($main['delimiter'] == '4'){
			$main['delimiter'] = '／';
		}elseif($main['delimiter'] == '5'){
			$main['delimiter'] = '（';
		}elseif($main['delimiter'] == '6'){
			$main['delimiter'] = '）';
		}
	}

	private function replaceSubField(&$sub){
		$sub['id'] = 'S'.str_pad($sub['id'], 5, 0, STR_PAD_LEFT);

		if($sub['red_letter'] == '1'){
			$sub['red_letter'] = '●';
		}else{
			$sub['red_letter'] = '';
		}

		if($sub['text_frequency'] >= 6){
			$sub['text_frequency'] = 'A';
		}elseif(($sub['text_frequency'] >= 3)&&($sub['text_frequency'] <= 5)){
			$sub['text_frequency'] = 'B';
		}elseif(($sub['text_frequency'] >= 1)&&($sub['text_frequency'] <= 2)){
			$sub['text_frequency'] = 'C';
		}else{
			$sub['text_frequency'] = '';
		}

		if($sub['center_frequency'] == 0){
			$sub['center_frequency'] = '';
		}
		
		if($sub['news_exam'] == '1'){
			$sub['news_exam'] = 'N';
		}else{
			$sub['news_exam'] = '';
		}

		if($sub['delimiter'] == '0'){
			$sub['delimiter'] = '';
		}elseif($sub['delimiter'] == '1'){
			$sub['delimiter'] = 'と';
		}elseif($sub['delimiter'] == '2'){
			$sub['delimiter'] = '，';
		}elseif($sub['delimiter'] == '3'){
			$sub['delimiter'] = '・';
		}elseif($sub['delimiter'] == '4'){
			$sub['delimiter'] = '／';
		}elseif($sub['delimiter'] == '5'){
			$sub['delimiter'] = '（';
		}elseif($sub['delimiter'] == '6'){
			$sub['delimiter'] = '）';
		}

		if($sub['delimiter_kana'] == '0'){
			$sub['delimiter_kana'] = '';
		}elseif($sub['delimiter_kana'] == '1'){
			$sub['delimiter_kana'] = 'と';
		}elseif($sub['delimiter_kana'] == '2'){
			$sub['delimiter_kana'] = '，';
		}elseif($sub['delimiter_kana'] == '3'){
			$sub['delimiter_kana'] = '・';
		}elseif($sub['delimiter_kana'] == '4'){
			$sub['delimiter_kana'] = '／';
		}elseif($sub['delimiter_kana'] == '5'){
			$sub['delimiter_kana'] = '（';
		}elseif($sub['delimiter_kana'] == '6'){
			$sub['delimiter_kana'] = '）';
		}elseif($sub['delimiter_kana'] == '7'){
			$sub['delimiter_kana'] = '）（';
		}
	}

	private function replaceSynField(&$syn){
		$syn['id'] = 'D'.str_pad($syn['id'], 5, 0, STR_PAD_LEFT);

		if($syn['synonym_id'] == '1'){
			$syn['synonym_id'] = '同';
		}elseif($syn['synonym_id'] == '2'){
			$syn['synonym_id'] = '対';
		}else{
			$syn['synonym_id'] = '類';
		}

		if($syn['red_letter'] == '1'){
			$syn['red_letter'] = '●';
		}else{
			$syn['red_letter'] = '';
		}

		if($syn['text_frequency'] >= 6){
			$syn['text_frequency'] = 'A';
		}elseif(($syn['text_frequency'] >= 3)&&($syn['text_frequency'] <= 5)){
			$syn['text_frequency'] = 'B';
		}elseif(($syn['text_frequency'] >= 1)&&($syn['text_frequency'] <= 2)){
			$syn['text_frequency'] = 'C';
		}else{
			$syn['text_frequency'] = '';
		}
		
		if($syn['center_frequency'] == 0){
			$syn['center_frequency'] = '';
		}
		
		if($syn['news_exam'] == '1'){
			$syn['news_exam'] = 'N';
		}else{
			$syn['news_exam'] = '';
		}

		if($syn['delimiter'] == '0'){
			$syn['delimiter'] = '';
		}elseif($syn['delimiter'] == '1'){
			$syn['delimiter'] = '　';
		}elseif($syn['delimiter'] == '2'){
			$syn['delimiter'] = '（';
		}elseif($syn['delimiter'] == '3'){
			$syn['delimiter'] = '）';
		}elseif($syn['delimiter'] == '4'){
			$syn['delimiter'] = '改行';
		}elseif($syn['delimiter'] == '5'){
			$syn['delimiter'] = '改行＋（';
		}elseif($syn['delimiter'] == '6'){
			$syn['delimiter'] = '）＋改行';
		}
	}

	private function getHeaderName(&$record){
		// 見出し名称取得
		$entityHeader = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findOneBy(array(
				'id' => $record['header_id'],
				'deleteFlag' => FALSE
		));

		$entityHen = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findOneBy(array(
				'versionId' => $record['ver_id'],
				'headerId' => 1,
				'hen' => $entityHeader->getHen(),
				'deleteFlag' => FALSE
		));
		$record['hen'] = $entityHen->getName();

		$entitySho = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findOneBy(array(
				'versionId' => $record['ver_id'],
				'headerId' => 2,
				'hen' => $entityHeader->getHen(),
				'sho' => $entityHeader->getSho(),
				'deleteFlag' => FALSE
		));

		$record['sho'] = "";
		if($entitySho){
			$record['sho'] = $entitySho->getName();
		}

		$entityDai = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findOneBy(array(
				'versionId' => $record['ver_id'],
				'headerId' => 3,
				'hen' => $entityHeader->getHen(),
				'sho' => $entityHeader->getSho(),
				'dai' => $entityHeader->getDai(),
				'deleteFlag' => FALSE
		));

		$record['dai'] = "";
		if($entityDai){
			$record['dai'] = $entityDai->getName();
		}

		$entityChu = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findOneBy(array(
				'versionId' => $record['ver_id'],
				'headerId' => 4,
				'hen' => $entityHeader->getHen(),
				'sho' => $entityHeader->getSho(),
				'dai' => $entityHeader->getDai(),
				'chu' => $entityHeader->getChu(),
				'deleteFlag' => FALSE
		));

		$record['chu'] = "";
		if($entityChu){
			$record['chu'] = $entityChu->getName();
		}

		$entityKo = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findOneBy(array(
				'versionId' => $record['ver_id'],
				'headerId' => 5,
				'hen' => $entityHeader->getHen(),
				'sho' => $entityHeader->getSho(),
				'dai' => $entityHeader->getDai(),
				'chu' => $entityHeader->getChu(),
				'ko' => $entityHeader->getKo(),
				'deleteFlag' => FALSE
		));

		$record['ko'] = "";
		if($entityKo){
			$record['ko'] = $entityKo->getName();
		}

	}



	private function unPadding($str){
		if(substr($str, 0, 1) == '0'){
			$str = substr($str, 1, 1);
		}
		return $str;
	}

	/**
	 * @param  array $header
	 * @return array $trans
	 */
	private function generateHeader_Report($header_base, $header_content, $page){
		// trans service
		$translator = $this->get('translator');

		// データを一つにまとめないといけないものは別で作業する
		// それ以外は翻訳する
		$result = [];
		// 基本情報
		foreach ($header_base as $key => $value) {
			$result[$key] = $translator->trans('csv.genko.' . $key);
		}

		// 本文情報
		foreach ($header_content as $key => $value) {
			$result[$key] = $translator->trans('csv.genko.' . $key);
		}

		//TODO:台割情報　要検討
		$result['lr'] = $translator->trans('csv.genko.lr');
		$result['no'] = $translator->trans('csv.genko.no');

		$result['reporter_image'] = $translator->trans('csv.genko.reporter_image');
		$result['catch'] = $translator->trans('csv.genko.report_catch');
		$result['clinic_image'] = $translator->trans('csv.genko.clinic_image');
		$result['interviewed_doctor'] = $translator->trans('csv.genko.interviewed_doctor');
		$result['company_name'] = $translator->trans('csv.genko.report_company_name');
		$result['telno'] = $translator->trans('csv.genko.report_telno');
		$result['adress'] = $translator->trans('csv.genko.report_adress');

		$result['step1_image'] = $translator->trans('csv.genko.step1_image');
		$result['step2_image'] = $translator->trans('csv.genko.step2_image');
		$result['step3_image'] = $translator->trans('csv.genko.step3_image');
		$result['step4_image'] = $translator->trans('csv.genko.step4_image');
		$result['step4-2_image'] = $translator->trans('csv.genko.step4-2_image');
		$result['srep5_image'] = $translator->trans('csv.genko.srep5_image');
		$result['after_image'] = $translator->trans('csv.genko.after_image');
		$result['doctor_image'] = $translator->trans('csv.genko.doctor_image');

		$result['doctor_name'] = $translator->trans('csv.genko.doctor_name');
		$result['doctor_post'] = $translator->trans('csv.genko.doctor_post');

		$result['q1'] = $translator->trans('csv.genko.q1');
		$result['q2'] = $translator->trans('csv.genko.q2');
		$result['q3'] = $translator->trans('csv.genko.q3');

		$result['link'] = $translator->trans('csv.genko.link');
		$result['han'] = $translator->trans('csv.genko.han');
		$result['year'] = $translator->trans('csv.genko.year');

		// ページ数に応じて出力項目を変える
		if($page == '2'){
			$kikaku_page = 1;
		}else{
			$kikaku_page = 2;
		}

		// 順番を決める
		$trans = [];
		foreach($this->reportCsvHeaderSorting as $sort){
			if(!isset($result[$sort[0]])) continue;
			if($sort[$kikaku_page] == false) continue;
			$trans[] = $result[$sort[0]];
		}

		return $trans;
	}

	/**
	 * @param  array $coupons
	 * @return array $body
	 */
	private function generateBody_Report($base, $content, $page, $classifiction){
		$body = [];
		$result = [];
		$doctor = [];
		$telno = [];
		$adress = [];

		$catch = [];
		$q1 = [];
		$q2 = [];
		$q3 = [];
		$reporter_name = [];

		$step1 = [];
		$step2 = [];
		$step3 = [];
		$step4 = [];
		$step5 = [];

		$lr = '';
		$page = '';
		$year = '';
		$nombre = '';
		$link = '';

		$em = $this->getDoctrine()->getManager();
		$translator = $this->get('translator');

		$search_newline = '/\r\n|\r|\n/';

		// 基本情報
		foreach ($base as $key => $value) {
			$value = preg_replace('/\t/', '', $value); //入力されたTabを削除
			// 画像　2P,1P共通
			if($key == 'image1'){
				$result['reporter_image'] = '';
			}elseif($key == 'image2'){
				$result['clinic_image'] = $value;
			}elseif($key == 'image3'){
				$result['doctor_image'] = $value;
			}elseif($key == 'image4'){
				$result['after_image'] = '';
			}elseif($key == 'image5'){
				$result['step1_image'] = $value;
			}elseif($key == 'image6'){
				$result['step2_image'] = $value;
			}elseif($key == 'image7'){
				$result['step3_image'] = $value;
			}elseif($key == 'image8'){
				$result['step4_image'] = $value;
			}elseif($key == 'image9'){
				$result['step4-2_image'] = '';
			}elseif($key == 'image10'){
				$result['srep5_image'] = $value;
			// テキスト
			}elseif(preg_match('/telno/i', $key)){
				$telno[] = $value;
			}elseif(preg_match('/adress/i', $key)){
				$adress[] = $value;

			}elseif($key == 'doctor1'){
				$doctor_info = unserialize($value);

				$result['doctor_name'] = $doctor_info['doctor_sei'] . ' ' . $doctor_info['doctor_name'];
				$result['doctor_post'] = $doctor_info['doctor_post'];

			}elseif($key == 'han_name'){
				$result['han'] = $value;
			}elseif($key == 'page'){
				$page = $value;
			}elseif($key == 'lr'){
				$lr = $value;
			}elseif($key == 'format'){
				$result['format'] = $classifiction;
			}elseif($key == 'volume_year'){
				$year = $value;
			}elseif($key == 'nombre'){
				$nombre = $value;
			}elseif($key == 'link'){
				$link = $value;
			}else{
				$result[$key] = preg_replace($search_newline, '¶', $value);
			}
		}

		if($page == '2'){
			$result['lr'] = '2P';
		}else{
			$result['lr'] = $translator->trans($this->container->getParameter('master.lr')[$lr]);
		}

		$result['no'] = '';
		$result['telno'] = preg_replace('/¶$/u', '', implode('¶', $telno));
		$result['adress'] = preg_replace('/¶+$/u', '', implode('¶', $adress));

		// 本文情報
		foreach ($content as $key => $value) {
			$value = preg_replace('/\t/', '', $value); //入力されたTabを削除
			if(preg_match('/catch/i', $key)){
				$catch[] = $value;
			}elseif(preg_match('/q1/i', $key)){
				$q1[] = preg_replace($search_newline, '¶', $value);
			}elseif(preg_match('/q2/i', $key)){
				$q2[] = preg_replace($search_newline, '¶', $value);
			}elseif(preg_match('/q3/i', $key)){
				$q3[] = preg_replace($search_newline, '¶', $value);
			}elseif($key == 'step1'){
				$step1[] = $value;
			}elseif($key == 'step12'){
				$step1[] = $value;
			}elseif($key == 'step2'){
				$step2[] = $value;
			}elseif($key == 'step22'){
				$step2[] = $value;
			}elseif($key == 'step3'){
				$step3[] = $value;
			}elseif($key == 'step32'){
				$step3[] = $value;
			}elseif($key == 'step4'){
				$step4[] = $value;
			}elseif($key == 'step42'){
				$step4[] = $value;
			}elseif($key == 'step5'){
				$step5[] = $value;
			}elseif($key == 'step52'){
				$step5[] = $value;
			/*}elseif(preg_match('/reporter_name/i', $key)){
				$reporter_name[] = $value;*/
			}elseif(preg_match('/before_comment/i', $key)){
				$result['interviewed_doctor'] = preg_replace($search_newline, '¶', $value);
			}else{
				$result[$key] = preg_replace($search_newline, '¶', $value);
			}
		}

		$result['catch'] = '';
		$result['q1'] = $q1[0].'¶'.$q1[1];
		$result['q2'] = $q2[0].'¶'.$q2[1];
		$result['q3'] = $q3[0].'¶'.$q3[1];
		$result['q1_honmon'] = $q1[2];
		$result['q2_honmon'] = $q2[2];
		$result['q3_honmon'] = $q3[2];

		$result['step1'] = preg_replace('/¶$/u', '', $step1[0].'¶'.$step1[1]);
		$result['step2'] = preg_replace('/¶$/u', '', $step2[0].'¶'.$step2[1]);
		$result['step3'] = preg_replace('/¶$/u', '', $step3[0].'¶'.$step3[1]);
		$result['step4'] = preg_replace('/¶$/u', '', $step4[0].'¶'.$step4[1]);
		$result['step5'] = preg_replace('/¶$/u', '', $step5[0].'¶'.$step5[1]);

		if($result['main_theme2'] != ''){
			$result['main_theme'] = $result['main_theme'].'¶'.$result['main_theme2'];
		}

		$result['reporter_name'] = '';
		$result['main_theme_yomigana'] = '';
		$result['main_theme_lead'] = '';
		$result['age'] = '';
		$result['before_comment'] = '';

		// linkからnombre(自ページ)を削除
		$array_link_list = array();
		$arr_link = explode(',', $link);
		foreach($arr_link as $ele_link){
			if($ele_link != $nombre){
				array_push($array_link_list, $ele_link);
			}
		}

		$result['link'] = implode(',', $array_link_list);
		$result['year'] = $year;

		// ページ数に応じて出力項目を変える
		if($page == '2'){
			$kikaku_page = 1;
		}else{
			$kikaku_page = 2;
		}

		// 順番を決める
		$trans = [];
		foreach($this->reportCsvHeaderSorting as $sort){
			if(!array_key_exists($sort[0], $result)) continue;
			if($sort[$kikaku_page] == false) continue;
			$trans[] = trim($result[$sort[0]]);
		}
		$body[] = $trans;

		return $body;
	}

	/**
	 * @param  array $header
	 * @return array $trans
	 */
	private function generateHeader_Topics($header_base, $header_content, $page){
		// trans service
		$translator = $this->get('translator');

		// データを一つにまとめないといけないものは別で作業する
		// それ以外は翻訳する
		$result = [];
		// 基本情報
		foreach ($header_base as $key => $value) {
			$result[$key] = $translator->trans('csv.genko.' . $key);
		}

		// 本文情報
		foreach ($header_content as $key => $value) {
			$result[$key] = $translator->trans('csv.genko.' . $key);
		}

		//TODO:台割情報　要検討
		$result['lr'] = $translator->trans('csv.genko.lr');
		$result['no'] = $translator->trans('csv.genko.topics_no');

		$result['company_name'] = $translator->trans('csv.genko.topics_company_name');
		$result['catch'] = $translator->trans('csv.genko.topics_catch');
		$result['doctor_name'] = $translator->trans('csv.genko.doctor_name');
		$result['doctor_post'] = $translator->trans('csv.genko.doctor_post');
		$result['doctor_profile'] = $translator->trans('csv.genko.topics_doctor_profile');
		$result['doctor_image'] = $translator->trans('csv.genko.doctor_image');
		$result['column_catch'] = $translator->trans('csv.genko.column_catch');
		$result['honmon'] = $translator->trans('csv.genko.topics_honmon');
		$result['column_image'] = $translator->trans('csv.genko.column_image');

		$result['link'] = $translator->trans('csv.genko.link');
		$result['han'] = $translator->trans('csv.genko.han');
		$result['year'] = $translator->trans('csv.genko.year');

		// ページ数に応じて出力項目を変える
		if($page == '1'){
			$kikaku_page = 1;
		}else{
			$kikaku_page = 2;
		}

		// 順番を決める
		$trans = [];
		foreach($this->topicsCsvHeaderSorting as $sort){
			if(!isset($result[$sort[0]])) continue;
			if($sort[$kikaku_page] == false) continue;
			$trans[] = $result[$sort[0]];
		}

		return $trans;
	}

	/**
	 * @param  array $coupons
	 * @return array $body
	 */
	private function generateBody_Topics($base, $content, $page, $classification){
		$body = [];
		$result = [];
		$doctor = [];

		$catch = [];
		$column_catch = [];

		$lr = '';
		$page = '';
		$year = '';
		$nombre = '';
		$link = '';
		$main_theme_title_add = '';

		$em = $this->getDoctrine()->getManager();
		$translator = $this->get('translator');

		$search_newline = '/\r\n|\r|\n/';

		// 基本情報
		foreach ($base as $key => $value) {
			$value = preg_replace('/\t/', '', $value); //入力されたTabを削除
			if($key == 'image1'){
				$result['doctor_image'] = $value;
			}elseif($key == 'image2'){
				$result['column_image'] = $value;
			}elseif($key == 'doctor1'){
				$doctor_info = unserialize($value);

				$result['doctor_name'] = $doctor_info['doctor_sei'] . ' ' . $doctor_info['doctor_name'];
				$result['doctor_post'] = $doctor_info['doctor_post'];

			}elseif($key == 'han_name'){
				$result['han'] = $value;
			}elseif($key == 'page'){
				$page = $value;
			}elseif($key == 'lr'){
				$lr = $value;
			}elseif($key == 'format'){
				$result['format'] = $classification;
			}elseif($key == 'image_caption2'){
				$result['caption'] = $value;
			}elseif($key == 'volume_year'){
				$year = $value;
			}elseif($key == 'nombre'){
				$nombre = $value;
			}elseif($key == 'link'){
				$link = $value;
			}else{
				$result[$key] = preg_replace($search_newline, '¶', $value);
			}
		}

		$result['lr'] = $translator->trans($this->container->getParameter('master.lr')[$lr]);
		$result['no'] = '';

		// 本文情報
		foreach ($content as $key => $value) {
			$value = preg_replace('/\t/', '', $value); //入力されたTabを削除
			if(preg_match('/^catch/i', $key)){
				$catch[] = $value;
			}elseif(preg_match('/column_catch/i', $key)){
				$column_catch[] = $value;
			}elseif($key == 'caption'){
				// 基本情報の受けられる診療キャプション(image_caption2)から取得
			}else{
				$result[$key] = preg_replace($search_newline, '¶', $value);
			}
		}

		$result['catch'] = preg_replace('/¶$/u', '', implode('¶', $catch));
		$result['column_catch'] = preg_replace('/¶+$/u', '', implode('¶', $column_catch));

		// linkからnombre(自ページ)を削除
		$array_link_list = array();
		$arr_link = explode(',', $link);
		foreach($arr_link as $ele_link){
			if($ele_link != $nombre){
				array_push($array_link_list, $ele_link);
			}
		}

		$result['link'] = implode(',', $array_link_list);
		$result['year'] = $year;

		// ページ数に応じて出力項目を変える
		if($page == '1'){
			$kikaku_page = 1;
		}else{
			$kikaku_page = 2;
		}

		// 順番を決める
		$trans = [];
		foreach($this->topicsCsvHeaderSorting as $sort){
			if(!array_key_exists($sort[0], $result)) continue;
			if($sort[$kikaku_page] == false) continue;
			$trans[] = trim($result[$sort[0]]);
		}
		$body[] = $trans;

		return $body;
	}

	/**
	 * @Route("/lookupindex/download", name="client.lookupindex.download")
	 * @Method("POST|GET")
	 */
	public function lookupindexDownloadAction(Request $request){
		$zip = new ZipArchive();
		$tmpFilePath = tempnam(sys_get_temp_dir(), 'tmp');
		// 作業ファイルをオープン
		$result = $zip->open($tmpFilePath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);

		if($result !== true) {
			$response = $this->responseForm('404', 'HTTP/1.1 404 Not Found');
			return $response;
		}

		if(!$request->query->has('han')){
			$response = $this->responseForm('404', 'HTTP/1.1 404 Not Found');
			return $response;
		}
		if(!$request->query->has('go')){
			$response = $this->responseForm('404', 'HTTP/1.1 404 Not Found');
			return $response;
		}

		$han = $request->query->get('han');
		$go = $request->query->get('go');

		$outFileName = $han . '_' . $go . ".zip";

		// 巻末IndexデータCSV生成
		$return_file = $this->constructIndexCSV($han, $go, $request);
		if($return_file === false){
			$response = $this->responseForm('204', 'HTTP/1.1 204 No Content');
			return $response;
		}

		$path = $this->container->getParameter('archive')['image'] . 'index/' . $han . '_' . $go;
		$webpath = $request->getSchemeAndHttpHost() . '/' . $this->container->getParameter('archive')['link'] . 'index/'  . $han . '_' . $go;

		// CSVファイル
		$return_obj = array();
		$return_obj = $this->collectFileData($path, $webpath, '', $zip);
		$zip = $return_obj[0];

		// ストリームを閉じる
		$zip->close();

		$response = new BinaryFileResponse($tmpFilePath);
		$response->headers->set('Content-type', 'application/octet-stream');
		$response->headers->set('Access-Control-Allow-Origin', '*');
		BinaryFileResponse::trustXSendfileTypeHeader();
		$response->setContentDisposition(
				ResponseHeaderBag::DISPOSITION_ATTACHMENT,
				$outFileName
		);

		return $response;

	}

	// 巻末IndexデータCSV生成
	private function constructIndexCSV($han, $go, $request) {
		$em = $this->getDoctrine()->getManager();

		$entity_han = $em->getRepository('GIMICCommonBundle:Han')->findOneBy(array(
				'ryakusho' => $han,
				'deleteFlag' => FALSE
		));

		if(!$entity_han){
			return false;
		}

		// Han.id 引き当て
		$han_id = $this->getHanIdFromNameAndYear($han, $go);
		if (empty($han_id)){
			return false;
		}

		$entity = $em->getRepository('GIMICCommonBundle:Genko')->getIndexCSVList($han_id, $go);

		if(!$entity){
			return false;
		}

		// header
		$header = $this->encoding($this->generateHeader_Index($entity[0]), $request);
		// body
		$body = $this->encoding($this->generateBody_Index($entity), $request);

		// CSV出力先の決定
		$csvName = $han . '_' . $go . '_index_' . date('Ymd') . '_' . date('Hi') . ".csv";
		$csvDir = $this->container->getParameter('archive')['image'] . 'index/' . $han . '_' . $go;
		$csv_file = $csvDir . '/' . $csvName;

		// ディレクトリ作成
		if (!is_dir($csvDir)) {
			// ディレクトリが存在しない場合作成
			mkdir($csvDir, 0777, True);
			// umaskを考慮して再度777に変更
			chmod($csvDir, 0777);
		}else{
			// ディレクトリが既にある場合、前回ダウンロードしたファイルを削除
			$this->clearDirectory($csvDir);
		}

		// ファイル作成
		if (!file_exists($csv_file)) {
			// ファイルが存在しない場合作成
			touch($csv_file);
			// 権限変更
			chmod($csv_file, 0666);
		}

		// CSVファイル出力
		$handle = fopen($csv_file, "w+");
		fputcsv($handle, $header, '	');
		foreach ($body as $value) {
			// 改行対応
			$value = str_replace("\n", chr(10), $value);
			//fputcsv($handle, $value, '	');
			fputs($handle, implode($value, "\t")."\n");
		}
		fclose($handle);

		return $csvName;

	}

	/**
	 * @param  array $header
	 * @return array $trans
	 */
	private function generateHeader_Index($header_base){
		// trans service
		$translator = $this->get('translator');

		// データを一つにまとめないといけないものは別で作業する
		// それ以外は翻訳する
		$result = [];
		// 基本情報
		foreach ($header_base as $key => $value) {
			$result[$key] = $translator->trans('csv.genko.' . $key);
		}

		// 診療科目などはデータを結合しないといけないので別で生成する
		$result['shinryo_kamoku_main'] = $translator->trans('csv.genko.shinryo_kamoku_main');
		$result['shinryo_kamoku'] = $translator->trans('csv.genko.shinryo_kamoku_all');
		$result['station'] = $translator->trans('csv.genko.station');
		$result['icon1'] = $translator->trans('csv.genko.icon1');
		$result['icon2'] = $translator->trans('csv.genko.icon2');
		$result['icon3'] = $translator->trans('csv.genko.icon3');
		$result['icon4'] = $translator->trans('csv.genko.icon4');
		$result['icon5'] = $translator->trans('csv.genko.icon5');
		$result['icon6'] = $translator->trans('csv.genko.icon6');
		$result['icon7'] = $translator->trans('csv.genko.icon7');
		//TODO:掲載ページ(ノンブル)　要検討
		$result['nombre'] = $translator->trans('csv.genko.nombre');

		// 順番を決める
		$trans = [];
		foreach($this->indexCsvHeaderSorting as $sort){
			if(!isset($result[$sort])) continue;
			$trans[] = $result[$sort];
		}

		return $trans;
	}

	/**
	 * @param  array $coupons
	 * @return array $body
	 */
	private function generateBody_Index($records){
		$body = [];
		$wk_hid = '';
		$wk_kikaku = '';

		foreach ($records as $record) {
			$result = [];
			$shinryo_kamoku = [];
			$shinryo_kamoku_sonota = '';
			$icon = [];
			$station = [];
			$link = '';
			$nombre = '';

			$em = $this->getDoctrine()->getManager();
			$translator = $this->get('translator');

			$search_newline = '/\r\n|\r|\n/';

			// 基本情報
			foreach ($record as $key => $value) {
				$value = preg_replace('/\t/', '', $value); //入力されたTabを削除
				if(preg_match('/shinryo_kamoku/i', $key)){
					if($value != ''){
						$shinryo_kamoku[] = $value;
					}
				}elseif(preg_match('/sonota/i', $key)){
					if($value == '1'){
						$shinryo_kamoku_sonota = 'ほか';
					}
				}elseif(preg_match('/station/i', $key)){
					if($value != ''){
						$station[] = $value;
					}
				}elseif(preg_match('/icon/i', $key)){
					$icon_info = unserialize($value);

					if(count($icon_info) == 0){
						continue;
					}

					foreach($icon_info as $icon_element){
						$icon[] = $icon_element;
					}

				}elseif($key == 'link'){
					$link = str_replace(',', '¶', $value);
				}elseif($key == 'nombre'){
					$nombre = str_pad($value, 3, 0, STR_PAD_LEFT);

				}else{
					$result[$key] = preg_replace($search_newline, '¶', $value);
				}
			}

			if(empty($shinryo_kamoku)){
				$result['shinryo_kamoku_main'] = '';
			}else{
				$result['shinryo_kamoku_main'] = $shinryo_kamoku[0];
			}
			$result['shinryo_kamoku'] = implode('、', $shinryo_kamoku) . $shinryo_kamoku_sonota;
			$result['station'] = preg_replace('/¶+$/u', '', implode('¶', $station));

			for($idx=1;$idx<=7;$idx++){
				if(in_array('text.genko.icon'.$idx, $icon)){
					$result['icon' . $idx] = '['. $idx .']';
				}else{
					$result['icon' . $idx] = '';
				}
			}

			if($link == ''){
				$result['nombre'] = $nombre;
			}else{
				$result['nombre'] = $link;
			}

			// 順番を決める
			$trans = [];
			foreach($this->indexCsvHeaderSorting as $sort){
				if(!array_key_exists($sort, $result)) continue;
				$trans[] = trim($result[$sort]);
			}

			// HID単位に原稿データを集約
			if($wk_hid == ''){
				$wk_hid = $result['hid'];
				$wk_kikaku = $result['kikaku'];
				$wk_record = $trans;
				$wk_record['sort'] = $result['sort'];
			}

			if($wk_hid != $result['hid']){
				$wk_hid = $result['hid'];

				$body[] = $wk_record;

				$wk_kikaku = '';
			}

			// 企画が"エリア紹介"、"特集"の情報を優先して統合。
			if(($wk_kikaku == '')||($result['kikaku'] == '1')||($result['kikaku'] == '2')){
				$wk_hid = $result['hid'];
				$wk_kikaku = $result['kikaku'];

				$wk_record = $trans;
				$wk_record['sort'] = $result['sort'];
			}
		}

		$body[] = $wk_record;

		usort($body, array($this, 'cmp_obj_index'));

		$rtn_body = [];
		$rtn_rec = [];
		foreach ($body as $trans){
			$rtn_rec = array_splice($trans, 15, 1);
			array_push($rtn_body, $trans);
		}

		return $rtn_body;
	}

	/**
	 * @param unknown $before
	 * @param unknown $after
	 * @return number
	 */
	private function cmp_obj_index($before, $after){
		// メイン診療科目
		$beforeTarget = $before['sort'];
		$afterTarget = $after['sort'];

		if($beforeTarget == $afterTarget){
			// ノンブル
			$beforeTarget2 = $before[14];
			$afterTarget2 = $after[14];

			return ($beforeTarget2 < $afterTarget2) ? -1 : +1;
		}
		return ($beforeTarget < $afterTarget) ? -1 : +1;
	}

	/**
	 * @Route("/daiwari/download", name="client.daiwari.download")
	 * @Method("POST|GET")
	 */
	public function daiwariDownloadAction(Request $request){
		$zip = new ZipArchive();
		$tmpFilePath = tempnam(sys_get_temp_dir(), 'tmp');
		// 作業ファイルをオープン
		$result = $zip->open($tmpFilePath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);

		if($result !== true) {
			$response = $this->responseForm('404', 'HTTP/1.1 404 Not Found');
			return $response;
		}

		if(!$request->query->has('han')){
			$response = $this->responseForm('404', 'HTTP/1.1 404 Not Found');
			return $response;
		}
		if(!$request->query->has('go')){
			$response = $this->responseForm('404', 'HTTP/1.1 404 Not Found');
			return $response;
		}

		$han = $request->query->get('han');
		$go = $request->query->get('go');

		$outFileName = $han . '_' . $go . ".zip";

		// 台割データCSV生成
		$return_file = $this->constructDaiwariCSV($han, $go, $request);
		if($return_file === false){
			$response = $this->responseForm('204', 'HTTP/1.1 204 No Content');
			return $response;
		}

		$path = $this->container->getParameter('archive')['image'] . 'daiwari/' . $han . '_' . $go;
		$webpath = $request->getSchemeAndHttpHost() . '/' . $this->container->getParameter('archive')['link'] . 'daiwari/'  . $han . '_' . $go;

		// CSVファイル
		$return_obj = array();
		$return_obj = $this->collectFileData($path, $webpath, '', $zip);
		$zip = $return_obj[0];

		// ストリームを閉じる
		$zip->close();

		$response = new BinaryFileResponse($tmpFilePath);
		$response->headers->set('Content-type', 'application/octet-stream');
		$response->headers->set('Access-Control-Allow-Origin', '*');
		BinaryFileResponse::trustXSendfileTypeHeader();
		$response->setContentDisposition(
				ResponseHeaderBag::DISPOSITION_ATTACHMENT,
				$outFileName
		);

		return $response;

	}

	// 台割データCSV生成
	private function constructDaiwariCSV($han, $go, $request) {
		$em = $this->getDoctrine()->getManager();

		$entity_han = $em->getRepository('GIMICCommonBundle:Han')->findOneBy(array(
				'ryakusho' => $han,
				'deleteFlag' => FALSE
		));

		if(!$entity_han){
			return false;
		}

		// Han.id 引き当て
		$han_id = $this->getHanIdFromNameAndYear($han, $go);
		if (empty($han_id)){
			return false;
		}

		$entity = $em->getRepository('GIMICCommonBundle:Genko')->getDaiwariCSVList($han_id, $go);

		if(!$entity){
			return false;
		}

		// header
		$header = $this->encoding($this->generateHeader_Daiwari($entity[0]), $request);
		// body
		$body = $this->encoding($this->generateBody_Daiwari($entity), $request);

		// CSV出力先の決定
		$csvName = $han . '_' . $go . '_daiwari_' . date('Ymd') . '_' . date('Hi') . ".csv";
		$csvDir = $this->container->getParameter('archive')['image'] . 'daiwari/' . $han . '_' . $go;
		$csv_file = $csvDir . '/' . $csvName;

		// ディレクトリ作成
		if (!is_dir($csvDir)) {
			// ディレクトリが存在しない場合作成
			mkdir($csvDir, 0777, True);
			// umaskを考慮して再度777に変更
			chmod($csvDir, 0777);
		}else{
			// ディレクトリが既にある場合、前回ダウンロードしたファイルを削除
			$this->clearDirectory($csvDir);
		}

		// ファイル作成
		if (!file_exists($csv_file)) {
			// ファイルが存在しない場合作成
			touch($csv_file);
			// 権限変更
			chmod($csv_file, 0666);
		}

		// CSVファイル出力
		$handle = fopen($csv_file, "w+");
		fputcsv($handle, $header, '	');
		foreach ($body as $value) {
			// 改行対応
			$value = str_replace("\n", chr(10), $value);
			//fputcsv($handle, $value, '	');
			fputs($handle, implode($value, "\t")."\n");
		}
		fclose($handle);

		return $csvName;

	}

	/**
	 * @param  array $header
	 * @return array $trans
	 */
	private function generateHeader_Daiwari($header_base){
		// trans service
		$translator = $this->get('translator');

		// データを一つにまとめないといけないものは別で作業する
		// それ以外は翻訳する
		$result = [];
		// 基本情報
		foreach ($header_base as $key => $value) {
			$result[$key] = $translator->trans('csv.genko.' . $key);
		}

		//TODO:掲載ページ(ノンブル)　要検討
		$result['tobira'] = $translator->trans('csv.genko.tobira');
		$result['kiji_shubetsu'] = $translator->trans('csv.genko.kiji_shubetsu');
		$result['clinic_name'] = $translator->trans('csv.genko.clinic_name_daiwari');
		$result['start_page'] = $translator->trans('csv.genko.start_page');
		$result['master_page'] = $translator->trans('csv.genko.master_page');
		$result['map'] = $translator->trans('csv.genko.map');
		$result['coordinates'] = $translator->trans('csv.genko.coordinates');
		$result['link'] = $translator->trans('csv.genko.link');

		// 順番を決める
		$trans = [];
		foreach($this->daiwariCsvHeaderSorting as $sort){
			if(!isset($result[$sort])) continue;
			$trans[] = $result[$sort];
		}

		return $trans;
	}

	/**
	 * @param  array $coupons
	 * @return array $body
	 */
	private function generateBody_Daiwari($records){
		$body = [];
		$topics_half_nombre_cnt = array();
		$link = '';

		foreach ($records as $record) {
			$result = [];

			$em = $this->getDoctrine()->getManager();
			$translator = $this->get('translator');

			$search_newline = '/\r\n|\r|\n/';

			$start_page = '';
			$start_page_wk = '';
			$kikaku = '';
			// 基本情報
			foreach ($record as $key => $value) {
				$value = preg_replace('/\t/', '', $value); //入力されたTabを削除
				if($key == 'kikaku'){
					$entity_plan = $em->getRepository('GIMICCommonBundle:Plan')->findOneBy(array(
							'id' => $value,
							'deleteFlag' => FALSE
					));

					if($entity_plan){
						$kikaku = $entity_plan->getName();
					}
					$kikaku_id = $value;
				}elseif($key == 'kikaku_shousai'){
					$entity_detail = $em->getRepository('GIMICCommonBundle:Plandetail')->findOneBy(array(
							'id' => $value,
							'deleteFlag' => FALSE
					));

					$kikaku_shousai = "";
					if($entity_detail){
						$kikaku_shousai = $entity_detail->getName();
					}
					$kikaku_shousai_id = $value;
				}elseif($key == 'start_page'){
					$start_page = $value;
					$result[$key] = preg_replace($search_newline, '¶', $value);
				}elseif($key == 'page'){
					$page_id = $value;
					$result[$key] = preg_replace($search_newline, '¶', $value);
				}elseif($key == 'link'){
					$link = $value;
				}else{
					$result[$key] = preg_replace($search_newline, '¶', $value);
				}

				if(!array_key_exists($start_page, $topics_half_nombre_cnt)){
					$topics_half_nombre_cnt[$start_page] = 0;
				}
			}

			// トピックス、0.5Pが1ページに1原稿のみの場合、"扉"設定
			if(($this->container->getParameter('master.kikaku')[$kikaku_id] == 'kikaku.topics')&&($page_id == '0.5')){
				$topics_half_nombre_cnt[$start_page] += 1;
			}
			$result['tobira'] = "";

			if($this->container->getParameter('master.kikaku')[$kikaku_id] == 'kikaku.area'){
				$result['kiji_shubetsu'] = 'エリア';
			}else{
				$result['kiji_shubetsu'] = $kikaku;
			}

			//$result['start_page'] = "7";

			if((intval($kikaku_shousai_id) > 0)&&(intval($kikaku_shousai_id) < 8)){
				//企画詳細が"エリア1"～"エリア7"の場合
				$result['master_page'] = $kikaku_shousai_id . '-area';
			}else{
				$result['master_page'] = '';
			}

			// linkからnombre(自ページ)を削除
			$array_link_list = array();
			$arr_link = explode(',', $link);
			foreach($arr_link as $ele_link){
				if($ele_link != $start_page){
					array_push($array_link_list, $ele_link);
				}
			}

			//$result['map'] = "";
			//$result['coordinates'] = "";
			$result['link'] = implode(',', $array_link_list);


			// 順番を決める
			$trans = [];
			foreach($this->daiwariCsvHeaderSorting as $sort){
				if(!array_key_exists($sort, $result)) continue;
				$trans[] = trim($result[$sort]);
			}
			$body[] = $trans;
		}

		// トピックス、0.5Pが1ページに1原稿のみの場合、"扉"設定
		$body_add_tobira = array();
		foreach ($body as $record){
			if($topics_half_nombre_cnt[$record[4]] == '1'){
				$record[1] = '扉';
			}
			array_push($body_add_tobira, $record);
		}

		return $body_add_tobira;
	}

	/**
	 * @Route("/map/download", name="client.map.download")
	 * @Method("POST|GET")
	 */
	public function mapDownloadAction(Request $request){
		$tmpFilePath = tempnam(sys_get_temp_dir(), 'tmp');

		if(!$request->query->has('han')){
			$response = $this->responseForm('404', 'HTTP/1.1 404 Not Found');
			return $this->redirect($this->generateUrl('client.csv.export', array('status' => 'default')));
		}
		if(!$request->query->has('go')){
			$response = $this->responseForm('404', 'HTTP/1.1 404 Not Found');
			return $this->redirect($this->generateUrl('client.csv.export', array('status' => 'default')));
		}

		$han = $request->query->get('han');
		$go = $request->query->get('go');

		// 巻末mapデータCSV生成
		$return_file = $this->constructMapCSV($han, $go, $request);
		if($return_file === false){
			$response = $this->responseForm('204', 'HTTP/1.1 204 No Content');
			return $this->redirect($this->generateUrl('client.csv.export', array('status' => 'default')));
		}

		$path = $this->container->getParameter('archive')['image'] . 'map/' . $han . '_' . $go;
		$webpath = $request->getSchemeAndHttpHost() . '/' . $this->container->getParameter('archive')['link'] . 'map/'  . $han . '_' . $go;

		$response = new Response();
		$response->setStatusCode(200);
		$response->headers->set('Content-Encoding', 'Shift-JIS');
		$response->headers->set('Content-Type', 'text/csv; charset=Shift-JIS');
		$response->headers->set('Content-Transfer-Encoding', 'binary');
		$response->headers->set('Content-Type', 'application/force-download');
		$response->headers->set('Content-Type', 'application/octet-stream');
		$response->headers->set('Content-Disposition', 'attachment; filename='.$return_file);

		//$ua = $request->server->get('HTTP_USER_AGENT');
		// MACでダウンロードする場合はUTF-16リトルエンディアン＋BOM追加にする
		/*if(preg_match("/Mac/i", $ua) || preg_match("/mac/i", $ua)){
			$encoded = chr(255) . chr(254). mb_convert_encoding(file_get_contents($path . '/' . $return_file), 'UTF-16LE', 'UTF-8');
			file_put_contents($path . '/' . $return_file, $encoded);
		}*/
		$response->setContent(file_get_contents($path . '/' . $return_file));

		return $response;

	}

	// 巻末mapデータCSV生成
	private function constructMapCSV($han, $go, $request) {
		$em = $this->getDoctrine()->getManager();

		$entity_han = $em->getRepository('GIMICCommonBundle:Han')->findOneBy(array(
				'ryakusho' => $han,
				'deleteFlag' => FALSE
		));

		if(!$entity_han){
			return false;
		}

		// Han.id 引き当て
		$han_id = $this->getHanIdFromNameAndYear($han, $go);
		if (empty($han_id)){
			return false;
		}

		$entity = $em->getRepository('GIMICCommonBundle:Genko')->getMapCSVList($han_id);

		if(!$entity){
			return false;
		}

		// header
		$header = $this->encoding($this->generateHeader_Map($entity[0]), $request, true);
		// body
		$body = $this->encoding($this->generateBody_Map($entity), $request, true);

		// CSV出力先の決定
		$csvName = $han . '_' . $go . '_' . date('Ymd') . '_' . date('Hi') . ".csv";
		$csvDir = $this->container->getParameter('archive')['image'] . 'map/' . $han . '_' . $go;
		$csv_file = $csvDir . '/' . $csvName;

		// ディレクトリ作成
		if (!is_dir($csvDir)) {
			// ディレクトリが存在しない場合作成
			mkdir($csvDir, 0777, True);
			// umaskを考慮して再度777に変更
			chmod($csvDir, 0777);
		}else{
			// ディレクトリが既にある場合、前回ダウンロードしたファイルを削除
			$this->clearDirectory($csvDir);
		}

		// ファイル作成
		if (!file_exists($csv_file)) {
			// ファイルが存在しない場合作成
			touch($csv_file);
			// 権限変更
			chmod($csv_file, 0666);
		}

		// CSVファイル出力
		$handle = fopen($csv_file, "w+");
		fputcsv($handle, $header, ',');
		foreach ($body as $value) {
			// 改行対応
			$value = str_replace("\n", chr(10), $value);
			fputcsv($handle, $value, ',');
		}
		fclose($handle);

		return $csvName;

	}

	/**
	 * @param  array $header
	 * @return array $trans
	 */
	private function generateHeader_Map($header_base){
		// trans service
		$translator = $this->get('translator');

		// データを一つにまとめないといけないものは別で作業する
		// それ以外は翻訳する
		$result = [];
		// 基本情報
		foreach ($header_base as $key => $value) {
			$result[$key] = $translator->trans('csv.genko.' . $key);
		}

		$result['adress'] = $translator->trans('csv.genko.adress');
		$result['shinryo_kamoku'] = $translator->trans('csv.genko.shinryo_kamoku');

		//TODO:掲載ページ(ノンブル)　要検討
		$result['fixed_nombre'] = $translator->trans('csv.genko.fixed_nombre');
		$result['link'] = $translator->trans('csv.genko.link_info');
		$result['nombre'] = $translator->trans('csv.genko.manu_nombre');
		$result['plot'] = $translator->trans('csv.genko.plot');

		// 順番を決める
		$trans = [];
		foreach($this->mapCsvHeaderSorting as $sort){
			if(!isset($result[$sort])) continue;
			$trans[] = $result[$sort];
		}

		return $trans;
	}

	/**
	 * @param  array $coupons
	 * @return array $body
	 */
	private function generateBody_Map($records){
		$body = [];
		$wk_hid = '';
		$wk_record = [];
		$wk_genko_id = [];
		$wk_kikaku = [];
		$wk_kikaku_shousai = [];
		$wk_nombre = '';
		$wk_link = '';
		$wk_shinryo_kamoku = '';
		$wk_adress = '';
		$wk_kikaku_id = '';
		$wk_kikaku_shousai_id = '';

		foreach ($records as $record) {
			$result = [];
			$nombre = [];
			$adress = [];
			$telno = [];

			$em = $this->getDoctrine()->getManager();
			$translator = $this->get('translator');

			$search_newline = '/\r\n|\r|\n/';

			// 基本情報
			foreach ($record as $key => $value) {
				if(preg_match('/adress/i', $key)){
					if($value != ''){
						$adress[] = $value;
					}
				}elseif(preg_match('/telno/i', $key)){
					if($value != ''){
						$telno[] = $value;
					}
				}elseif($key == 'nombre'){
					$nombre[] = str_pad($value, 3, 0, STR_PAD_LEFT);
				}elseif($key == 'link'){
					$result['link'] = str_replace(',', '／', $value);
				}else{
					$result[$key] = preg_replace($search_newline, '／', $value);
				}
			}

			$result['adress'] = preg_replace('/／+$/u', '', implode('／', $adress));
			$result['telno'] = preg_replace('/／$/u', '', implode('／', $telno));
			$result['fixed_nombre'] = preg_replace('/／$/u', '', implode('／', $nombre));

			$result['nombre'] = '';
			$result['plot'] = '';

			// 順番を決める
			$trans = [];
			foreach($this->mapCsvHeaderSorting as $sort){
				if(!array_key_exists($sort, $result)) continue;
				$trans[] = trim($result[$sort]);
			}

			// HID単位に原稿データを集約
			if($wk_hid == ''){
				$wk_hid = $result['hid'];
				$wk_record = $trans;
			}

			if($wk_hid != $result['hid']){
				$wk_hid = $result['hid'];

				// HID単位にデリミタで区切る
				$wk_record[0] = preg_replace('/／+$/u', '', implode('／', $wk_genko_id));
				$wk_record[1] = preg_replace('/／+$/u', '', implode('／', $wk_kikaku));
				$wk_record[2] = preg_replace('/／+$/u', '', implode('／', $wk_kikaku_shousai));
				$wk_record[7] = $wk_nombre;

				// リンク情報から確定後ノンブルを削除
				$link_except_nombre = '';
				$arr_link = explode('／', $wk_record[8]);

				foreach($arr_link as $el_link){
					if($el_link != $wk_nombre){
						$link_except_nombre = $link_except_nombre . '／' . $el_link;
					}
				}
				$wk_record[8] = ltrim($link_except_nombre,'／');

				$wk_record[6] = $wk_shinryo_kamoku;
				$wk_record[4] = $wk_adress;

				$wk_record['kikaku_id'] = $wk_kikaku_id;
				if(($wk_kikaku_id == '1')||($wk_kikaku_id == '2')){
					$wk_record['kikaku_shousai_id'] = $wk_kikaku_shousai_id;
				}else{
					$wk_record['kikaku_shousai_id'] = "";
				}

				$body[] = $wk_record;

				$wk_genko_id = [];
				$wk_kikaku = [];
				$wk_kikaku_shousai = [];
				$wk_nombre = '';
				$wk_shinryo_kamoku = '';
				$wk_adress = '';
				$wk_kikaku_id = '';
				$wk_kikaku_shousai_id = '';
			}

			$wk_genko_id[] = $result['genko_id'];
			$wk_kikaku[] = $result['kikaku'];
			$wk_kikaku_shousai[] = $result['kikaku_shousai'];
			if($wk_nombre == ''){$wk_nombre = $result['fixed_nombre'];}

			if($result['shinryo_kamoku'] != ''){$wk_shinryo_kamoku = $result['shinryo_kamoku'];}
			if($result['adress'] != ''){$wk_adress = $result['adress'];}

			// 企画、企画詳細、ノンブルの順にソート
			if(($wk_kikaku_id == '')||($wk_kikaku_id > $result['kikaku_id'])){
				$wk_kikaku_id = $result['kikaku_id'];
			}
			if(($wk_kikaku_shousai_id == '')||($wk_kikaku_shousai_id > $result['kikaku_shousai_id'])){
				if($result['kikaku_shousai_id'] != ''){
					$wk_kikaku_shousai_id = $result['kikaku_shousai_id'];
				}
			}

			$wk_record = $trans;
		}

		// HID単位にデリミタで区切る
		$wk_record[0] = preg_replace('/／+$/u', '', implode('／', $wk_genko_id));
		$wk_record[1] = preg_replace('/／+$/u', '', implode('／', $wk_kikaku));
		$wk_record[2] = preg_replace('/／+$/u', '', implode('／', $wk_kikaku_shousai));
		$wk_record[7] = $wk_nombre;

		// リンク情報から確定後ノンブルを削除
		$link_except_nombre = '';
		$arr_link = explode('／', $wk_record[8]);

		foreach($arr_link as $el_link){
			if($el_link != $wk_nombre){
				$link_except_nombre = $link_except_nombre . '／' . $el_link;
			}
		}
		$wk_record[8] = ltrim($link_except_nombre,'／');

		$wk_record[6] = $wk_shinryo_kamoku;
		$wk_record[4] = $wk_adress;

		$wk_record['kikaku_id'] = $wk_kikaku_id;
		if(($wk_kikaku_id == '1')||($wk_kikaku_id == '2')){
			$wk_record['kikaku_shousai_id'] = $wk_kikaku_shousai_id;
		}else{
			$wk_record['kikaku_shousai_id'] = "";
		}

		$body[] = $wk_record;

		usort($body, array($this, 'cmp_obj'));

		/*foreach ($body as  $trans){
		 print("企画ID:");
		 print($trans['kikaku_id']);
		 print("企画詳細ID:");
		 print($trans['kikaku_shousai_id']);
		 print("ノンブル:");
		 print($trans[7]);
		 print("●<br>");
		 }
		 exit();*/

		$rtn_body = [];
		$rtn_rec = [];
		foreach ($body as $trans){
			$rtn_rec = array_splice($trans, 11, 2);
			array_push($rtn_body, $trans);
		}

		return $rtn_body;
	}

	/**
	 * @param unknown $before
	 * @param unknown $after
	 * @return number
	 */
	private function cmp_obj($before, $after){
		// 企画ID
		$beforeTarget = $before['kikaku_id'];
		$afterTarget = $after['kikaku_id'];

		if($beforeTarget == $afterTarget){
			// 企画詳細ID
			$beforeTarget2 = $before['kikaku_shousai_id'];
			$afterTarget2 = $after['kikaku_shousai_id'];

			if($beforeTarget2 == $afterTarget2){
				// ノンブル
				return ($before[7] < $after[7]) ? -1 : +1;
			}
			return ($beforeTarget2 < $afterTarget2) ? -1 : +1;
		}
		return ($beforeTarget < $afterTarget) ? -1 : +1;
	}

	/**
	 * @Route("/bill/download", name="client.bill.download")
	 * @Method("POST|GET")
	 */
	public function billDownloadAction(Request $request){
		$tmpFilePath = tempnam(sys_get_temp_dir(), 'tmp');

		if((!$request->query->has('start_year'))||(!$request->query->has('start_month'))||(!$request->query->has('start_day'))||
			(!$request->query->has('end_year'))||(!$request->query->has('end_month'))||(!$request->query->has('end_day'))){
			$response = $this->responseForm('404', 'HTTP/1.1 404 Not Found');
			return $this->redirect($this->generateUrl('client.csv.export', array('status' => 'default')));
		}

		$start_date = $request->query->get('start_year') . '-'  . $request->query->get('start_month') . '-' . $request->query->get('start_day');
		$end_date = $request->query->get('end_year') . '-'  . $request->query->get('end_month') . '-' . $request->query->get('end_day');

		// 請求データCSV生成
		$return_file = $this->constructBillCSV($start_date, $end_date, $request);
		if($return_file === false){
			$response = $this->responseForm('204', 'HTTP/1.1 204 No Content');
			return $this->redirect($this->generateUrl('client.csv.export', array('status' => 'default')));
		}

		$path = $this->container->getParameter('archive')['image'] . 'bill';
		$webpath = $request->getSchemeAndHttpHost() . '/' . $this->container->getParameter('archive')['link'] . 'bill' ;

		$response = new Response();
		$response->setStatusCode(200);
		$response->headers->set('Content-Encoding', 'UTF-8');
		$response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
		$response->headers->set('Content-Transfer-Encoding', 'binary');
		$response->headers->set('Content-Type', 'application/force-download');
		$response->headers->set('Content-Type', 'application/octet-stream');
		$response->headers->set('Content-Disposition', 'attachment; filename='.$return_file);
		$response->setContent(file_get_contents($path . '/' . $return_file));

		return $response;

	}

	// 請求データCSV生成
	private function constructBillCSV($start_date, $end_date, $request) {
		$em = $this->getDoctrine()->getManager();

		$entity = $em->getRepository('GIMICCommonBundle:Genko')->getBillCSVList($start_date, $end_date);

		if(!$entity){
			return false;
		}

		// header
		$header = $this->generateHeader_Bill($entity[0]);
		// body
		$body = $this->generateBody_Bill($entity);

		// CSV出力先の決定
		$csvName = $start_date . '_' . $end_date . "_請求.csv";
		$csvDir = $this->container->getParameter('archive')['image'] . 'bill';
		$csv_file = $csvDir . '/' . $csvName;

		// ディレクトリ作成
		if (!is_dir($csvDir)) {
			// ディレクトリが存在しない場合作成
			mkdir($csvDir, 0777, True);
			// umaskを考慮して再度777に変更
			chmod($csvDir, 0777);
		}else{
			// ディレクトリが既にある場合、前回ダウンロードしたファイルを削除
			$this->clearDirectory($csvDir);
		}

		// ファイル作成
		if (!file_exists($csv_file)) {
			// ファイルが存在しない場合作成
			touch($csv_file);
			// 権限変更
			chmod($csv_file, 0666);
		}

		// CSVファイル出力
		$handle = fopen($csv_file, "w+");
		fputcsv($handle, $header, ',');
		foreach ($body as $value) {
			// 改行対応
			$value = str_replace("\n", chr(10), $value);
			fputcsv($handle, $value, ',');
		}
		fclose($handle);

		return $csvName;

	}

	/**
	 * @param  array $header
	 * @return array $trans
	 */
	private function generateHeader_Bill($header_base){
		// trans service
		$translator = $this->get('translator');

		$result = [];
		// 基本情報
		foreach ($header_base as $key => $value) {
			$result[$key] = $translator->trans('csv.genko.' . $key);
		}

		// 順番を決める
		$trans = [];
		foreach($this->billCsvHeaderSorting as $sort){
			if(!isset($result[$sort])) continue;
			$trans[] = $result[$sort];
		}

		return $trans;
	}

	/**
	 * @param  array $coupons
	 * @return array $body
	 */
	private function generateBody_Bill($records){
		$body = [];

		foreach ($records as $record) {
			$result = [];

			$em = $this->getDoctrine()->getManager();
			$translator = $this->get('translator');

			$search_newline = '/\r\n|\r|\n/';

			// 基本情報
			foreach ($record as $key => $value) {
				$result[$key] = preg_replace($search_newline, '／', $value);
			}

			// 順番を決める
			$trans = [];
			foreach($this->billCsvHeaderSorting as $sort){
				if(!array_key_exists($sort, $result)) continue;
				$trans[] = trim($result[$sort]);
			}
			$body[] = $trans;
		}

		return $body;
	}

	/**
	 * @Route("/daiwari/board/download", name="client.daiwari.board.download")
	 * @Method("POST|GET")
	 */
	public function daiwariBoardDownloadAction(Request $request){
		$zip = new ZipArchive();
		$tmpFilePath = tempnam(sys_get_temp_dir(), 'tmp');
		// 作業ファイルをオープン
		$result = $zip->open($tmpFilePath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);

		if($result !== true) {
			$response = $this->responseForm('404', 'HTTP/1.1 404 Not Found');
			return $response;
		}

		if(!$request->query->has('han')){
			$response = $this->responseForm('404', 'HTTP/1.1 404 Not Found');
			return $response;
		}
		if(!$request->query->has('go')){
			$response = $this->responseForm('404', 'HTTP/1.1 404 Not Found');
			return $response;
		}

		$han = $request->query->get('han');
		$go = $request->query->get('go');

		$outFileName = $han . '_' . $go . "_board.zip";

		// 台割ボードデータCSV生成
		$return_file = $this->constructDaiwariBoardCSV($han, $go, $request);
		if($return_file === false){
			$response = $this->responseForm('204', 'HTTP/1.1 204 No Content');
			return $this->redirect($this->generateUrl('client.csv.export', array('status' => 'default')));
		}

		$path = $this->container->getParameter('archive')['image'] . 'daiwari_board/' . $han . '_' . $go;
		$webpath = $request->getSchemeAndHttpHost() . '/' . $this->container->getParameter('archive')['link'] . 'daiwari_board/'  . $han . '_' . $go;

		$response = new Response();
		$response->setStatusCode(200);
		$response->headers->set('Content-Encoding', 'Shift-JIS');
		$response->headers->set('Content-Type', 'text/csv; charset=Shift-JIS');
		$response->headers->set('Content-Transfer-Encoding', 'binary');
		$response->headers->set('Content-Type', 'application/force-download');
		$response->headers->set('Content-Type', 'application/octet-stream');
		$response->headers->set('Content-Disposition', 'attachment; filename='.$return_file);
		$response->setContent(file_get_contents($path . '/' . $return_file));

		return $response;

	}

	// 台割ボードデータCSV生成
	private function constructDaiwariBoardCSV($ryakusho, $go, $request) {
		$em = $this->getDoctrine()->getManager();

		$entity_han = $em->getRepository('GIMICCommonBundle:Han')->findOneBy(array(
				'ryakusho' => $ryakusho,
				'deleteFlag' => FALSE
		));

		if(!$entity_han){
			return false;
		}

		// Han.id 引き当て
		$han = $this->getHanIdFromNameAndYear($ryakusho, $go);
		if (empty($han)){
			return false;
		}

		$articles = $em->getRepository('GIMICCommonBundle:Daiwari')->getDaiwariBoardArticles($han);
		$fpmeta = $em->getRepository('GIMICCommonBundle:FlatplanMeta')->getMeta($han);

		if (empty($fpmeta)){
			$fillers = $em->getRepository('GIMICCommonBundle:Filler')->setInitialFillers($han);
			$articleFillers = $em->getRepository('GIMICCommonBundle:Daiwari')->getDaiwariBoardArticlesOnlyFillers($han);
			$articles = array_merge($articleFillers, $articles);
			//初期状態メタ情報作成
			$meta = '{}';
			$fpmeta = $em->getRepository('GIMICCommonBundle:FlatplanMeta')->setMeta($han, $meta);
		}

		$articlesFirst = array();
		foreach($articles as $elarticles){
			//var_dump($elarticles);
			$articlesFirst = $elarticles;
			break;
		}

		// header
		$header = $this->encoding($this->generateHeader_DaiwariBoard($articlesFirst), $request, true);
		// body
		$body = $this->encoding($this->generateBody_DaiwariBoard($articles, $fpmeta['metadata']), $request, true);

		// CSV出力先の決定
		$csvName = $ryakusho . '_' . $go . '_daiwari_board_' . date('Ymd') . '_' . date('Hi') . ".csv";
		$csvDir = $this->container->getParameter('archive')['image'] . 'daiwari_board/' . $ryakusho . '_' . $go;
		$csv_file = $csvDir . '/' . $csvName;

		// ディレクトリ作成
		if (!is_dir($csvDir)) {
			// ディレクトリが存在しない場合作成
			mkdir($csvDir, 0777, True);
			// umaskを考慮して再度777に変更
			chmod($csvDir, 0777);
		}else{
			// ディレクトリが既にある場合、前回ダウンロードしたファイルを削除
			$this->clearDirectory($csvDir);
		}

		// ファイル作成
		if (!file_exists($csv_file)) {
			// ファイルが存在しない場合作成
			touch($csv_file);
			// 権限変更
			chmod($csv_file, 0666);
		}

		// CSVファイル出力
		$handle = fopen($csv_file, "w+");
		fputcsv($handle, $header, ',');
		foreach ($body as $value) {
			// 改行対応
			$value = str_replace("\n", chr(10), $value);
			fputcsv($handle, $value, ',');
		}
		fclose($handle);

		return $csvName;

	}

	/**
	 * @param  array $header
	 * @return array $trans
	 */
	private function generateHeader_DaiwariBoard($header_base){
		// trans service
		$translator = $this->get('translator');

		// データを一つにまとめないといけないものは別で作業する
		// それ以外は翻訳する
		$result = [];
		// 基本情報
		foreach ($header_base as $key => $value) {
			$result[$key] = $translator->trans('csv.genko.' . $key);
		}

		$result['link'] = $translator->trans('csv.genko.link');

		// 順番を決める
		$trans = [];
		foreach($this->daiwariBoardCsvHeaderSorting as $sort){
			if(!isset($result[$sort])) continue;
			$trans[] = $result[$sort];
		}

		return $trans;
	}

	/**
	 * @param  array $coupons
	 * @return array $body
	 */
	private function generateBody_DaiwariBoard($records, $fpmeta_records){
		$body = [];
		$link = '';

		$daiwari_cnt = 0;
		$nombre_cnt = 0;
		$lr_prev = '';

		$meta_data = json_decode($fpmeta_records);
		foreach ($meta_data as $key => $value) {
		//foreach ($records as $record) {
			$record = $records[$key];
			$result = [];

			$em = $this->getDoctrine()->getManager();
			$translator = $this->get('translator');

			$search_newline = '/\r\n|\r|\n/';

			foreach ($record as $key => $value) {
				$result[$key] = preg_replace($search_newline, '¶', $value);

				if($key == 'page'){
					$page = $value;
				}
				if($key == 'lr'){
					if($daiwari_cnt == 0){
						if($value == '0'){
							//左
							$nombre_cnt = $nombre_cnt + 1;
						}else{
							//右
						}
					}else{
						if($page != '0.5'){
							$nombre_cnt = $nombre_cnt + 1;
						}

						if($value == '0'){
							//左(奇数)
							if(($nombre_cnt % 2) == 0){
								$nombre_cnt = $nombre_cnt + 1;
							}
						}elseif($value == '1'){
							//右(偶数)
							if(($nombre_cnt % 2) == 1){
								$nombre_cnt = $nombre_cnt + 1;
							}
						}
					}

					$lr_prev = $value;
					$daiwari_cnt++;
				}
			}

			for ($i = 0; $i < $page; $i++) {
				if($i > 0){
					if($lr_prev == ''){
						$lr_prev = '';
					}elseif($lr_prev == '0'){
						$lr_prev = '1';
					}else{
						$lr_prev = '0';
					}

					$result['format'] = '-';
					$result['page'] = '-';
					$result['link'] = '-';
					$result['hid'] = '-';
					$result['genko_id'] = '-';
					$nombre_cnt++;
				}else{
					$list_link = '';
					$arr_link = explode(',', $result['link']);
					foreach ($arr_link as $el_link){
						if($el_link != $nombre_cnt){
							$list_link = $list_link . ',' . $el_link;
						}
					}
					$result['link'] = ltrim($list_link, ',');
				}

				if($lr_prev == ''){
					$result['lr'] = '';
				}else{
					$result['lr'] = $translator->trans($this->container->getParameter('master.lr')[$lr_prev]);
				}

				$result['nombre'] = $nombre_cnt;

				// 順番を決める
				$trans = [];
				foreach($this->daiwariBoardCsvHeaderSorting as $sort){
					if(!array_key_exists($sort, $result)) continue;
					$trans[] = trim($result[$sort]);
				}
				$body[] = $trans;
			}

		}

		return $body;
	}

	private function getHanIdFromNameAndYear($han_name, $year){
		$query = 'SELECT * FROM Han WHERE ryakusho = :ryakusho AND volume_year = :year AND delete_flag = FALSE';
		$stmt = $this->getDoctrine()->getManager()->getConnection()->prepare($query);
		$stmt->execute(array(
				'ryakusho'	=> $han_name,
				'year'	=> 1*$year,
		));
		$han = $stmt->fetch();
		return empty($han)?false:$han['id'];
	}

}