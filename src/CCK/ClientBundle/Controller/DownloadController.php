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
			'index_original_kana',
			'index_original',
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

		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository('CCKCommonBundle:MainTerm')->getMainTermList('','','','','0');
		$header = $this->generateHeader($entity[0]);


		return array(
				'currentUser' => ['user_id' => $this->getUser()->getUserId(), 'name' => $this->getUser()->getName()],
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
				'hen_list' => array(),
				'sho_list' => array(),
				'field_list' => $header,
		);
	}

	/**
	 * @Route("/typesetting/download", name="client.typesetting.download")
	 * @Method("POST|GET")
	 */
	public function typesettingDownloadAction(Request $request){
		$tmpFilePath = tempnam(sys_get_temp_dir(), 'tmp');

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
		}elseif($type == '1'){
			$type_name = '索引';
		}else{
			$type_name = 'preset';
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

		$entity = $em->getRepository('CCKCommonBundle:MainTerm')->getMainTermList($versionId,$term_id,$hen,$sho,$type);

		// ヘッダー
		if($type == '2'){
			// 汎用CSV
			$header = $this->encoding(explode(",", $request->query->get('generic_field')), $request);
		}else{
			// 本文・索引組版
			$header = $this->encoding($this->generateHeader($entity[0]), $request);
		}

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
		$response = new StreamedResponse(function() use($body_list,$header) {
			$handle = fopen('php://output', 'w+');
			fputcsv($handle, $header, '	');

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
		$generic_value = explode(",", $request->query->get('generic_value'));

		$body_list = [];
		foreach($entity as $mainTermRec){

			$entity_exp = $em->getRepository('CCKCommonBundle:ExplainIndex')->getExplainTerms($mainTermRec['term_id']);
			$entity_sub = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSubterm($mainTermRec['term_id'],false,$type);
			$entity_syn = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSynonym($mainTermRec['term_id'],false,$type);
			$entity_ref = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfRefer($mainTermRec['term_id']);

			// body
			$body = $this->encoding($this->generateBody($mainTermRec,$entity_exp, $entity_sub, $entity_syn, $entity_ref, $type, $generic_value), $request);
			array_push($body_list,$body);
		}

		return $body_list;

	}

	/**
	 * @param  array $header
	 * @return array $trans
	 */
	private function generateHeader($header_main){
		// trans service
		$translator = $this->get('translator');

		$result = [];
		// 主用語
		foreach ($header_main as $key => $value) {
			$result[$key] = $translator->trans('csv.term.' . $key);
		}

		$result['hen'] = $translator->trans('csv.term.hen');
		$result['sho'] = $translator->trans('csv.term.sho');
		$result['dai'] = $translator->trans('csv.term.dai');
		$result['chu'] = $translator->trans('csv.term.chu');
		$result['ko'] = $translator->trans('csv.term.ko');
		$result['header_position'] = $translator->trans('csv.term.header_position');

		// 解説内索引用語
		$result['exp_term'] = $translator->trans('csv.term.exp_term');
		$result['exp_index_kana'] = $translator->trans('csv.term.exp_index_kana');
		$result['exp_index_add_letter'] = $translator->trans('csv.term.exp_index_add_letter');
		$result['exp_nombre'] = $translator->trans('csv.term.exp_nombre');

		// サブ用語
		$result['sub_id'] = $translator->trans('csv.term.sub_id');
		$result['sub_term'] = $translator->trans('csv.term.sub_term');
		$result['sub_red_letter'] = $translator->trans('csv.term.sub_red_letter');
		$result['sub_kana'] = $translator->trans('csv.term.sub_kana');
		$result['sub_text_frequency'] = $translator->trans('csv.term.sub_text_frequency');
		$result['sub_center_frequency'] = $translator->trans('csv.term.sub_center_frequency');
		$result['sub_news_exam'] = $translator->trans('csv.term.sub_news_exam');
		$result['sub_delimiter'] = $translator->trans('csv.term.sub_delimiter');
		$result['sub_delimiter_kana'] = $translator->trans('csv.term.sub_delimiter_kana');
		$result['sub_index_add_letter'] = $translator->trans('csv.term.sub_index_add_letter');
		$result['sub_index_kana'] = $translator->trans('csv.term.sub_index_kana');
		$result['sub_nombre'] = $translator->trans('csv.term.sub_nombre');

		// 同対類用語
		$result['syn_id'] = $translator->trans('csv.term.syn_id');
		$result['syn_synonym_id'] = $translator->trans('csv.term.syn_synonym_id');
		$result['syn_term'] = $translator->trans('csv.term.syn_term');
		$result['syn_red_letter'] = $translator->trans('csv.term.syn_red_letter');
		$result['syn_kana'] = $translator->trans('csv.term.syn_kana');
		$result['syn_text_frequency'] = $translator->trans('csv.term.syn_text_frequency');
		$result['syn_center_frequency'] = $translator->trans('csv.term.syn_center_frequency');
		$result['syn_news_exam'] = $translator->trans('csv.term.syn_news_exam');
		$result['syn_delimiter'] = $translator->trans('csv.term.syn_delimiter');
		$result['syn_index_add_letter'] = $translator->trans('csv.term.syn_index_add_letter');
		$result['syn_index_kana'] = $translator->trans('csv.term.syn_index_kana');
		$result['syn_nombre'] = $translator->trans('csv.term.syn_nombre');

		// 指矢印用語
		$result['ref_hen'] = $translator->trans('csv.term.ref_hen');
		$result['ref_sho'] = $translator->trans('csv.term.ref_sho');
		$result['ref_dai'] = $translator->trans('csv.term.ref_dai');
		$result['ref_chu'] = $translator->trans('csv.term.ref_chu');
		$result['ref_ko'] = $translator->trans('csv.term.ref_ko');
		$result['ref_refer_term_id'] = $translator->trans('csv.term.ref_refer_term_id');
		$result['ref_main_term'] = $translator->trans('csv.term.ref_main_term');
		$result['ref_nombre'] = $translator->trans('csv.term.ref_nombre');

		// 順番を決める
		$trans = [];
		foreach($this->termCsvHeaderSorting as $sort){
			if(!isset($result[$sort])) continue;
			$trans[$sort] = $result[$sort];
		}

		return $trans;
	}

	/**
	 * @param  array $coupons
	 * @return array $body
	 */
	private function generateBody($main, $expterm, $subterm, $synterm, $refterm, $type, $generic){
		$body = [];
		$result = [];

		$em = $this->getDoctrine()->getManager();
		$translator = $this->get('translator');

		$search_newline = '/\r\n|\r|\n/';

		// 主用語
		$this->replaceMainField($main);

		$main['nombre'] = (($type == '1') ? $main['nombre'] : '');
		$main['illust_nombre'] = (($type == '1') ? $main['illust_nombre'] : '');

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
		if($type == '2'){
			// 汎用
			$field_list = $generic;
		}else{
			// 本文・索引組版
			$field_list = $this->termCsvHeaderSorting;
		}

		$trans = [];
		foreach($field_list as $sort){
			if(!array_key_exists($sort, $result)) continue;
			$trans[] = trim($result[$sort]);
		}

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

}