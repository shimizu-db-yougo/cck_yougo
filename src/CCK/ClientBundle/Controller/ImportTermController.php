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
use CCK\CommonBundle\Entity\MainTerm;
use CCK\CommonBundle\Entity\SubTerm;
use CCK\CommonBundle\Entity\Synonym;
use CCK\CommonBundle\Entity\ExplainIndex;
use CCK\CommonBundle\Entity\Refer;
use CCK\CommonBundle\Entity\Center;

/**
 * importTerm controller.
 * 用語上書きコントローラー
 *
 */
class ImportTermController extends BaseController {

	/*  -AH */
	private $termCsvHeaderMain = [
			'term_id',
			'cur_name',
			'var_name',
			'hen',
			'sho',
			'dai',
			'chu',
			'ko',
			'print_order',
			'main_term',
			'text_frequency',
			'center_frequency2009',
			'center_frequency2010',
			'center_frequency2011',
			'center_frequency2012',
			'center_frequency2013',
			'center_frequency2014',
			'center_frequency2015',
			'center_frequency2016',
			'center_frequency2017',
			'center_frequency2018',
			'news_exam',
			'delimiter',
			'western_language',
			'birth_year',
			'index_add_letter',
			'index_kana',
			'index_original_kana',
			'index_original',
			'index_abbreviation'
	];

	/* 5件 -EI */
	private $termCsvHeaderSub = [
			'subxxx_id',
			'subxxx_term',
			'subxxx_text_frequency',
			'subxxx_center_frequency2009',
			'subxxx_center_frequency2010',
			'subxxx_center_frequency2011',
			'subxxx_center_frequency2012',
			'subxxx_center_frequency2013',
			'subxxx_center_frequency2014',
			'subxxx_center_frequency2015',
			'subxxx_center_frequency2016',
			'subxxx_center_frequency2017',
			'subxxx_center_frequency2018',
			'subxxx_news_exam',
			'subxxx_delimiter',
			'subxxx_index_add_letter',
			'subxxx_index_kana'
	];

	/* -EM */
	private $termCsvHeaderMain2 = [
			'term_explain'
	];

	/* -FA */
	private $termCsvHeaderExp = [
			'exp_id',
			'exp_index_kana',
			'exp_term',
			'exp_text_frequency',
			'exp_center_frequency2009',
			'exp_center_frequency2010',
			'exp_center_frequency2011',
			'exp_center_frequency2012',
			'exp_center_frequency2013',
			'exp_center_frequency2014',
			'exp_center_frequency2015',
			'exp_center_frequency2016',
			'exp_center_frequency2017',
			'exp_center_frequency2018',
			'exp_news_exam'
	];

	/* 11件 -NM */
	private $termCsvHeaderSyn = [
			'synxxx_synonym_id',
			'synxxx_id',
			'synxxx_term',
			'synxxx_text_frequency',
			'synxxx_center_frequency2009',
			'synxxx_center_frequency2010',
			'synxxx_center_frequency2011',
			'synxxx_center_frequency2012',
			'synxxx_center_frequency2013',
			'synxxx_center_frequency2014',
			'synxxx_center_frequency2015',
			'synxxx_center_frequency2016',
			'synxxx_center_frequency2017',
			'synxxx_center_frequency2018',
			'synxxx_news_exam',
			'synxxx_delimiter',
			'synxxx_index_add_letter',
			'synxxx_index_kana'
	];

	/* 2件 -NS */
	private $termCsvHeaderRef = [
			'refxxx_refer_term_id'
	];

	/* -NX */
	private $termCsvHeaderIllust = [
			'handover',
			'illust_filename',
			'illust_caption',
			'illust_kana',
			'nombre_bold'
	];

