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
use CCK\CommonBundle\Entity\CSVPreset;

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
			'index_add_letter',
			'index_kana',
			'index_original_kana',
			'index_original',
			'index_abbreviation',
			'nombre',
			'nombre_bold',
			'term_explain',
			'exp_id',
			'exp_term',
			'exp_text_frequency',
			'exp_center_frequency',
			'exp_news_exam',
			'exp_index_kana',
			'exp_index_add_letter',
			'exp_nombre',
			'sub_id',
			'sub_term',
			'sub_red_letter',
			'sub_text_frequency',
			'sub_center_frequency',
			'sub_news_exam',
			'sub_delimiter',
			'sub_index_add_letter',
			'sub_index_kana',
			'sub_nombre',
			'syn_id',
			'syn_synonym_id',
			'syn_term',
			'syn_red_letter',
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
	 * @var session key
	 */
	const SES_CSV_FIELD_ALERT_KEY = "ses_csv_field_alert_key";

	/**
	 * @Route("/csv/download/index/{status}", name="client.csv.export")
	 * @Template()
	 */
	public function indexAction(Request $request,$status) {
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

		// フィールド選択出力リスト表示ように項目名の変更
		$header_wk = str_replace('さくいん', '索引', $header);
		$header_wk = str_replace('仮名', '', $header_wk);
		unset($header_wk['header_position']);
		array_splice($header_wk, 2, 0, array('var_name'=>"版"));

		foreach($header_wk as $key => $value) {
			if($key === 0){
				$header_selectable['var_name'] = $header_wk[$key];
			} else {
				$header_selectable[$key] = $header_wk[$key];
			}
		}

		$preset = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:CSVPreset')->findBy(array(
				'user_id' => $this->getUser()->getUserId(),
				'deleteFlag' => FALSE
		));

		$message_honmon = '';
		$message_sakuin = '';
		$message_preset = '';
		$message_maemikaeshi = '';
		if($status == 204){
			$message_honmon = "用語の登録がありません。教科・版を確認してください。";
		}elseif($status == 205){
			$message_sakuin = "用語の登録がありません。教科・版を確認してください。";
		}elseif($status == 206){
			$message_preset = "用語の登録がありません。教科・版を確認してください。";
		}elseif($status == 207){
			$message_maemikaeshi = "用語の登録がありません。教科・版を確認してください。";
		}elseif($status == 210){
			$message_honmon = "IDが付加されていない用語があります。ログテキストをご確認ください。";
		}elseif($status == 211){
			$message_sakuin = "IDが付加されていない用語があります。ログテキストをご確認ください。";
		}elseif($status == 212){
			$message_maemikaeshi = "IDが付加されていない用語があります。ログテキストをご確認ください。";
		}elseif($status == 213){
			$message_preset = "IDが付加されていない用語があります。ログテキストをご確認ください。";
		}elseif(($status > 250)&&($status < 260)){

			$arr_alert_list = $session->get(self::SES_CSV_FIELD_ALERT_KEY);
			$txt_alert = "";
			foreach ($arr_alert_list as $ele_alert_list){
				$txt_alert .= $ele_alert_list[0].$ele_alert_list[1]."　主用語：".$ele_alert_list[2]."　同対類語：".$ele_alert_list[3]."の同体類マークがブランクです\n\r";
			}

			if($status == 251){
				$message_honmon = $txt_alert;
			}elseif($status == 252){
				$message_sakuin = $txt_alert;
			}elseif($status == 253){
				$message_preset = $txt_alert;
			}else{
				$message_maemikaeshi = $txt_alert;
			}

			$this->sessionRemove($request);
		}

		return array(
				'currentUser' => ['user_id' => $this->getUser()->getUserId(), 'name' => $this->getUser()->getName()],
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
				'hen_list' => array(),
				'sho_list' => array(),
				'field_list' => $header_selectable,
				'preset_list' => $preset,
				'message_honmon' => $message_honmon,
				'message_sakuin' => $message_sakuin,
				'message_preset' => $message_preset,
				'message_maemikaeshi' => $message_maemikaeshi,
				'status' => $status,
		);
	}

	/**
	 * @Route("/typesetting/download", name="client.typesetting.download")
	 * @Method("POST|GET")
	 */
	public function typesettingDownloadAction(Request $request){
		// session
		$session = $request->getSession();

		$tmpFilePath = tempnam(sys_get_temp_dir(), 'tmp');

		$em = $this->getDoctrine()->getManager();

		// 教科名の取得
		$curriculumId = $request->query->get('curriculum');

		// 本文/索引の区分
		$type = $request->query->get('type');
		if($type == '0'){
			$type_name = '本文';
			$versionId = $request->query->get('version');
		}elseif($type == '1'){
			$type_name = '索引';
			$versionId = $request->query->get('version2');
		}elseif($type == '3'){
			$type_name = '前見返し';
			$versionId = $request->query->get('version4');
		}else{
			$type_name = $request->query->get('preset');
			$versionId = $request->query->get('version');
		}

		$entityCurriculum = $em->getRepository('CCKCommonBundle:Curriculum')->getCurriculumVersionList($versionId);

		$cur_name = '';
		if($entityCurriculum){
			$cur_name = $entityCurriculum[0]['cur_name'] . '_' . $entityCurriculum[0]['name'];
		}

		// 見出しID(編、章)
		$hen = $request->query->get('hen');
		$sho = $request->query->get('sho');

		// 用語ID
		$term_id = $request->query->get('term_id');
		if($term_id){
			$type_name = 'M'.str_pad($term_id, 6, 0, STR_PAD_LEFT);
		}

		// ファイル名の生成
		$outFileName = $cur_name . '_' . $type_name . '_' . date('YmdHis') . ".csv";

		$path = $this->container->getParameter('archive')['dir_path'];
		$webpath = $request->getSchemeAndHttpHost() . '/' . $this->container->getParameter('archive')['link'];

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

			$body_list = $this->constructManuscriptCSV($term_id, $request, $entity, $outFileName, $entityVer, $arr_err_list, $status_err);
		}else{
			$body_list = false;
		}

		$status = 0;
		$this->get('logger')->error("***body_list***".serialize($body_list)."***status_err***".$status_err);
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
				}elseif ($type == '2'){
					$status = 213;
				}elseif ($type == '3'){
					$status = 212;
				}
			}

			return $this->redirect($this->generateUrl('client.csv.export', array('status' => $status)));
		}

		// ヘッダー
		if($type == '2'){
			// 汎用CSV
			$header = $this->encoding(explode(",", $request->query->get('generic_field')), $request);
		}else{
			// 本文・索引組版
			$header = $this->encoding($this->generateHeader($entity[0]), $request);
		}

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

		// ダウンロード完了時にローディング表示を消す
		setcookie('downloaded','complete',0,'/');

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
	private function constructManuscriptCSV($genkoId, $request, $entity, $outFileName, $entityVer, &$arr_err_list, &$status_err) {
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
			$body = $this->encoding($this->generateBody($mainTermRec,$entity_exp, $entity_sub, $entity_syn, $entity_ref, $type, $generic_value, $entityVer, $arr_err_list, $status_err), $request);
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
		$result['exp_id'] = $translator->trans('csv.term.exp_id');
		$result['exp_term'] = $translator->trans('csv.term.exp_term');
		$result['exp_text_frequency'] = $translator->trans('csv.term.exp_text_frequency');
		$result['exp_center_frequency'] = $translator->trans('csv.term.exp_center_frequency');
		$result['exp_news_exam'] = $translator->trans('csv.term.exp_news_exam');
		$result['exp_index_kana'] = $translator->trans('csv.term.exp_index_kana');
		$result['exp_index_add_letter'] = $translator->trans('csv.term.exp_index_add_letter');
		$result['exp_nombre'] = $translator->trans('csv.term.exp_nombre');

		// サブ用語
		$result['sub_id'] = $translator->trans('csv.term.sub_id');
		$result['sub_term'] = $translator->trans('csv.term.sub_term');
		$result['sub_red_letter'] = $translator->trans('csv.term.sub_red_letter');
		$result['sub_kana'] = $translator->trans('csv.term.sub_kana');
		$result['sub_kana_exist_flag'] = $translator->trans('csv.term.sub_kana_exist_flag');
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
	private function generateBody($main, $expterm, $subterm, $synterm, $refterm, $type, $generic, $entityVer ,&$arr_err_list ,&$status_err){
		$body = [];
		$result = [];

		$em = $this->getDoctrine()->getManager();
		$translator = $this->get('translator');

		$search_newline = '/\r\n|\r|\n/';

		// 主用語
		$this->replaceMainField($main,$entityVer);

		$main['nombre'] = (($type != '0') ? $main['nombre'] : '');
		$main['illust_nombre'] = (($type != '0') ? $main['illust_nombre'] : '');

		// 解説内索引用語
		$exp = [];
		$exp['exp_id'] = "";
		$exp['exp_term'] = "";
		$exp['exp_text_frequency'] = "";
		$exp['exp_center_frequency'] = "";
		$exp['exp_news_exam'] = "";
		$exp['exp_index_kana'] = "";
		$exp['exp_index_add_letter'] = "";
		$exp['exp_nombre'] = "";

		$arr_exp_indexTerm = [];
		if($expterm){
			$exp_term_text = [];
			// 解説内のタグ出現順にソート
			if(preg_match_all('/《c_SAK》(.*?)《\/c_SAK》/u', $main['term_explain'], $match_data, PREG_SET_ORDER)){
				foreach($match_data as $main_explain_ele){
					array_push($exp_term_text, $main_explain_ele[1]);
				}
			}

			$wk_exp = [];
			foreach ($exp_term_text as $exp_term_ele) {
				foreach ($expterm as $exptermRec) {
					if($exp_term_ele == $exptermRec['indexTerm']){
						array_push($wk_exp,$exptermRec);
						break;
					}
				}
			}
			$expterm = $wk_exp;

			foreach ($expterm as $exptermRec) {
				$exptermRec['id'] = 'K'.str_pad($exptermRec['id'], 6, 0, STR_PAD_LEFT);
				$this->replaceExpField($exptermRec,$entityVer);

				$exp['exp_id'] .= $exptermRec['id'] . '\v';
				$exp['exp_term'] .= $exptermRec['indexTerm'] . '\v';
				$exp['exp_text_frequency'] .= $exptermRec['textFrequency'] . '\v';
				$exp['exp_center_frequency'] .= $exptermRec['centerFrequency'] . '\v';
				$exp['exp_news_exam'] .= $exptermRec['newsExam'] . '\v';
				$exp['exp_index_kana'] .= $exptermRec['indexKana'] . '\v';
				$exp['exp_index_add_letter'] .= $exptermRec['indexAddLetter'] . '\v';
				$exp['exp_nombre'] .= (($type != '0') ? $exptermRec['nombre'] : '') . '\v';

				array_push($arr_exp_indexTerm, $exptermRec['indexTerm']);
			}

			foreach ($exp as $key => $val) {
				$exp[$key] = mb_substr($val,0,mb_strlen($val)-2);
			}
		}
		// 解説内用語存在チェック
		$this->isExistsExplainTerm($main['term_id'],$main['main_term'],$main['term_explain'],$arr_exp_indexTerm,$status_err);


		// サブ用語
		$sub = [];
		$sub['sub_id'] = "";
		$sub['sub_term'] = "";
		$sub['sub_red_letter'] = "";
		$sub['sub_kana'] = "";
		$sub['sub_kana_exist_flag'] = "";
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
				$this->replaceSubField($subtermRec,$entityVer);

				$sub['sub_id'] .= $subtermRec['id'] . '\v';
				$sub['sub_term'] .= $subtermRec['sub_term'] . '\v';
				$sub['sub_red_letter'] .= $subtermRec['red_letter'] . '\v';
				$sub['sub_kana'] .= $subtermRec['kana'] . '\v';
				$sub['sub_kana_exist_flag'] .= $subtermRec['kana_exist_flag'] . '\v';
				$sub['sub_text_frequency'] .= $subtermRec['text_frequency'] . '\v';
				$sub['sub_center_frequency'] .= $subtermRec['center_frequency'] . '\v';
				$sub['sub_news_exam'] .= $subtermRec['news_exam'] . '\v';
				$sub['sub_delimiter'] .= $subtermRec['delimiter'] . '\v';
				$sub['sub_delimiter_kana'] .= $subtermRec['delimiter_kana'] . '\v';
				$sub['sub_index_add_letter'] .= $subtermRec['index_add_letter'] . '\v';
				$sub['sub_index_kana'] .= $subtermRec['index_kana'] . '\v';
				$sub['sub_nombre'] .= (($type != '0') ? $subtermRec['nombre'] : '') . '\v';
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
				$this->replaceSynField($syntermRec,$entityVer,$main,$arr_err_list,$status_err);

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
				$syn['syn_nombre'] .= (($type != '0') ? $syntermRec['nombre'] : '') . '\v';
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
				$reftermRec['refer_term_id'] = 'M'.str_pad($reftermRec['refer_term_id'], 6, 0, STR_PAD_LEFT);
				$this->getHeaderName($reftermRec);

				$ref['ref_hen'] .= $reftermRec['hen'] . '\v';
				$ref['ref_sho'] .= $reftermRec['sho'] . '\v';
				$ref['ref_dai'] .= $reftermRec['dai'] . '\v';
				$ref['ref_chu'] .= $reftermRec['chu'] . '\v';
				$ref['ref_ko'] .= $reftermRec['ko'] . '\v';
				$ref['ref_refer_term_id'] .= $reftermRec['refer_term_id'] . '\v';
				$ref['ref_main_term'] .= preg_replace('/【.*?】/', '', $reftermRec['main_term']) . '\v';
				$ref['ref_nombre'] .= (($type != '0') ? $reftermRec['nombre'] : '') . '\v';
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

	private function replaceMainField(&$main,$entityVer){
		$main['header_position'] = "";
		// 主用語　見出し名称の取得
		$this->getHeaderName($main);

		$main['term_id'] = 'M'.str_pad($main['term_id'], 6, 0, STR_PAD_LEFT);

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
			$main['text_frequency'] = 'A';
		}elseif(($main['text_frequency'] >= $entityVer->getRankB())&&($main['text_frequency'] <= ($entityVer->getRankA()-1))){
			$main['text_frequency'] = 'B';
		}elseif(($main['text_frequency'] >= 1)&&($main['text_frequency'] <= ($entityVer->getRankB()-1))){
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

		if($main['nombre_bold'] == '1'){
			$main['nombre_bold'] = '＊';
		}else{
			$main['nombre_bold'] = '';
		}

		if($main['illust_caption'] != ''){
			$main['illust_caption'] .= "〔図〕";
		}
	}

	private function replaceExpField(&$exp,$entityVer){
		if($exp['textFrequency'] >= $entityVer->getRankA()){
			$exp['textFrequency'] = 'A';
		}elseif(($exp['textFrequency'] >= $entityVer->getRankB())&&($exp['textFrequency'] <= ($entityVer->getRankA()-1))){
			$exp['textFrequency'] = 'B';
		}elseif(($exp['textFrequency'] >= 1)&&($exp['textFrequency'] <= ($entityVer->getRankB()-1))){
			$exp['textFrequency'] = 'C';
		}else{
			$exp['textFrequency'] = '';
		}

		if($exp['centerFrequency'] == 0){
			$exp['centerFrequency'] = '';
		}

		if($exp['newsExam'] == '1'){
			$exp['newsExam'] = 'N';
		}else{
			$exp['newsExam'] = '';
		}
	}

	private function replaceSubField(&$sub,$entityVer){
		$sub['id'] = 'S'.str_pad($sub['id'], 6, 0, STR_PAD_LEFT);

		if($sub['center_frequency'] > 4){
			$sub['red_letter'] = '●';
		}else{
			$sub['red_letter'] = '';
		}

		if($sub['kana_exist_flag'] == '1'){
			$sub['kana_exist_flag'] = '●';
		}else{
			$sub['kana_exist_flag'] = '';
		}

		if($sub['text_frequency'] >= $entityVer->getRankA()){
			$sub['text_frequency'] = 'A';
		}elseif(($sub['text_frequency'] >= $entityVer->getRankB())&&($sub['text_frequency'] <= ($entityVer->getRankA()-1))){
			$sub['text_frequency'] = 'B';
		}elseif(($sub['text_frequency'] >= 1)&&($sub['text_frequency'] <= ($entityVer->getRankB()-1))){
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

	private function replaceSynField(&$syn,$entityVer,$main,&$arr_err_list,&$status_err){
		$syn['id'] = 'D'.str_pad($syn['id'], 6, 0, STR_PAD_LEFT);

		if($syn['synonym_id'] == '1'){
			$syn['synonym_id'] = '同';
		}elseif($syn['synonym_id'] == '2'){
			$syn['synonym_id'] = '対';
		}elseif($syn['synonym_id'] == '3'){
			$syn['synonym_id'] = '類';
		}else{

			$this->OutputLog("ERROR", "id_check.log", "同対類用語アイコンが空欄です。主用語ID:".$main['term_id'].",主用語:".$main['main_term'].",同対類用語:".$syn['term']);
			$status_err = 210;

			if(mb_strpos($main['hen'], "編") !== false){
				$hen = mb_substr($main['hen'], 0, mb_strpos($main['hen'], "編")+1);
			}else{
				$hen = $main['hen']."　";
			}
			if(mb_strpos($main['sho'], "章") !== false){
				$sho = mb_substr($main['sho'], 0, mb_strpos($main['sho'], "章")+1);
			}else{
				$sho = $main['sho'];
			}
			array_push($arr_err_list,array($hen,$sho,$main['main_term'],$syn['term']));
		}

		if($syn['center_frequency'] > 4){
			$syn['red_letter'] = '●';
		}else{
			$syn['red_letter'] = '';
		}

		if($syn['text_frequency'] >= $entityVer->getRankA()){
			$syn['text_frequency'] = 'A';
		}elseif(($syn['text_frequency'] >= $entityVer->getRankB())&&($syn['text_frequency'] <= ($entityVer->getRankA()-1))){
			$syn['text_frequency'] = 'B';
		}elseif(($syn['text_frequency'] >= 1)&&($syn['text_frequency'] <= ($entityVer->getRankB()-1))){
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

	private function isExistsExplainTerm($main_term_id,$main_term,$main_explain,$explain_term,&$status_err){
		if(preg_match_all('/《c_SAK》(.*?)《\/c_SAK》/u', $main_explain, $match_data, PREG_SET_ORDER)){
			foreach($match_data as $main_explain_ele){
				if(!in_array($main_explain_ele[1], $explain_term)){
					$this->OutputLog("ERROR", "id_check.log", "解説内さくいん用語IDが空欄です。主用語ID:".$main_term_id.",主用語:".$main_term.",解説内さくいん用語:".$main_explain_ele[1]);
					$status_err = 210;
				}
			}
		}
	}

	/**
	 * @Route("/csv/preset/update", name="client.csv.preset.update")
	 * @Method("POST|GET")
	 * @Template("CCKClientBundle:download:index.html.twig")
	 */
	public function updatePresetAction(Request $request){
		$session = $request->getSession();
		if(!$request->request->has('preset_name')){
			$response = new JsonResponse(array("return_cd" => false, "name" => 'parameter err'));
			return $response;
		}

		$preset_name = $request->request->get('preset_name');
		$preset_field = $request->request->get('preset_field');

		// 件数チェック
		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:CSVPreset')->findBy(array(
				'user_id' => $this->getUser()->getUserId(),
				'deleteFlag' => FALSE
		));
		if(count($entity) > 9){
			$response = new JsonResponse(array("return_cd" => false, "name" => 'preset saved count over'));
			return $response;
		}

		// プリセット名称重複チェック
		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:CSVPreset')->findOneBy(array(
				'presetName' => $preset_name,
				'deleteFlag' => FALSE
		));
		if($entity){
			$response = new JsonResponse(array("return_cd" => false, "name" => 'preset saved duplicate'));
			return $response;
		}

		// transaction
		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		try {
			$preset = new CSVPreset();

			$preset->setUserId($this->getUser()->getUserId());
			$preset->setPresetName($preset_name);
			$preset->setFieldName($preset_field);

			$em->persist($preset);
			$em->flush();
			$em->getConnection()->commit();

		} catch (\Exception $e){
			// もし、DBに登録失敗した場合rollbackする
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			$response = new JsonResponse(array("return_cd" => false, "name" => 'DB error'));
			return $response;
		}

		$response = new JsonResponse(array("return_cd" => true, "name" => ''));
		return $response;
	}

	/**
	 * @Route("/csv/preset/delete", name="client.csv.preset.delete")
	 * @Method("POST|GET")
	 * @Template("CCKClientBundle:download:index.html.twig")
	 */
	public function deletePresetAction(Request $request){
		$session = $request->getSession();
		if(!$request->request->has('id')){
			$response = new JsonResponse(array("return_cd" => false, "name" => 'parameter err'));
			return $response;
		}

		$id = $request->request->get('id');

		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:CSVPreset')->findOneBy(array(
				'id' =>$id,
				'deleteFlag' => FALSE
		));

		if(!$entity){
			$response = new JsonResponse(array("return_cd" => false, "name" => 'exist err'));
			return $response;
		}

		// transaction
		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		try {
			$entity->setDeleteFlag(true);
			$entity->setModifyDate(new \DateTime());
			$entity->setDeleteDate(new \DateTime());

			$em->flush();
			$em->getConnection()->commit();

		} catch (\Exception $e){
			// もし、DBに登録失敗した場合rollbackする
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			$response = new JsonResponse(array("return_cd" => false, "name" => 'DB error'));
			return $response;
		}

		$response = new JsonResponse(array("return_cd" => true, "name" => ''));
		return $response;
	}

	/**
	 * @Route("/log/id/download", name="client.log.id.download")
	 * @Method("POST|GET")
	 */
	public function logIdDownloadAction(Request $request){
		return $this->DownloadLog("id_check.log","IDチェックエラー");
	}

	/**
	 * session data remove
	 */
	private function sessionRemove($request){
		$session = $request->getSession();
		$session->remove(self::SES_CSV_FIELD_ALERT_KEY);
	}
}