	/**
	 * @Route("/csv/import_term/index/{status}", name="client.csv.import.term")
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
			$status_message = "ファイル形式を確認してください。CSVファイルのみ取込可能です。";
		}elseif($status == 202){
			$status_message = "テキストファイルのエンコード形式を確認してください。取込可能な形式はUTF-8です。";
		}elseif($status == 203){
			$status_message = "教科・版が異なっています。";
		}elseif($status == 204){
			$status_message = "項目数が異なっています。項目数：336";
		}elseif($status == 'default'){
			$status_message = "";
		}else{
			$status_message = "csvファイルの取込みに失敗しました。ファイルを確認して下さい。";
		}

		$upload_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Upload')->getUploadList("term");

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
	 * @Route("/import_term/download", name="client.import_term.download")
	 * @Method("POST|GET")
	 */
	public function importTermDownloadAction(Request $request){
		// session
		$session = $request->getSession();

		$tmpFilePath = tempnam(sys_get_temp_dir(), 'tmp');

		$em = $this->getDoctrine()->getManager();

		// 教科名の取得
		$curriculumId = $request->query->get('curriculum');

		$versionId = $request->query->get('version');

		$entityCurriculum = $em->getRepository('CCKCommonBundle:Curriculum')->getCurriculumVersionList($versionId);

		$cur_name = '';
		if($entityCurriculum){
			$cur_name = $entityCurriculum[0]['cur_name'] . '_' . $entityCurriculum[0]['name'];
		}

		$type_name = '上書き';

		// ファイル名の生成
		$outFileName = $cur_name . '_' . $type_name . '_' . date('YmdHis') . ".csv";

		$path = $this->container->getParameter('archive')['dir_path'];
		$webpath = $request->getSchemeAndHttpHost() . '/' . $this->container->getParameter('archive')['link'];

		$term_id = "";
		$hen = "";
		$sho = "";
		$type = "";
		$entity = $em->getRepository('CCKCommonBundle:MainTerm')->getMainTermList($versionId,$term_id,$hen,$sho,$type);

		// 原稿データCSV生成
		global $arr_err_list;
		$arr_err_list = array();
		global $status_err;
		$status_err = 0;

		$this->RemoveLog("id_check.log");

		if($entity){
			$entityVer = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findOneBy(array(
					'id' => $entity[0]['ver_id'],
					'deleteFlag' => FALSE
			));

			$body_list = $this->constructManuscriptCSV($request, $entity, $outFileName, $entityVer, $arr_err_list, $status_err);
		}else{
			$body_list = false;
		}

		$status = 0;
		if(($body_list === false)||($status_err > 0)){
			if($type == '0'){
				$status = 204;
			}elseif ($type == '1'){
				$status = 205;
			}elseif ($type == '3'){
				$status = 207;
			}else{
				$status = 206;
			}

			// ID存在チェックエラー
			if($status_err > 0){
				if($type == '0'){
					$status = 210;
				}elseif ($type == '1'){
					$status = 211;
				}elseif ($type == '3'){
					$status = 212;
				}
			}

			return $this->redirect($this->generateUrl('client.csv.export', array('status' => $status)));
		}

		// ヘッダー
		$header = $this->encoding($this->generateHeader($entity[0]), $request);

		// trans service
		$translator = $this->get('translator');
		// 入力データの不備の場合のアラート表示
		if(count($arr_err_list) > 0){
			if($type == '0'){
				$status = 251;
			}elseif ($type == '1'){
				$status = 252;
			}elseif ($type == '3'){
				$status = 254;
			}else{
				if(in_array($translator->trans('csv.term.syn_synonym_id'), $header)){
					$status = 253;
				}
			}
			$session->set(self::SES_CSV_FIELD_ALERT_KEY, $arr_err_list);

			if($status > 0){
				if($term_id){
					// アラート表示
					setcookie('isalert',$arr_err_list[0][0].$arr_err_list[0][1]."　主用語：".$arr_err_list[0][2]."　同対類語：".$arr_err_list[0][3]."の同体類マークがブランクです",0,'/');
				}else{
					return $this->redirect($this->generateUrl('client.csv.export', array('status' => $status)));
				}
			}
		}

		// response
		$response = new StreamedResponse(function() use($body_list,$header) {
			$handle = fopen('php://output', 'w+');

			// ExcelでUTF-8と認識させるためにBOMを付ける
			fwrite($handle, pack('C*',0xEF,0xBB,0xBF));

			fputcsv($handle, $header, ',');

			foreach ($body_list as $value) {
				$value = str_replace("\n", chr(10), $value);
				$value = str_replace('"', '""', $value); // Excelで読み込めるようにダブルクォーテーションのエスケープ
				fputs($handle, '"'.implode('","', $value).'"'."\n"); // 各フィールドにダブルクォーテーションを付与
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

			// ダウンロード完了時にローディング表示を消す
			setcookie('downloaded','complete',0,'/');

			return $response;

	}

	// ExcelデータCSV生成
	private function constructManuscriptCSV($request, $entity, $outFileName, $entityVer, &$arr_err_list, &$status_err) {
		$em = $this->getDoctrine()->getManager();

		$body_list = [];
		foreach($entity as $mainTermRec){

			$entity_exp = $em->getRepository('CCKCommonBundle:ExplainIndex')->getExplainTerms($mainTermRec['term_id']);
			$entity_sub = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSubterm($mainTermRec['term_id'],false);
			$entity_syn = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSynonym($mainTermRec['term_id'],false);
			$entity_ref = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfRefer($mainTermRec['term_id']);

			// body
			$body = $this->encoding($this->generateBody($mainTermRec, $entity_exp, $entity_sub, $entity_syn, $entity_ref, $entityVer, $arr_err_list, $status_err), $request);
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

		$result['term_id'] = $translator->trans('csv.macro.term_id');
		$result['var_name'] = $translator->trans('csv.macro.ver_name');
		$result['hen'] = $translator->trans('csv.term.hen');
		$result['sho'] = $translator->trans('csv.term.sho');
		$result['dai'] = $translator->trans('csv.term.dai');
		$result['chu'] = $translator->trans('csv.term.chu');
		$result['ko'] = $translator->trans('csv.term.ko');
		$result['header_position'] = $translator->trans('csv.term.header_position');
		$result['main_term'] = $translator->trans('csv.macro.term1');
		$result['text_frequency_alphabet'] = $translator->trans('csv.macro.text_frequency_alphabet');

		for($i=2009;$i<=2018;$i++){
			$result['center_frequency'.$i] = $translator->trans('csv.term.center_frequency').$i;
		}

		// サブ用語
		for($idx=1;$idx<=5;$idx++){
			$result['sub'.$idx.'_id'] = $translator->trans('csv.macro.sub'.$idx.'_id');
			$result['sub'.$idx.'_term'] = $translator->trans('csv.macro.sub'.$idx.'_term');
			$result['sub'.$idx.'_red_letter'] = $translator->trans('csv.macro.sub'.$idx.'_red_letter');
			$result['sub'.$idx.'_kana'] = $translator->trans('csv.macro.sub'.$idx.'_kana');
			$result['sub'.$idx.'_delimiter_kana'] = $translator->trans('csv.macro.sub'.$idx.'_delimiter_kana');
			$result['sub'.$idx.'_text_frequency'] = $translator->trans('csv.macro.sub'.$idx.'_text_frequency');
			$result['sub'.$idx.'_text_frequency_alphabet'] = $translator->trans('csv.macro.sub'.$idx.'_text_frequency_alphabet');

			for($i=2009;$i<=2018;$i++){
				$result['sub'.$idx.'_center_frequency'.$i] = $translator->trans('csv.macro.sub'.$idx.'_center_frequency'.$i);
			}

			$result['sub'.$idx.'_center_frequency'] = $translator->trans('csv.macro.sub'.$idx.'_center_frequency');
			$result['sub'.$idx.'_news_exam'] = $translator->trans('csv.macro.sub'.$idx.'_news_exam');
			$result['sub'.$idx.'_delimiter'] = $translator->trans('csv.macro.sub'.$idx.'_delimiter');
			$result['sub'.$idx.'_index_add_letter'] = $translator->trans('csv.macro.sub'.$idx.'_index_add_letter');
			$result['sub'.$idx.'_index_kana'] = $translator->trans('csv.macro.sub'.$idx.'_index_kana');
		}

		$result['western_sentence'] = $translator->trans('csv.macro.western_language');

		// 解説内索引用語
		$result['exp_id'] = $translator->trans('csv.term.exp_id');
		$result['exp_term'] = $translator->trans('csv.macro.exp_term');
		$result['exp_text_frequency'] = $translator->trans('csv.term.exp_text_frequency');

		for($i=2009;$i<=2018;$i++){
			$result['exp_center_frequency'.$i] = $translator->trans('csv.term.exp_center_frequency').$i;
		}

		$result['exp_news_exam'] = $translator->trans('csv.term.exp_news_exam');
		$result['exp_index_kana'] = $translator->trans('csv.term.exp_index_kana');

		// 同対類用語
		for($idx=1;$idx<=11;$idx++){
			$result['syn'.$idx.'_id'] = $translator->trans('csv.macro.syn'.$idx.'_id');
			$result['syn'.$idx.'_synonym_id'] = $translator->trans('csv.macro.syn'.$idx.'_synonym_id');
			$result['syn'.$idx.'_term'] = $translator->trans('csv.macro.syn'.$idx.'_term');
			$result['syn'.$idx.'_red_letter'] = $translator->trans('csv.macro.syn'.$idx.'_red_letter');
			$result['syn'.$idx.'_text_frequency'] = $translator->trans('csv.macro.syn'.$idx.'_text_frequency');
			$result['syn'.$idx.'_text_frequency_alphabet'] = $translator->trans('csv.macro.syn'.$idx.'_text_frequency_alphabet');

			for($i=2009;$i<=2018;$i++){
				$result['syn'.$idx.'_center_frequency'.$i] = $translator->trans('csv.macro.syn'.$idx.'_center_frequency').$i;
			}

			$result['syn'.$idx.'_center_frequency'] = $translator->trans('csv.macro.syn'.$idx.'_center_frequency');
			$result['syn'.$idx.'_news_exam'] = $translator->trans('csv.macro.syn'.$idx.'_news_exam');
			$result['syn'.$idx.'_delimiter'] = $translator->trans('csv.macro.syn'.$idx.'_delimiter');
			$result['syn'.$idx.'_index_add_letter'] = $translator->trans('csv.macro.syn'.$idx.'_index_add_letter');
			$result['syn'.$idx.'_index_kana'] = $translator->trans('csv.macro.syn'.$idx.'_index_kana');
		}

		// 指矢印用語
		for($idx=1;$idx<=2;$idx++){
			$result['ref'.$idx.'_refer_term_id'] = $translator->trans('csv.macro.ref'.$idx.'_refer_term_id');
			$result['ref'.$idx.'_nombre'] = $translator->trans('csv.macro.ref'.$idx.'_nombre');
			$result['ref'.$idx.'_main_term'] = $translator->trans('csv.macro.ref'.$idx.'_main_term');
		}

		// 順番を決める
		$trans = $this->constructField($result);

		return $trans;
	}

	/**
	 * @param  array $coupons
	 * @return array $body
	 */
	private function generateBody($main, $expterm, $subterm, $synterm, $refterm, $entityVer ,&$arr_err_list ,&$status_err){
		$body = [];
		$result = [];

		$em = $this->getDoctrine()->getManager();
		$translator = $this->get('translator');

		$search_newline = '/\r\n|\r|\n/';

		// 主用語
		$term_id = $main['term_id'];
		$this->replaceMainField($main,$entityVer);

		// サブ用語
		$sub = [];

		for($idx=1;$idx<=5;$idx++){
			if(!empty($subterm[$idx-1])){
				$this->replaceSubField($subterm[$idx-1],$entityVer,$term_id);
			}

			$sub['sub'.$idx.'_id'] = (empty($subterm[$idx-1])) ? "" : $subterm[$idx-1]['id'];
			$sub['sub'.$idx.'_term'] = (empty($subterm[$idx-1])) ? "" : $subterm[$idx-1]['sub_term'];
			$sub['sub'.$idx.'_red_letter'] = (empty($subterm[$idx-1])) ? "" : $subterm[$idx-1]['red_letter'];
			$sub['sub'.$idx.'_kana'] = (empty($subterm[$idx-1])) ? "" : $subterm[$idx-1]['kana'];
			$sub['sub'.$idx.'_delimiter_kana'] = (empty($subterm[$idx-1])) ? "" : $subterm[$idx-1]['delimiter_kana'];
			$sub['sub'.$idx.'_text_frequency'] = (empty($subterm[$idx-1])) ? "" : $subterm[$idx-1]['text_frequency'];
			$sub['sub'.$idx.'_text_frequency_alphabet'] = (empty($subterm[$idx-1])) ? "" : $subterm[$idx-1]['text_frequency_alphabet'];

			for($year=2009;$year<=2018;$year++){
				$sub['sub'.$idx.'_center_frequency'.$year] = (empty($subterm[$idx-1])) ? "" : $subterm[$idx-1]['center_frequency'.$year];
			}

			$sub['sub'.$idx.'_center_frequency'] = (empty($subterm[$idx-1])) ? 0 : $subterm[$idx-1]['center_frequency'];
			$sub['sub'.$idx.'_news_exam'] = (empty($subterm[$idx-1])) ? "" : $subterm[$idx-1]['news_exam'];
			$sub['sub'.$idx.'_delimiter'] = (empty($subterm[$idx-1])) ? "" : $subterm[$idx-1]['delimiter'];
			$sub['sub'.$idx.'_index_add_letter'] = (empty($subterm[$idx-1])) ? "" : $subterm[$idx-1]['index_add_letter'];
			$sub['sub'.$idx.'_index_kana'] = (empty($subterm[$idx-1])) ? "" : $subterm[$idx-1]['index_kana'];
		}

		// 解説内索引用語
		$exp = [];
		$exp['exp_id'] = "";
		$exp['exp_index_kana'] = "";
		$exp['exp_term'] = "";
		$exp['exp_text_frequency'] = "";

		for($i=2009;$i<=2018;$i++){
			$exp['exp_center_frequency'.$i] = "";
		}

		$exp['exp_news_exam'] = "";

		if($expterm){
			foreach ($expterm as $exptermRec) {
				$this->replaceExpField($exptermRec,$entityVer);

				$exp['exp_id'] .= $exptermRec['id'] . ';';
				$exp['exp_index_kana'] .= $exptermRec['indexKana'] . ';';
				$exp['exp_term'] .= $exptermRec['indexTerm'] . ';';
				$exp['exp_text_frequency'] .= $exptermRec['textFrequency'] . ';';

				// センター頻度10年分の取得
				$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
						'mainTermId' => $term_id,
						'subTermId' => $exptermRec['id'],
						'yougoFlag' => '4',
						'deleteFlag' => FALSE
				),
				array('year' => 'ASC'));

				foreach ($entityCenter as $entityCenterRec){
					if(($entityCenterRec->getYear() >= 2009)&&($entityCenterRec->getYear() <= 2018)){
						$exp['exp_center_frequency'.$entityCenterRec->getYear()] .= $entityCenterRec->getMainExam() . "/" . $entityCenterRec->getSubExam() . ';';
					}
				}

				$exp['exp_news_exam'] .= (($exptermRec['newsExam'] == '') ? 0 : $exptermRec['newsExam']) . ';';
			}

			foreach ($exp as $key => $val) {
				$exp[$key] = mb_substr($val,0,mb_strlen($val)-1);
			}
		}

		$main['western_sentence'] = '';

		// 同対類用語
		$syn = [];

		for($idx=1;$idx<=11;$idx++){
			if(!empty($synterm[$idx-1])){
				$this->replaceSynField($synterm[$idx-1],$entityVer,$main,$arr_err_list,$term_id);
			}

			$syn['syn'.$idx.'_id'] = (empty($synterm[$idx-1])) ? "" : $synterm[$idx-1]['id'];
			$syn['syn'.$idx.'_synonym_id'] = (empty($synterm[$idx-1])) ? "" : $synterm[$idx-1]['synonym_id'];
			$syn['syn'.$idx.'_term'] = (empty($synterm[$idx-1])) ? "" : $synterm[$idx-1]['term'];
			$syn['syn'.$idx.'_red_letter'] = (empty($synterm[$idx-1])) ? "" : $synterm[$idx-1]['red_letter'];
			$syn['syn'.$idx.'_text_frequency'] = (empty($synterm[$idx-1])) ? "" : $synterm[$idx-1]['text_frequency'];
			$syn['syn'.$idx.'_text_frequency_alphabet'] = (empty($synterm[$idx-1])) ? "" : $synterm[$idx-1]['text_frequency_alphabet'];

			for($year=2009;$year<=2018;$year++){
				$syn['syn'.$idx.'_center_frequency'.$year] = (empty($synterm[$idx-1])) ? "" : $synterm[$idx-1]['center_frequency'.$year];
			}

			$syn['syn'.$idx.'_center_frequency'] = (empty($synterm[$idx-1])) ? 0 : $synterm[$idx-1]['center_frequency'];
			$syn['syn'.$idx.'_news_exam'] = (empty($synterm[$idx-1])) ? "" : $synterm[$idx-1]['news_exam'];
			$syn['syn'.$idx.'_delimiter'] = (empty($synterm[$idx-1])) ? "" : $synterm[$idx-1]['delimiter'];
			$syn['syn'.$idx.'_index_add_letter'] = (empty($synterm[$idx-1])) ? "" : $synterm[$idx-1]['index_add_letter'];
			$syn['syn'.$idx.'_index_kana'] = (empty($synterm[$idx-1])) ? "" : $synterm[$idx-1]['index_kana'];
		}

		// 指矢印参照
		$ref = [];

		for($idx=1;$idx<=2;$idx++){
			$ref['ref'.$idx.'_refer_term_id'] = (empty($refterm[$idx-1])) ? "" : 'M'.str_pad($refterm[$idx-1]['refer_term_id'], 6, 0, STR_PAD_LEFT);
			$ref['ref'.$idx.'_nombre'] = '';
			$ref['ref'.$idx.'_main_term'] = (empty($refterm[$idx-1])) ? "" : $refterm[$idx-1]['main_term'];
		}

		$result = array_merge($main,$exp,$sub,$syn,$ref);

		$trans = $this->constructField($result);

		return $trans;
	}

	private function constructField($result){
		// Excelの用語入力シートに合わせて配列を構成する
		// 主用語
		$field_list_main = $this->termCsvHeaderMain;

		$field_list_sub = [];
		$idx = 0;
		// サブ用語
		for ($i=1; $i<=5; $i++) {
			foreach ($this->termCsvHeaderSub as $key => $value){
				$field_list_sub[$idx] = str_replace('xxx', $i, $value);
				$idx++;
			}
		}

		$field_list2 = [];
		foreach ($this->termCsvHeaderMain2 as $key => $value){
			$field_list2[$key] = $value;
		}

		$field_list_exp = [];
		// 解説内さくいん用語
		foreach ($this->termCsvHeaderExp as $key => $value){
			$field_list_exp[$key] = $value;
		}

		$field_list_syn = [];
		$idx = 0;
		// 同対類用語
		for ($i=1; $i<=11; $i++) {
			foreach ($this->termCsvHeaderSyn as $key => $value){
				$field_list_syn[$idx] = str_replace('xxx', $i, $value);
				$idx++;
			}
		}

		$field_list_ref = [];
		$idx = 0;
		// 指矢印参照用語
		for ($i=1; $i<=2; $i++) {
			foreach ($this->termCsvHeaderRef as $key => $value){
				$field_list_ref[$idx] = str_replace('xxx', $i, $value);
				$idx++;
			}
		}

		$field_list_illust = [];
		foreach ($this->termCsvHeaderIllust as $key => $value){
			$field_list_illust[$key] = $value;
		}

		$field_list = array_merge($field_list_main,$field_list_sub,$field_list2,$field_list_exp,$field_list_syn,$field_list_ref,$field_list_illust);
		$trans = [];

		foreach($field_list as $sort){
			if(!array_key_exists($sort, $result)) continue;
			$trans[] = trim($result[$sort]);
		}

		return $trans;
	}



	private function replaceMainField(&$main,$entityVer){
		$em = $this->getDoctrine()->getManager();

		$main['header_position'] = "";
		// 主用語　見出し名称の取得
		$this->getHeaderName($main);

		$main_term_id = $main['term_id'];
		$main['term_id'] = 'M'.str_pad($main_term_id, 6, 0, STR_PAD_LEFT);

		if($main['center_frequency'] > 4){
			$main['red_letter'] = '●';
		}else{
			$main['red_letter'] = '';
		}

		if($main['kana_exist_flag'] == '1'){
			$main['kana_exist_flag'] = '●';
		}else{
			$main['kana_exist_flag'] = '';
		}

		if($main['text_frequency'] >= $entityVer->getRankA()){
			$main['text_frequency_alphabet'] = 'A';
		}elseif(($main['text_frequency'] >= $entityVer->getRankB())&&($main['text_frequency'] <= ($entityVer->getRankA()-1))){
			$main['text_frequency_alphabet'] = 'B';
		}elseif(($main['text_frequency'] >= 0)&&($main['text_frequency'] <= ($entityVer->getRankB()-1))){
			$main['text_frequency_alphabet'] = 'C';
		}else{
			$main['text_frequency_alphabet'] = '';
		}

		// センター頻度10年分の取得
		$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
				'mainTermId' => $main_term_id,
				'yougoFlag' => '1',
				'deleteFlag' => FALSE
		),
		array('year' => 'ASC'));

		foreach ($entityCenter as $entityCenterRec){
			if(($entityCenterRec->getYear() >= 2009)&&($entityCenterRec->getYear() <= 2018)){
				$main['center_frequency'.$entityCenterRec->getYear()] = $entityCenterRec->getMainExam() . "/" . $entityCenterRec->getSubExam();
			}
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

		if($main['nombre_bold'] == '1'){
			$main['nombre_bold'] = '●';
		}else{
			$main['nombre_bold'] = '';
		}

	}

	private function replaceExpField(&$exp,$entityVer){
	}

	private function replaceSubField(&$sub,$entityVer,$term_id){
		$em = $this->getDoctrine()->getManager();

		if($sub['center_frequency'] > 4){
			$sub['red_letter'] = '●';
		}else{
			$sub['red_letter'] = '';
		}

		if($sub['text_frequency'] >= $entityVer->getRankA()){
			$sub['text_frequency_alphabet'] = 'A';
		}elseif(($sub['text_frequency'] >= $entityVer->getRankB())&&($sub['text_frequency'] <= ($entityVer->getRankA()-1))){
			$sub['text_frequency_alphabet'] = 'B';
		}elseif(($sub['text_frequency'] >= 0)&&($sub['text_frequency'] <= ($entityVer->getRankB()-1))){
			$sub['text_frequency_alphabet'] = 'C';
		}else{
			$sub['text_frequency_alphabet'] = '';
		}

		// センター頻度10年分の取得
		$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
				'mainTermId' => $term_id,
				'subTermId' => $sub['id'],
				'yougoFlag' => '2',
				'deleteFlag' => FALSE
		),
				array('year' => 'ASC'));

		foreach ($entityCenter as $entityCenterRec){
			if(($entityCenterRec->getYear() >= 2009)&&($entityCenterRec->getYear() <= 2018)){
				$sub['center_frequency'.$entityCenterRec->getYear()] = $entityCenterRec->getMainExam() . "/" . $entityCenterRec->getSubExam();
			}
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
		}elseif($sub['delimiter'] == '8'){
			$sub['delimiter'] = '）と';
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
		}elseif($sub['delimiter_kana'] == '8'){
			$sub['delimiter_kana'] = '）と';
		}
	}

	private function replaceSynField(&$syn,$entityVer,$main,&$arr_err_list,$term_id){
		$em = $this->getDoctrine()->getManager();

		if($syn['synonym_id'] == '1'){
			$syn['synonym_id'] = '同';
		}elseif($syn['synonym_id'] == '2'){
			$syn['synonym_id'] = '対';
		}elseif($syn['synonym_id'] == '3'){
			$syn['synonym_id'] = '類';
		}

		if($syn['center_frequency'] > 4){
			$syn['red_letter'] = '●';
		}else{
			$syn['red_letter'] = '';
		}

		if($syn['text_frequency'] >= $entityVer->getRankA()){
			$syn['text_frequency_alphabet'] = 'A';
		}elseif(($syn['text_frequency'] >= $entityVer->getRankB())&&($syn['text_frequency'] <= ($entityVer->getRankA()-1))){
			$syn['text_frequency_alphabet'] = 'B';
		}elseif(($syn['text_frequency'] >= 0)&&($syn['text_frequency'] <= ($entityVer->getRankB()-1))){
			$syn['text_frequency_alphabet'] = 'C';
		}else{
			$syn['text_frequency_alphabet'] = '';
		}

		// センター頻度10年分の取得
		$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
				'mainTermId' => $term_id,
				'subTermId' => $syn['id'],
				'yougoFlag' => '3',
				'deleteFlag' => FALSE
		),
		array('year' => 'ASC'));

		foreach ($entityCenter as $entityCenterRec){
			if(($entityCenterRec->getYear() >= 2009)&&($entityCenterRec->getYear() <= 2018)){
				$syn['center_frequency'.$entityCenterRec->getYear()] = $entityCenterRec->getMainExam() . "/" . $entityCenterRec->getSubExam();
			}
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
			$syn['delimiter'] = '《rtn》';
		}elseif($syn['delimiter'] == '5'){
			$syn['delimiter'] = '《rtn》（';
		}elseif($syn['delimiter'] == '6'){
			$syn['delimiter'] = '）《rtn》';
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
		$record['hen'] = "";
		if($entityHen){
			$record['hen'] = $entityHen->getName();
		}

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

	private function openExportFile($tablename){
		// CSV出力先の決定
		$csvName = $tablename.'_' . date('Ymd') . '_' . date('Hi') . '.csv';
		$csvDir = $this->container->getParameter('updateterm')['dir_path'];
		$csv_path = $csvDir . '/' . $csvName;

		// ファイル作成
		if (!file_exists($csv_path)) {
			// ファイルが存在しない場合作成
			touch($csv_path);
			// 権限変更
			chmod($csv_path, 0666);
		}

		// CSVファイル出力
		$handle = fopen($csv_path, "w+");

		$sql = "SET NAMES utf8;";
		fputs($handle, $sql."\n");

		return array($handle,$csv_path,$csvName);
	}

	/**
	 * @Route("/import_term/upload", name="client.import_term.upload")
	 * @Method("POST|GET")
	 */
	public function importTermUploadAction(Request $request){
		$user = $this->getUser();

		// postでもらうべきのリストを取得
		$targets = $this->container->getParameter('api.upload.term');

		$update_all = false;
		if($request->request->has("up_submit_freq")){
			$update_all = false;
		}
		if($request->request->has("up_submit_all")){
			$update_all = true;
		}

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
			return $this->redirect($this->generateUrl('client.csv.import.term', array('status' => 451)));
		}

		$this->RemoveLog("import_term.log");
		$status = 200;
		try {
			// 初期化
			$this->clearDirectory($archivePath . $params['curriculum'] . '_' . $params['version2']);

			foreach ($files as $el_file_list){
				$filename = $this->uploads($el_file_list['name'], $archivePath . $params['curriculum'] . '_' . $params['version2'] . '/', $tempPath . '/');
			}

			if(!$this->checkFileType($archivePath . $params['curriculum'] . '_' . $params['version2'] . '/' . $filename)){
				// ログ出力
				$this->OutputLog("ERROR", "import_term.log", "ファイル形式を確認してください。CSVファイルのみ取込可能です。");
				$status = 201;
				return $this->redirect($this->generateUrl('client.csv.import.term', array('status' => $status)));
			}

			// 教科・版の取得
			$entityCurriculum = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findOneBy(array(
					'id' => $params['curriculum'],
					'deleteFlag' => FALSE
			));
			$curriculum_name = $entityCurriculum->getName();

			$entityVersion = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findOneBy(array(
					'id' => $params['version2'],
					'deleteFlag' => FALSE
			));
			$version_name = $entityVersion->getName();

		} catch(\Exception $e){
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());
			return $this->redirect($this->generateUrl('client.csv.import.term', array('status' => 452)));
		}

		// テンポラリフォルダ内のファイルを削除
		$this->clearDirectory($tempPath);

		// クエリファイルの定義
		$csvDir = $this->container->getParameter('updateterm')['dir_path'];

		// ディレクトリ作成
		if (!is_dir($csvDir)) {
			// ディレクトリが存在しない場合作成
			mkdir($csvDir, 0777, True);
			// umaskを考慮して再度777に変更
			chmod($csvDir, 0777);
		}else{
			$this->clearDirectory($csvDir);
		}

		$handleMain = $this->openExportFile("MainTerm");
		$handleExplain = $this->openExportFile("ExplainIndex");
		$handleSub = $this->openExportFile("SubTerm");
		$handleSyn = $this->openExportFile("Synonym");
		$handleRefer = $this->openExportFile("Refer");
		$handleCenter = $this->openExportFile("Center");

		$this->get('logger')->info("***用語上書処理:START***");


		$em = $this->get('doctrine.orm.entity_manager');

		$updatefile = array();
		$updatefile = $this->openDir($archivePath . $params['curriculum'] . '_' . $params['version2'], '', $updatefile);
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
					//$line = str_replace('"','',$line);
					//$data = explode(",",$line);
					$data = str_getcsv($line);

					//ヘッダーはスキップ
					if($lineCnt == 1) {
						continue;
					}

					if($lineCnt > 1) {
						//エンコードチェック
						if (mb_detect_encoding($data[2], "UTF-8") === false){
							fclose($filePointer);
							$this->OutputLog("ERROR0", "import_term.log", "テキストファイルのエンコード形式を確認してください。取込可能な形式はUTF-8です。");
							$status = 202;
							return $this->redirect($this->generateUrl('client.csv.import.term', array('status' => $status)));
						}
					}

					// 教科・版チェック
					if(($curriculum_name != $data[1])||($version_name != $data[2])){
						fclose($filePointer);
						$this->OutputLog("ERROR1", "import_term.log", "教科・版が異なっています。Excelデータの教科：".$data[1].",版：".$data[2].";取込対象の教科：".$curriculum_name.",版：".$version_name);
						$status = 203;
						return $this->redirect($this->generateUrl('client.csv.import.term', array('status' => $status)));
					}

					// 項目数チェック
					if(count($data) != 336){
						fclose($filePointer);
						$this->OutputLog("ERROR2", "import_term.log", "[".$data[0]."]の項目数が異なっています。正しい項目数：336。項目数：".count($data));
						$status = 204;
						return $this->redirect($this->generateUrl('client.csv.import.term', array('status' => $status)));
					}

					// db connect and Transaction start
					$em->getConnection()->beginTransaction();

					try {
						// 主用語の更新
						$this->updateMainTerm($data,$em,$update_all,$handleMain[0],$handleCenter[0]);

						// サブ用語の更新
						$sub_id = [];
						for($idx=0;$idx<5;$idx++){
							array_push($sub_id, $this->updateSubTerm($data, $idx*17,$em,$update_all,$handleSub[0],$handleCenter[0]));
						}

						if($update_all){
							// サブ用語の削除
							$this->deleteSubTerm($data, $sub_id, $em, 'SubTerm',$handleSub[0],$handleCenter[0]);
						}

						// 同対類用語の更新
						$syn_id = [];
						for($idx=0;$idx<11;$idx++){
							array_push($syn_id, $this->updateSynTerm($data, $idx*18,$em,$update_all,$handleSyn[0],$handleCenter[0]));
						}

						if($update_all){
							// 同対類用語の削除
							$this->deleteSubTerm($data, $syn_id, $em, 'Synonym',$handleSyn[0],$handleCenter[0]);
						}

						// 解説内さくいん用語の更新
						$this->get('logger')->error("***updateExpTerm***");
						$exp_id = $this->updateExpTerm($data,$em,$update_all,$handleExplain[0],$handleCenter[0]);

						if($update_all){
							// 解説内さくいん用語の削除
							$this->deleteSubTerm($data, $exp_id, $em, 'ExplainIndex',$handleExplain[0],$handleCenter[0]);
						}

						if($update_all){
							// 指矢印参照用語
							$ref_id = [];
							for($idx=0;$idx<2;$idx++){
								array_push($ref_id, $this->updateRefTerm($data, $idx,$em));
							}
							// 指矢印用語の削除
							$this->deleteRefTerm($data, $ref_id, $em, 'Refer',$handleRefer[0]);
						}

						$em->getConnection()->commit();

					} catch (\Exception $e){
						// もし、DBに登録失敗した場合rollbackする
						$em->getConnection()->rollback();
						$em->close();

						// log
						$this->get('logger')->error($e->getMessage());
						$this->get('logger')->error($e->getTraceAsString());

						return $this->redirect($this->generateUrl('client.csv.import.term', array('status' => 453)));
					}
				}
				// ファイルをクローズする
				fclose($filePointer);
				fclose($handleMain[0]);
				fclose($handleExplain[0]);
				fclose($handleSub[0]);
				fclose($handleSyn[0]);
				fclose($handleRefer[0]);
				fclose($handleCenter[0]);

				try{
					$em->getConnection()->beginTransaction();
					$rtn_cd = $this->importSQLFile($handleMain[1],$handleExplain[1],$handleSub[1],$handleSyn[1],$handleRefer[1],$handleCenter[1]);

					if($rtn_cd == false){
						throw new \Exception("importSQL error");
					}

					$em->getConnection()->commit();
				} catch (\Exception $e){
					$em->getConnection()->rollback();
					$em->close();

					// log
					$this->get('logger')->error($e->getMessage());
					$this->get('logger')->error($e->getTraceAsString());
					return $this->redirect($this->generateUrl('client.csv.import.term', array('status' => 453)));
				}

			}else{
				$this->OutputLog("ERROR3", "import_term.log", "ファイル形式を確認してください。CSVファイルのみ取込可能です。");
				$status = 201;
				break;
			}
		}
		$this->get('logger')->info("***用語上書処理:END***");

		// インポート履歴
		$this->registerUploadHistory($em,$params['version2'],$user->getUserId(),$files,$update_all);

		$em->close();

		return $this->redirect($this->generateUrl('client.csv.import.term', array('status' => $status)));

	}

	private function updateMainTerm($data,$em,$update_all,$handle,$handle_center){

		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:MainTerm')->findOneBy(array(
				'termId' =>intval(substr($data[0], 1)),
				'deleteFlag' => FALSE
		));
		if(!$entity){
			return false;
		}

		// センター頻度の年別データ
		$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
				'mainTermId' => intval(substr($data[0], 1)),
				'yougoFlag' => '1',
				'deleteFlag' => FALSE
		),
				array('year' => 'ASC'));

		$year = 2009;
		$idx = 0;
		$center_freq_sum = 0;
		foreach ($entityCenter as $entityCenterRec){
			if($entityCenterRec->getYear() == ($year+$idx)){
				$arr_freq = $this->splitCenterFreq($data[11+$idx]);

				if(count($arr_freq) == 2){
					//$entityCenterRec->setMainExam($arr_freq[0]);
					//$entityCenterRec->setSubExam($arr_freq[1]);
					$center_freq_sum += intval($arr_freq[0]) + intval($arr_freq[1]);

					//$em->flush();

					$sql = "UPDATE `Center` SET `main_exam` = " . $arr_freq[0] . ",`sub_exam` = " . $arr_freq[1];
					$sql .= ",`modify_date` = NOW() WHERE `main_term_id` = ".intval(substr($data[0], 1))." AND `yougo_flag` = 1 AND `year` = " . ($year+$idx) . " AND `delete_flag` = 0;";
					fputs($handle_center, $sql."\n");
				}
			}
			$idx++;
		}

		//$entity->setTextFrequency($data[10]);
		//$entity->setCenterFrequency($center_freq_sum);
		//$entity->setNewsExam($data[21] == "N" ? 1 : 0);

		$sql = "UPDATE `MainTerm` SET `text_frequency` = " . $data[10] . ",`center_frequency` = " . $center_freq_sum . ",`news_exam` = " . ($data[21] == "N" ? 1 : 0);

		if($update_all){
			//$entity->setPrintOrder($data[8]);
			//$entity->setMainTerm($data[9]);

			if($data[22] == ""){
				//$entity->setDelimiter(0);
				$delimiter = 0;
			}elseif($data[22] == "と"){
				//$entity->setDelimiter(1);
				$delimiter = 1;
			}elseif($data[22] == "，"){
				//$entity->setDelimiter(2);
				$delimiter = 2;
			}elseif($data[22] == "・"){
				//$entity->setDelimiter(3);
				$delimiter = 3;
			}elseif($data[22] == "／"){
				//$entity->setDelimiter(4);
				$delimiter = 4;
			}elseif($data[22] == "（"){
				//$entity->setDelimiter(5);
				$delimiter = 5;
			}elseif($data[22] == "）"){
				//$entity->setDelimiter(6);
				$delimiter = 6;
			}

			/*$entity->setWesternLanguage($data[23]);
			$entity->setBirthYear($data[24]);
			$entity->setIndexAddLetter($data[25]);
			$entity->setIndexKana($data[26]);
			$entity->setIndexOriginal($data[27]);
			$entity->setIndexOriginalKana($data[28]);
			$entity->setIndexAbbreviation($data[29]);
			$entity->setTermExplain($data[115]);
			$entity->setHandover($data[331]);
			$entity->setIllustFilename($data[332]);
			$entity->setIllustCaption($data[333]);
			$entity->setIllustKana($data[334]);
			$entity->setNombreBold(($data[335] == "●") ? 1 : 0);*/

			$sql .= ",`print_order` = " . $data[8] . ",`main_term` = '" . $data[9] . "',`delimiter` = '" . $delimiter . "',`western_language` = '" . $data[23] . "',`birth_year` = '" . $data[24] . "',`index_add_letter` = '" . $data[25] . "'";
			$sql .= ",`index_kana` = '" . $data[26] . "',`index_original` = '" . $data[27] . "',`index_original_kana` = '" . $data[28] . "',`index_abbreviation` = '" . $data[29] . "',`term_explain` = '" . $data[115] . "',`handover` = '" . $data[331] . "'";
			$sql .= ",`illust_filename` = '" . $data[332] . "',`illust_caption` = '" . $data[333] . "',`illust_kana` = '" . $data[334] . "',`nombre_bold` = " . (($data[335] == "●") ? 1 : 0);
		}

		//$em->flush();
		$sql .= ",`modify_date` = NOW() WHERE `term_id` = ".intval(substr($data[0], 1))." AND `delete_flag` = 0;";
		fputs($handle, $sql."\n");

	}

	private function updateSubTerm($data,$offset,$em,$update_all,$handle,$handle_center){
		// 用語が設定されていない場合
		if($data[31+$offset] == ""){
			return false;
		}

		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:SubTerm')->findOneBy(array(
				'id' =>$data[30+$offset],
				'mainTermId' =>intval(substr($data[0], 1)),
				'deleteFlag' => FALSE
		));

		$update_mode = 'update';
		if(!$entity){
			if($data[30+$offset] == ""){
				// 用語IDが設定されていない場合、新規登録
				$entity = new SubTerm();
				$update_mode = 'new';
			}else{
				return false;
			}
		}

		// センター頻度の年別データ
		$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
				'mainTermId' => intval(substr($data[0], 1)),
				'subTermId' => $data[30+$offset],
				'yougoFlag' => '2',
				'deleteFlag' => FALSE
		),
		array('year' => 'ASC'));

		$year = 2009;
		$idx = 0;
		$center_freq_sum = 0;
		foreach ($entityCenter as $entityCenterRec){
			if($entityCenterRec->getYear() == ($year+$idx)){
				$arr_freq = $this->splitCenterFreq($data[33+$idx+$offset]);

				if(count($arr_freq) == 2){
					//$entityCenterRec->setMainExam($arr_freq[0]);
					//$entityCenterRec->setSubExam($arr_freq[1]);
					$center_freq_sum += intval($arr_freq[0]) + intval($arr_freq[1]);

					//$em->flush();
					$sql = "UPDATE `Center` SET `main_exam` = " . $arr_freq[0] . ",`sub_exam` = " . $arr_freq[1];
					$sql .= ",`modify_date` = NOW() WHERE `main_term_id` = ".intval(substr($data[0], 1))." AND `sub_term_id` = ".$data[30+$offset]." AND `yougo_flag` = 2 AND `year` = " . ($year+$idx) . " AND `delete_flag` = 0;";
					fputs($handle_center, $sql."\n");
				}
			}
			$idx++;
		}

		// センター頻度未登録の場合
		if(!$entityCenter){
			for ($idx=0;$idx<10;$idx++){
				$arr_freq = $this->splitCenterFreq($data[33+$idx+$offset]);

				if(count($arr_freq) == 2){
					$center_freq_sum += intval($arr_freq[0]) + intval($arr_freq[1]);
				}
			}
		}

		// サブ用語情報の更新(頻度)
		$entity->setTextFrequency($data[32+$offset]);
		$entity->setCenterFrequency($center_freq_sum);
		$entity->setNewsExam($data[43+$offset] == "N" ? 1 : 0);

		$sql = "UPDATE `SubTerm` SET `text_frequency` = " . $data[32+$offset] . ",`center_frequency` = " . $center_freq_sum . ",`news_exam` = " . ($data[43+$offset] == "N" ? 1 : 0);

		if($update_all){
			// サブ用語情報の更新
			$entity->setSubTerm($data[31+$offset]);

			if($data[44+$offset] == ""){
				$entity->setDelimiter(0);
				$delimiter = 0;
			}elseif($data[44+$offset] == "と"){
				$entity->setDelimiter(1);
				$delimiter = 1;
			}elseif($data[44+$offset] == "，"){
				$entity->setDelimiter(2);
				$delimiter = 2;
			}elseif($data[44+$offset] == "・"){
				$entity->setDelimiter(3);
				$delimiter = 3;
			}elseif($data[44+$offset] == "／"){
				$entity->setDelimiter(4);
				$delimiter = 4;
			}elseif($data[44+$offset] == "（"){
				$entity->setDelimiter(5);
				$delimiter = 5;
			}elseif($data[44+$offset] == "）"){
				$entity->setDelimiter(6);
				$delimiter = 6;
			}elseif($data[44+$offset] == "）と"){
				$entity->setDelimiter(8);
				$delimiter = 8;
			}

			$entity->setIndexAddLetter($data[45+$offset]);
			$entity->setIndexKana($data[46+$offset]);

			if($update_mode == 'new'){
				$entity->setMainTermId(intval(substr($data[0], 1)));
				$entity->setRedLetter(0);
				$entity->setNombre(0);
				$entity->setKanaExistFlag(0);
				$em->persist($entity);
				$em->flush();
			}else{
				$sql .= ",`sub_term` = '" . $data[31+$offset] . "',`delimiter` = '" . $delimiter . "',`index_add_letter` = '" . $data[45+$offset] . "',`index_kana` = '" . $data[46+$offset] . "'";
			}
		}

		$new_id = $entity->getId();

		// センター頻度未登録の場合
		if(!$entityCenter){
			for ($idx=0;$idx<10;$idx++){
				$entityCenter = new Center();

				$entityCenter->setMainTermId(intval(substr($data[0], 1)));
				$entityCenter->setSubTermId($new_id);
				$entityCenter->setYougoFlag(2);
				$entityCenter->setYear($year+$idx);

				$arr_freq = $this->splitCenterFreq($data[33+$idx+$offset]);
				if(count($arr_freq) == 2){
					$entityCenter->setMainExam(intval($arr_freq[0]));
					$entityCenter->setSubExam(intval($arr_freq[1]));
				}

				$em->persist($entityCenter);
				$em->flush();
			}
		}

		if($update_mode == 'update'){
			$sql .= ",`modify_date` = NOW() WHERE `id` = ".$data[30+$offset]." AND `delete_flag` = 0;";
			fputs($handle, $sql."\n");

			$rtn_id = $data[30+$offset];
		}else{
			$rtn_id = $new_id;
		}

		return $rtn_id;
	}

	private function deleteSubTerm($data,$sub_id,$em,$table,$handle,$handle_center){
		if($table == 'SubTerm'){
			$yougo_flag = 2;
		}elseif($table == 'Synonym'){
			$yougo_flag = 3;
		}else{
			$yougo_flag = 4;
		}

		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:'.$table)->findBy(array(
				'mainTermId' =>intval(substr($data[0], 1)),
				'deleteFlag' => FALSE
		));

		if($entity){
			foreach($entity as $entity_rec){
				if(!in_array($entity_rec->getId(), $sub_id)) {
					//$entity_rec->setDeleteFlag(true);
					//$entity_rec->setModifyDate(new \DateTime());
					//$entity_rec->setDeleteDate(new \DateTime());

					$sql = "UPDATE `".$table."` SET `delete_flag` = 1,`modify_date` = NOW(),`delete_date` = NOW()";
					$sql .= " WHERE `id` = ".$entity_rec->getId().";";
					fputs($handle, $sql."\n");

					$sql = "UPDATE `Center` SET `delete_flag` = 1,`modify_date` = NOW(),`delete_date` = NOW()";
					$sql .= " WHERE `main_term_id` = " . intval(substr($data[0], 1)) . " AND `sub_term_id` = " . $entity_rec->getId() . " AND `yougo_flag` = " . $yougo_flag .";";
					fputs($handle_center, $sql."\n");
				}

				//$em->flush();
			}
		}
	}

	private function updateSynTerm($data,$offset,$em,$update_all,$handle,$handle_center){
		// 用語が設定されていない場合
		if($data[133+$offset] == ""){
			return false;
		}

		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Synonym')->findOneBy(array(
				'id' =>$data[132+$offset],
				'mainTermId' =>intval(substr($data[0], 1)),
				'deleteFlag' => FALSE
		));

		$update_mode = 'update';
		if(!$entity){
			if($data[132+$offset] == ""){
				// 用語IDが設定されていない場合、新規登録
				$entity = new Synonym();
				$update_mode = 'new';
			}else{
				return false;
			}
		}

		// センター頻度の年別データ
		$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
				'mainTermId' => intval(substr($data[0], 1)),
				'subTermId' => $data[132+$offset],
				'yougoFlag' => '3',
				'deleteFlag' => FALSE
		),
		array('year' => 'ASC'));

		$year = 2009;
		$idx = 0;
		$center_freq_sum = 0;
		foreach ($entityCenter as $entityCenterRec){
			if($entityCenterRec->getYear() == ($year+$idx)){
				$arr_freq = $this->splitCenterFreq($data[135+$idx+$offset]);

				if(count($arr_freq) == 2){
					//$entityCenterRec->setMainExam($arr_freq[0]);
					//$entityCenterRec->setSubExam($arr_freq[1]);
					$center_freq_sum += intval($arr_freq[0]) + intval($arr_freq[1]);

					//$em->flush();
					$sql = "UPDATE `Center` SET `main_exam` = " . $arr_freq[0] . ",`sub_exam` = " . $arr_freq[1];
					$sql .= ",`modify_date` = NOW() WHERE `main_term_id` = ".intval(substr($data[0], 1))." AND `sub_term_id` = ".$data[132+$offset]." AND `yougo_flag` = 3 AND `year` = " . ($year+$idx) . " AND `delete_flag` = 0;";
					fputs($handle_center, $sql."\n");

				}
			}
			$idx++;
		}

		// センター頻度未登録の場合
		if(!$entityCenter){
			for ($idx=0;$idx<10;$idx++){
				$arr_freq = $this->splitCenterFreq($data[135+$idx+$offset]);

				if(count($arr_freq) == 2){
					$center_freq_sum += intval($arr_freq[0]) + intval($arr_freq[1]);
				}
			}
		}

		// 同対類用語情報の更新(頻度)
		$entity->setTextFrequency($data[134+$offset]);
		$entity->setCenterFrequency($center_freq_sum);
		$entity->setNewsExam($data[145+$offset] == "N" ? 1 : 0);

		$sql = "UPDATE `Synonym` SET `text_frequency` = " . $data[134+$offset] . ",`center_frequency` = " . $center_freq_sum . ",`news_exam` = " . ($data[145+$offset] == "N" ? 1 : 0);

		if($update_all){
			// 同対類用語情報の更新
			if($data[131+$offset] == "同"){
				$entity->setSynonymId(1);
				$synid = 1;
			}elseif($data[131+$offset] == "対"){
				$entity->setSynonymId(2);
				$synid = 2;
			}else{
				$entity->setSynonymId(3);
				$synid = 3;
			}

			$entity->setTerm($data[133+$offset]);

			if($data[146+$offset] == ""){
				$entity->setDelimiter(0);
				$delimiter = 0;
			}elseif($data[146+$offset] == "　"){
				$entity->setDelimiter(1);
				$delimiter = 1;
			}elseif($data[146+$offset] == "（"){
				$entity->setDelimiter(2);
				$delimiter = 2;
			}elseif($data[146+$offset] == "）"){
				$entity->setDelimiter(3);
				$delimiter = 3;
			}elseif($data[146+$offset] == "改行"){
				$entity->setDelimiter(4);
				$delimiter = 4;
			}elseif($data[146+$offset] == "改行＋（"){
				$entity->setDelimiter(5);
				$delimiter = 5;
			}elseif($data[146+$offset] == "）＋改行"){
				$entity->setDelimiter(6);
				$delimiter = 6;
			}

			$entity->setIndexAddLetter($data[147+$offset]);
			$entity->setIndexKana($data[148+$offset]);

			if($update_mode == 'new'){
				$entity->setMainTermId(intval(substr($data[0], 1)));
				$entity->setRedLetter(0);
				$entity->setNombre(0);
				$em->persist($entity);
				$em->flush();
			}else{
				$sql .= ",`synonym_id` = " . $synid . ",`term` = '" . $data[133+$offset] . "',`delimiter` = '" . $delimiter . "',`index_add_letter` = '" . $data[147+$offset] . "',`index_kana` = '" . $data[148+$offset] . "'";
			}
		}

		$new_id = $entity->getId();

		// センター頻度未登録の場合
		if(!$entityCenter){
			for ($idx=0;$idx<10;$idx++){
				$entityCenter = new Center();

				$entityCenter->setMainTermId(intval(substr($data[0], 1)));
				$entityCenter->setSubTermId($new_id);
				$entityCenter->setYougoFlag(3);
				$entityCenter->setYear($year+$idx);

				$arr_freq = $this->splitCenterFreq($data[135+$idx+$offset]);

				if(count($arr_freq) == 2){
					$entityCenter->setMainExam(intval($arr_freq[0]));
					$entityCenter->setSubExam(intval($arr_freq[1]));
				}

				$em->persist($entityCenter);
				$em->flush();
			}
		}

		if($update_mode == 'update'){
			$sql .= ",`modify_date` = NOW() WHERE `id` = ".$data[132+$offset]." AND `delete_flag` = 0;";
			fputs($handle, $sql."\n");

			$rtn_id = $data[132+$offset];
		}else{
			$rtn_id = $new_id;
		}

		return $rtn_id;
	}

	private function updateExpTerm($data,$em,$update_all,$handle,$handle_center){
		// 用語が設定されていない場合
		if($data[117] == ""){
			return false;
		}

		$exp = [];
		$exp['exp_id'] = explode(";",$data[116]);
		$exp['exp_index_kana'] = explode(";",$data[117]);
		$exp['exp_term'] = explode(";",$data[118]);
		$exp['exp_text_frequency'] = explode(";",$data[119]);

		$idx = 0;
		for($i=2009;$i<=2018;$i++){
			$exp['exp_center_frequency'.$i] = explode(";",$data[120+$idx]);
			$idx++;
		}

		$exp['exp_news_exam'] = explode(";",$data[130]);

		$exp_term = [];
		if(preg_match_all('/《c_SAK》(.*?)《\/c_SAK》/u', $data[115], $match_data, PREG_SET_ORDER)){
			foreach($match_data as $main_explain_ele){
				array_push($exp_term, $main_explain_ele[1]);
			}
		}

		$elem = 0;
		$rtn_exp_id = [];
		foreach($exp['exp_index_kana'] as $exp_id_ele){
			if($data[116] == ""){
				$exp_id = "";
			}else{
				$exp_id = $exp['exp_id'][$elem];
			}

			$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:ExplainIndex')->findOneBy(array(
					'id' =>$exp_id,
					'mainTermId' =>intval(substr($data[0], 1)),
					'deleteFlag' => FALSE
			));

			$update_mode = 'update';
			if(!$entity){
				if($exp_id == ""){
					// 用語IDが設定されていない場合、新規登録
					$entity = new ExplainIndex();
					$update_mode = 'new';
				}else{
					continue;
				}
			}

			// センター頻度の年別データ
			$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
					'mainTermId' => intval(substr($data[0], 1)),
					'subTermId' => intval($exp_id),
					'yougoFlag' => '4',
					'deleteFlag' => FALSE
			),
					array('year' => 'ASC'));

			$year = 2009;
			$idx = 0;
			$center_freq_sum = 0;
			foreach ($entityCenter as $entityCenterRec){
				if($entityCenterRec->getYear() == ($year+$idx)){
					$arr_freq = $this->splitCenterFreq($exp['exp_center_frequency'.($year+$idx)][$elem]);

					if(count($arr_freq) == 2){
						//$entityCenterRec->setMainExam(intval($arr_freq[0]));
						//$entityCenterRec->setSubExam(intval($arr_freq[1]));
						$center_freq_sum += intval($arr_freq[0]) + intval($arr_freq[1]);

						//$em->flush();
						$sql = "UPDATE `Center` SET `main_exam` = " . $arr_freq[0] . ",`sub_exam` = " . $arr_freq[1];
						$sql .= ",`modify_date` = NOW() WHERE `main_term_id` = ".intval(substr($data[0], 1))." AND `sub_term_id` = ".intval($exp_id)." AND `yougo_flag` = 4 AND `year` = " . ($year+$idx) . " AND `delete_flag` = 0;";
						fputs($handle_center, $sql."\n");
					}
				}
				$idx++;
			}

			// センター頻度未登録の場合
			if(!$entityCenter){
				for ($idx=0;$idx<10;$idx++){
					$arr_freq = $this->splitCenterFreq($exp['exp_center_frequency'.($year+$idx)][$elem]);

					if(count($arr_freq) == 2){
						$center_freq_sum += intval($arr_freq[0]) + intval($arr_freq[1]);
					}
				}
			}

			// 解説内用語情報の更新(頻度)
			$entity->setTextFrequency($exp['exp_text_frequency'][$elem]);
			$entity->setCenterFrequency($center_freq_sum);
			$entity->setNewsExam($exp['exp_news_exam'][$elem] == "N" ? 1 : 0);

			$sql = "UPDATE `ExplainIndex` SET `text_frequency` = " . $exp['exp_text_frequency'][$elem] . ",`center_frequency` = " . $center_freq_sum . ",`news_exam` = " . ($exp['exp_news_exam'][$elem] == "N" ? 1 : 0);

			if($update_all){
				// 解説内用語情報の更新
				$entity->setIndexAddLetter($exp['exp_term'][$elem]);
				$entity->setIndexKana($exp['exp_index_kana'][$elem]);

				if($update_mode == 'new'){
					$entity->setIndexTerm($exp_term[$elem]);
					$entity->setMainTermId(intval(substr($data[0], 1)));
					$entity->setNombre(0);
					$em->persist($entity);
					$em->flush();

				}else{
					$sql .= ",`index_add_letter` = '" . $exp['exp_term'][$elem] . "',`index_kana` = '" . $exp['exp_index_kana'][$elem] . "'";
				}
			}

			$new_id = $entity->getId();
			array_push($rtn_exp_id,$new_id);

			// センター頻度未登録の場合
			if(!$entityCenter){
				for ($idx=0;$idx<10;$idx++){
					$entityCenter = new Center();

					$entityCenter->setMainTermId(intval(substr($data[0], 1)));
					$entityCenter->setSubTermId($new_id);
					$entityCenter->setYougoFlag(4);
					$entityCenter->setYear($year+$idx);

					$arr_freq = $this->splitCenterFreq($exp['exp_center_frequency'.($year+$idx)][$elem]);

					if(count($arr_freq) == 2){
						$entityCenter->setMainExam(intval($arr_freq[0]));
						$entityCenter->setSubExam(intval($arr_freq[1]));
					}

					$em->persist($entityCenter);
					$em->flush();
				}
			}

			if($update_mode == 'update'){
				$sql .= ",`modify_date` = NOW() WHERE `id` = ".intval($exp_id)." AND `delete_flag` = 0;";
				fputs($handle, $sql."\n");
			}

			$elem++;
		}

		return $rtn_exp_id;

	}

	private function updateRefTerm($data,$offset,$em){

		if($data[329+$offset] == ""){
			return false;
		}

		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Refer')->findOneBy(array(
				'mainTermId' =>intval(substr($data[0], 1)),
				'referTermId' =>intval(substr($data[329+$offset], 1)),
				'deleteFlag' => FALSE
		));

		$update_mode = 'update';
		if(!$entity){
			$entity = new Refer();
			$update_mode = 'new';
		}

		if($update_mode == 'new'){
			$entity->setMainTermId(intval(substr($data[0], 1)));
			$entity->setReferTermId(intval(substr($data[329+$offset], 1)));
			$entity->setNombre(0);
			$em->persist($entity);
			$em->flush();
		}

		return intval(substr($data[329+$offset],1));

	}

	private function deleteRefTerm($data,$sub_id,$em,$table,$handle){
		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:'.$table)->findBy(array(
				'mainTermId' =>intval(substr($data[0], 1)),
				'deleteFlag' => FALSE
		));

		if($entity){
			foreach($entity as $entity_rec){
				if(!in_array($entity_rec->getReferTermId(), $sub_id)) {
					//$entity_rec->setDeleteFlag(true);
					//$entity_rec->setModifyDate(new \DateTime());
					//$entity_rec->setDeleteDate(new \DateTime());

					$sql = "UPDATE ".$table." SET `delete_flag` = 1,`modify_date` = NOW(),`delete_date` = NOW()";
					$sql .= " WHERE `main_term_id` = ". intval(substr($data[0], 1)) . " AND `refer_term_id` = ".$entity_rec->getReferTermId().";";
					fputs($handle, $sql."\n");
				}

				//$em->flush();
			}
		}
	}

	private function splitCenterFreq($value){
		if(preg_match('/(\d+)月(\d+)日/', $value, $matches)){
			// Excelの仕様で日付と認識してしまうので判別する
			$arr_freq = [];
			$arr_freq[0] = $matches[1];
			$arr_freq[1] = $matches[2];
		}else{
			$arr_freq = explode("/",$value);
		}
		return $arr_freq;
	}

	private function importSQLFile($handleMain,$handleExplain,$handleSub,$handleSyn,$handleRefer,$handleCenter){
		// SQLファイルのインポート
		$command_import = 'mysql -h'. $this->container->getParameter('database_host') . ' -u'. $this->container->getParameter('database_user') . ' -p' . $this->container->getParameter('database_password') . ' ' . $this->container->getParameter('database_name') . ' < ';
		$this->get('logger')->info($command_import . $handleMain);

		$return_txt = exec($command_import . $handleMain,$output,$retrun_ver);
		$this->get('logger')->info("***用語インポート:MAINTERM***" . $return_txt . ':' . $retrun_ver);

		if($retrun_ver != 0){
			return false;
		}

		$return_txt = exec($command_import . $handleExplain,$output,$retrun_ver);
		$this->get('logger')->info("***用語インポート:EXPLAIN***" . $return_txt . ':' . $retrun_ver);

		if($retrun_ver != 0){
			return false;
		}

		$return_txt = exec($command_import . $handleSub,$output,$retrun_ver);
		$this->get('logger')->info("***用語インポート:SUBTERM***" . $return_txt . ':' . $retrun_ver);

		if($retrun_ver != 0){
			return false;
		}

		$return_txt = exec($command_import . $handleSyn,$output,$retrun_ver);
		$this->get('logger')->info("***用語インポート:SYNONYM***" . $return_txt . ':' . $retrun_ver);

		if($retrun_ver != 0){
			return false;
		}

		$return_txt = exec($command_import . $handleRefer,$output,$retrun_ver);
		$this->get('logger')->info("***用語インポート:REFER***" . $return_txt . ':' . $retrun_ver);

		if($retrun_ver != 0){
			return false;
		}

		$return_txt = exec($command_import . $handleCenter,$output,$retrun_ver);
		$this->get('logger')->info("***用語インポート:CENTER***" . $return_txt . ':' . $retrun_ver);

		if($retrun_ver != 0){
			return false;
		}else{
			return true;
		}

	}

	/**
	 * @Route("/log/download", name="client.log.download")
	 * @Method("POST|GET")
	 */
	public function logDownloadAction(Request $request){
		return $this->DownloadLog("import_term.log","ノンブル取込みエラー");
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

	private function registerUploadHistory($em, $versionId,$userid,$files,$update_all){
		try {
			foreach($files as $ele_file){
				$em->getConnection()->beginTransaction();
				$entity = new Upload();
				$entity->setVersionId($versionId);
				$entity->setUserId($userid);
				$entity->setFileName($ele_file['name']);
				$entity->setCreateDate(new \DateTime());
				$entity->setContents($update_all ? "全項目上書き" : "頻度上書き");

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