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
use Symfony\Component\Config\Definition\Exception\Exception;

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
			'center_frequency1',
			'center_frequency2',
			'center_frequency3',
			'center_frequency4',
			'center_frequency5',
			'center_frequency6',
			'center_frequency7',
			'center_frequency8',
			'center_frequency9',
			'center_frequency10',
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
			'subxxx_center_frequency1',
			'subxxx_center_frequency2',
			'subxxx_center_frequency3',
			'subxxx_center_frequency4',
			'subxxx_center_frequency5',
			'subxxx_center_frequency6',
			'subxxx_center_frequency7',
			'subxxx_center_frequency8',
			'subxxx_center_frequency9',
			'subxxx_center_frequency10',
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
			'exp_center_frequency1',
			'exp_center_frequency2',
			'exp_center_frequency3',
			'exp_center_frequency4',
			'exp_center_frequency5',
			'exp_center_frequency6',
			'exp_center_frequency7',
			'exp_center_frequency8',
			'exp_center_frequency9',
			'exp_center_frequency10',
			'exp_news_exam'
	];

	/* 11件 -NM */
	private $termCsvHeaderSyn = [
			'synxxx_synonym_id',
			'synxxx_id',
			'synxxx_term',
			'synxxx_text_frequency',
			'synxxx_center_frequency1',
			'synxxx_center_frequency2',
			'synxxx_center_frequency3',
			'synxxx_center_frequency4',
			'synxxx_center_frequency5',
			'synxxx_center_frequency6',
			'synxxx_center_frequency7',
			'synxxx_center_frequency8',
			'synxxx_center_frequency9',
			'synxxx_center_frequency10',
			'synxxx_news_exam',
			'synxxx_delimiter',
			'synxxx_index_add_letter',
			'synxxx_index_kana'
	];

	/* 3件 -NS */
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
	 * @var session key
	 */
	const SES_REQUIRED_KEY = "ses_required_key";
	const SES_DIVISION_KEY = "ses_division_key";

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

		$status_download = "";
		$status_message = "";
		$status_update = "";
		$status_new = "";
		if($status == 200){
			$status_message = "csvファイルの取込みが完了しました。";
		}elseif($status == 201){
			$status_message = "ファイル形式を確認してください。CSVファイルのみ取込可能です。";
		}elseif($status == 202){
			$status_message = "テキストファイルのエンコード形式を確認してください。取込可能な形式はUTF-8です。";
		}elseif($status == 203){
			$status_message = "教科・版が異なっています。";
		}elseif($status == 204){
			$status_message = "項目数が異なっています。項目数：337";
		}elseif($status == 205){
			$status_download = "用語の登録がありません。教科・版を確認してください。";
		}elseif(($status >= 206)&&($status <= 215)){
			$status_message =  $session->get(self::SES_REQUIRED_KEY);
		}elseif($status == 'default'){
			$status_message = "";
		}else{
			$status_message = "csvファイルの取込みに失敗しました。ファイルを確認して下さい。";
		}

		if($session->get(self::SES_DIVISION_KEY) == 'update'){
			$status_update = $status_message;
		}else{
			$status_new = $status_message;
		}

		$session->remove(self::SES_REQUIRED_KEY);
		$session->remove(self::SES_DIVISION_KEY);

		$upload_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Upload')->getUploadList("term");

		return array(
				'currentUser' => ['user_id' => $this->getUser()->getUserId(), 'name' => $this->getUser()->getName()],
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
				'return_download' => $status_download,
				'return_upload' => $status_update,
				'return_new' => $status_new,
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
		if($body_list === false){
			$status = 205;
			return $this->redirect($this->generateUrl('client.csv.import.term', array('status' => $status)));
		}

		// ヘッダー
		$header = $this->encoding($this->generateHeader($entity[0],$entityVer), $request);

		// trans service
		$translator = $this->get('translator');

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
	private function generateHeader($header_main,$entityVer){
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

		$cnt = 1;
		for($i = $entityVer->getYear(); $i <= $entityVer->getYear() + 9; $i++){
			$result['center_frequency'.$cnt] = $translator->trans('csv.term.center_frequency').$i;
			$cnt++;
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

			$cnt = 1;
			for($i = $entityVer->getYear(); $i <= $entityVer->getYear() + 9; $i++){
				$result['sub'.$idx.'_center_frequency'.$cnt] = $translator->trans('csv.macro.sub'.$idx.'_center_frequency').$i;
				$cnt++;
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

		$cnt = 1;
		for($i = $entityVer->getYear(); $i <= $entityVer->getYear() + 9; $i++){
			$result['exp_center_frequency'.$cnt] = $translator->trans('csv.term.exp_center_frequency').$i;
			$cnt++;
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

			$cnt = 1;
			for($i = $entityVer->getYear(); $i <= $entityVer->getYear() + 9; $i++){
				$result['syn'.$idx.'_center_frequency'.$cnt] = $translator->trans('csv.macro.syn'.$idx.'_center_frequency').$i;
				$cnt++;
			}

			$result['syn'.$idx.'_center_frequency'] = $translator->trans('csv.macro.syn'.$idx.'_center_frequency');
			$result['syn'.$idx.'_news_exam'] = $translator->trans('csv.macro.syn'.$idx.'_news_exam');
			$result['syn'.$idx.'_delimiter'] = $translator->trans('csv.macro.syn'.$idx.'_delimiter');
			$result['syn'.$idx.'_index_add_letter'] = $translator->trans('csv.macro.syn'.$idx.'_index_add_letter');
			$result['syn'.$idx.'_index_kana'] = $translator->trans('csv.macro.syn'.$idx.'_index_kana');
		}

		// 指矢印用語
		for($idx=1;$idx<=3;$idx++){
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

			$cnt = 1;
			for($year = $entityVer->getYear(); $year <= $entityVer->getYear()+9; $year++){
				if(isset($subterm[$idx-1]['center_frequency'.$year])){
					$sub['sub'.$idx.'_center_frequency'.$cnt] = (empty($subterm[$idx-1])) ? "" : $subterm[$idx-1]['center_frequency'.$year];
				}else{
					//センター頻度開始年を用語登録後に変更した場合、頻度が取得されない年があるので初期値を設定
					$sub['sub'.$idx.'_center_frequency'.$cnt] = (empty($subterm[$idx-1])) ? "" : "0/0";
				}
				$cnt++;
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

		$cnt = 1;
		for($i = $entityVer->getYear(); $i <= $entityVer->getYear() + 9; $i++){
			$exp['exp_center_frequency'.$i] = "";
			$exp['exp_center_frequency'.$cnt] = "";
			$cnt++;
		}

		$exp['exp_news_exam'] = "";

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
				$this->replaceExpField($exptermRec,$entityVer);

				$exp['exp_id'] .= 'K'.str_pad($exptermRec['id'], 6, 0, STR_PAD_LEFT) . ';';
				$exp['exp_index_kana'] .= $exptermRec['indexKana'] . ';';
				$exp['exp_term'] .= $exptermRec['indexAddLetter'] . ';';
				$exp['exp_text_frequency'] .= $exptermRec['textFrequency'] . ';';

				// センター頻度10年分の取得
				$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
						'mainTermId' => $term_id,
						'subTermId' => $exptermRec['id'],
						'yougoFlag' => '4',
						'deleteFlag' => FALSE
				),
				array('year' => 'ASC'));

				if($entityCenter){
					foreach ($entityCenter as $entityCenterRec){
						if(($entityCenterRec->getYear() >= $entityVer->getYear())&&($entityCenterRec->getYear() <= $entityVer->getYear()+9)){
							$exp['exp_center_frequency'.$entityCenterRec->getYear()] .= $entityCenterRec->getMainExam() . "/" . $entityCenterRec->getSubExam() .";";
						}
					}
				}else{
					for($year = $entityVer->getYear(); $year <= $entityVer->getYear()+9; $year++){
						if(($year >= $entityVer->getYear())&&($year <= $entityVer->getYear()+9)){
							$exp['exp_center_frequency'.$year] .= "0/0;";
						}
					}
				}


				$exp['exp_news_exam'] .= (($exptermRec['newsExam'] == '1') ? 'N' : '') . ';';
			}

			foreach ($exp as $key => $val) {
				$exp[$key] = mb_substr($val,0,mb_strlen($val)-1);
			}
		}

		$idx = 1;
		for($year = $entityVer->getYear(); $year <= $entityVer->getYear()+9; $year++){
			if(($exp['exp_center_frequency'.$year] != "0/0")&&($exp['exp_center_frequency'.$year] != "")&&($exp['exp_center_frequency'.$year] != "/")){
				$exp['exp_center_frequency'.$idx] = $exp['exp_center_frequency'.$year];
			}else{
				foreach ($expterm as $exptermRec) {
					$exp['exp_center_frequency'.$idx] .= "0/0;";
				}
				$exp['exp_center_frequency'.$idx] = substr($exp['exp_center_frequency'.$idx],0,strlen($exp['exp_center_frequency'.$idx])-1);
			}
			$idx++;
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

			$cnt = 1;
			for($year = $entityVer->getYear(); $year <= $entityVer->getYear()+9; $year++){
				if(isset($synterm[$idx-1]['center_frequency'.$year])){
					$syn['syn'.$idx.'_center_frequency'.$cnt] = (empty($synterm[$idx-1])) ? "" : $synterm[$idx-1]['center_frequency'.$year];
				}else{
					//センター頻度開始年を用語登録後に変更した場合、頻度が取得されない年があるので初期値を設定
					$syn['syn'.$idx.'_center_frequency'.$cnt] = (empty($synterm[$idx-1])) ? "" : "0/0";
				}
				$cnt++;
			}

			$syn['syn'.$idx.'_center_frequency'] = (empty($synterm[$idx-1])) ? 0 : $synterm[$idx-1]['center_frequency'];
			$syn['syn'.$idx.'_news_exam'] = (empty($synterm[$idx-1])) ? "" : $synterm[$idx-1]['news_exam'];
			$syn['syn'.$idx.'_delimiter'] = (empty($synterm[$idx-1])) ? "" : $synterm[$idx-1]['delimiter'];
			$syn['syn'.$idx.'_index_add_letter'] = (empty($synterm[$idx-1])) ? "" : $synterm[$idx-1]['index_add_letter'];
			$syn['syn'.$idx.'_index_kana'] = (empty($synterm[$idx-1])) ? "" : $synterm[$idx-1]['index_kana'];
		}

		// 指矢印参照
		$ref = [];

		for($idx=1;$idx<=3;$idx++){
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
		for ($i=1; $i<=3; $i++) {
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
			if(($entityCenterRec->getYear() >= $entityVer->getYear())&&($entityCenterRec->getYear() <= $entityVer->getYear()+9)){
				$main['center_frequency'.$entityCenterRec->getYear()] = $entityCenterRec->getMainExam() . "/" . $entityCenterRec->getSubExam();
			}
		}

		$idx = 1;
		if($entityCenter){
			for($year = $entityVer->getYear(); $year <= $entityVer->getYear()+9; $year++){
				if(isset($main['center_frequency'.$year])){
					$main['center_frequency'.$idx] = $main['center_frequency'.$year];
				}else{
					$main['center_frequency'.$idx] = "0/0";
				}
				$idx++;
			}
		}else{
			for($idx=1;$idx<11;$idx++){
				$main['center_frequency'.$idx] = "0/0";
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
			if(($entityCenterRec->getYear() >= $entityVer->getYear())&&($entityCenterRec->getYear() <= $entityVer->getYear()+9)){
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

		if($sub['id'] != ''){
			$sub['id'] = 'S'.str_pad($sub['id'], 6, 0, STR_PAD_LEFT);
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
			if(($entityCenterRec->getYear() >= $entityVer->getYear())&&($entityCenterRec->getYear() <= $entityVer->getYear()+9)){
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
			$syn['delimiter'] = '改行';
		}elseif($syn['delimiter'] == '5'){
			$syn['delimiter'] = '改行＋（';
		}elseif($syn['delimiter'] == '6'){
			$syn['delimiter'] = '）＋改行';
		}

		if($syn['id'] != ""){
			$syn['id'] = 'D'.str_pad($syn['id'], 6, 0, STR_PAD_LEFT);
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
		$session = $request->getSession();

		$update_all = false;
		$import_new = false;
		if($request->request->has("up_submit_freq")){
			$update_all = false;
			$update_contens = "頻度上書き";
		}
		if($request->request->has("up_submit_all")){
			$update_all = true;
			$update_contens = "全項目上書き";
		}
		if($request->request->has("up_submit_new")){
			$import_new = true;
			$update_contens = "新語登録";
		}

		// postでもらうべきのリストを取得
		if($import_new){
			// 新規
			$targets = $this->container->getParameter('api.upload.term_new');
			$version_idx = "version3";
			$session->set(self::SES_DIVISION_KEY, 'new');
		}else{
			// 上書き
			$targets = $this->container->getParameter('api.upload.term');
			$version_idx = "version2";
			$session->set(self::SES_DIVISION_KEY, 'update');
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
			$this->clearDirectory($archivePath . $params['curriculum'] . '_' . $params[$version_idx]);

			foreach ($files as $el_file_list){
				$filename = $this->uploads($el_file_list['name'], $archivePath . $params['curriculum'] . '_' . $params[$version_idx] . '/', $tempPath . '/');
			}

			if(!$this->checkFileType($archivePath . $params['curriculum'] . '_' . $params[$version_idx] . '/' . $filename)){
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
					'id' => $params[$version_idx],
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

		if($import_new){
			// 用語IDの発番
			$maxTermIDRec = $em->getRepository('CCKCommonBundle:MainTerm')->getNewTermID();
			$newTermId = (int)$maxTermIDRec[0]['term_id'];

			$maxSubTermIDRec = $em->getRepository('CCKCommonBundle:SubTerm')->getNewTermID();
			$newSubTermId = (int)$maxSubTermIDRec[0]['id'];

			$maxSynTermIDRec = $em->getRepository('CCKCommonBundle:Synonym')->getNewTermID();
			$newSynTermId = (int)$maxSynTermIDRec[0]['id'];

			$maxExpTermIDRec = $em->getRepository('CCKCommonBundle:ExplainIndex')->getNewTermID();
			$newExpTermId = (int)$maxExpTermIDRec[0]['id'];
		}

		$updatefile = array();
		$updatefile = $this->openDir($archivePath . $params['curriculum'] . '_' . $params[$version_idx], '', $updatefile);
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
					$data = str_getcsv($line);

					//ヘッダーはスキップ
					if($lineCnt == 1) {
						$header = $data;
						continue;
					}

					// 入力データチェック
					$status = $this->inputDataCheck($lineCnt,$data,$header,$filePointer,$curriculum_name,$version_name,$entityVersion,$request,$import_new,$header_id);
					if($status != 200){
						return $this->redirect($this->generateUrl('client.csv.import.term', array('status' => $status)));
					}

					try {
						$em->getConnection()->beginTransaction();
						// 主用語
						if($import_new){
							$newTermId++;
							$this->insertMainTerm($data,$em,$update_all,$handleMain[0],$handleCenter[0],$entityVersion,$user,$newTermId,$header_id);
						}else{
							$this->updateMainTerm($data,$em,$update_all,$handleMain[0],$handleCenter[0],$entityVersion,$header_id);
						}

						// サブ用語の更新
						$sub_id = [];
						for($idx=0;$idx<5;$idx++){
							if($import_new){
								$this->insertSubTerm($data, $idx*17,$em,$update_all,$handleSub[0],$handleCenter[0],$entityVersion,$user,$newTermId,$newSubTermId);
							}else{
								array_push($sub_id, $this->updateSubTerm($data, $idx*17,$em,$update_all,$handleSub[0],$handleCenter[0],$entityVersion));
							}
						}

						if($update_all){
							// サブ用語の削除
							$this->deleteSubTerm($data, $sub_id, $em, 'SubTerm',$handleSub[0],$handleCenter[0]);
						}

						// 同対類用語の更新
						$syn_id = [];
						for($idx=0;$idx<11;$idx++){
							if($import_new){
								$this->insertSynTerm($data, $idx*18,$em,$update_all,$handleSyn[0],$handleCenter[0],$entityVersion,$user,$newTermId,$newSynTermId);
							}else{
								array_push($syn_id, $this->updateSynTerm($data, $idx*18,$em,$update_all,$handleSyn[0],$handleCenter[0],$entityVersion));
							}
						}

						if($update_all){
							// 同対類用語の削除
							$this->deleteSubTerm($data, $syn_id, $em, 'Synonym',$handleSyn[0],$handleCenter[0]);
						}

						// 解説内さくいん用語の更新
						$this->get('logger')->error("***updateExpTerm***");
						if($import_new){
							$this->insertExpTerm($data,$em,$update_all,$handleExplain[0],$handleCenter[0],$entityVersion,$user,$newTermId,$newExpTermId);
						}else{
							$exp_id = $this->updateExpTerm($data,$em,$update_all,$handleExplain[0],$handleCenter[0],$entityVersion);
						}

						if($update_all){
							// 解説内さくいん用語の削除
							$this->deleteSubTerm($data, $exp_id, $em, 'ExplainIndex',$handleExplain[0],$handleCenter[0]);
						}

						if($update_all){
							// 指矢印参照用語
							$ref_id = [];
							for($idx=0;$idx<3;$idx++){
								array_push($ref_id, $this->updateRefTerm($data, $idx,$em));
							}
							// 指矢印用語の削除
							$this->deleteRefTerm($data, $ref_id, $em, 'Refer',$handleRefer[0]);
						}
						if($import_new){
							for($idx=0;$idx<3;$idx++){
								$this->insertRefTerm($data, $idx,$em,$handleRefer[0],$newTermId);
							}
						}

						$em->getConnection()->commit();

					} catch (\Exception $e){
						// もし、DBに登録失敗した場合rollbackする
						$em->getConnection()->rollback();
						$em->close();

						// log
						$this->get('logger')->error($e->getMessage());
						$this->get('logger')->error($e->getTraceAsString());

						$errcode = $e->getCode();

						if(($errcode == 209)||($errcode == 210)){
							// ファイルをクローズする
							fclose($filePointer);
							fclose($handleMain[0]);
							fclose($handleExplain[0]);
							fclose($handleSub[0]);
							fclose($handleSyn[0]);
							fclose($handleRefer[0]);
							fclose($handleCenter[0]);

							$rtn_message = $e->getMessage();
							$this->OutputLog("ERROR9", "import_term.log",$rtn_message);
							$session->set(self::SES_REQUIRED_KEY, $rtn_message);
						}else{
							$errcode = 453;
						}

						return $this->redirect($this->generateUrl('client.csv.import.term', array('status' => $errcode)));
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
		$this->registerUploadHistory($em,$params[$version_idx],$user->getUserId(),$files,$update_contens);

		$em->close();

		return $this->redirect($this->generateUrl('client.csv.import.term', array('status' => $status)));

	}

	private function inputDataCheck($lineCnt,$data,$header,$filePointer,$curriculum_name,$version_name,$entityVersion,$request,$import_new,&$header_id){
		$session = $request->getSession();
		$status = 200;

		// 新語登録は主用語を、上書きは用語IDをエラーメッセージに表記する
		if($import_new){
			$term_info = $data[9];
		}else{
			$term_info = $data[0];
		}

		if($lineCnt > 1) {
			//エンコードチェック
			if (mb_detect_encoding($data[9], "UTF-8") === false){
				fclose($filePointer);
				$this->OutputLog("ERROR0", "import_term.log", "テキストファイルのエンコード形式を確認してください。取込可能な形式はUTF-8です。");
				$status = 202;
				return $status;
			}
		}

		// 項目数チェック
		if(count($data) != 337){
			fclose($filePointer);
			$this->OutputLog("ERROR2", "import_term.log", "[".$term_info."]の項目数が異なっています。正しい項目数：337。項目数：".count($data));
			$status = 204;
			return $status;
		}

		// 教科・版チェック
		if(($curriculum_name != $data[1])||($version_name != $data[2])){
			fclose($filePointer);
			$this->OutputLog("ERROR1", "import_term.log", "教科・版が異なっています。CSVデータの教科：".$data[1].",版：".$data[2].";取込対象の教科：".$curriculum_name.",版：".$version_name);
			$status = 203;
			return $status;
		}

		// 必須項目チェック
		$required_check = true;
		$extra_check = true;
		$blank_list = "";
		$extra_list = "";
		$required_filed = array(1,2,3,4,8,9,10,11,12,13,14,15,16,17,18,19,20,22,26);
		// 主用語
		foreach($required_filed as $required_idx){
			if($data[$required_idx] == ""){
				$blank_list .= $header[$required_idx].",";
				$required_check = false;
			}
		}

		$required_filed_sub = array(31,32,33,34,35,36,37,38,39,40,41,42,44,46);
		$extra_filed_sub = array(31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46);
		// サブ用語
		for($idx=0;$idx<5;$idx++){
			if($data[31+$idx*17] != ""){
				foreach($required_filed_sub as $required_idx){
					if($data[$required_idx+$idx*17] == ""){
						$blank_list .= $header[$required_idx+$idx*17].",";
						$required_check = false;
					}
				}
			}else{
				foreach($extra_filed_sub as $extra_idx){
					if($data[$extra_idx+$idx*17] != ""){
						$extra_list .= $header[$extra_idx+$idx*17].",";
						$extra_check = false;
					}
				}
			}
		}

		$required_filed_syn = array(131,133,134,135,136,137,138,139,140,141,142,143,144,148);
		$extra_filed_syn = array(131,132,133,134,135,136,137,138,139,140,141,142,143,144,145,146,147,148);
		// 同対類用語
		for($idx=0;$idx<11;$idx++){
			if($data[133+$idx*18] != ""){
				foreach($required_filed_syn as $required_idx){
					if($data[$required_idx+$idx*18] == ""){
						$blank_list .= $header[$required_idx+$idx*18].",";
						$required_check = false;
					}
				}
			}else{
				foreach($extra_filed_syn as $extra_idx){
					if($data[$extra_idx+$idx*18] != ""){
						$extra_list .= $header[$extra_idx+$idx*18].",";
						$extra_check = false;
					}
				}
			}
		}

		$required_filed_exp = array(117,119,120,121,122,123,124,125,126,127,128,129);
		$extra_filed_exp = array(116,117,118,119,120,121,122,123,124,125,126,127,128,129,130);
		// 解説内さくいん用語
		if($data[117] != ""){
			foreach($required_filed_exp as $required_idx){
				$arr_exp = explode(";",$data[$required_idx]);

				if(in_array("",$arr_exp)){
					$blank_list .= $header[$required_idx].",";
					$required_check = false;
				}
			}
		}else{
			foreach($extra_filed_exp as $extra_idx){
				if($data[$extra_idx] != ""){
					$extra_list .= $header[$extra_idx].",";
					$extra_check = false;
				}
			}
		}

		if(!$required_check){
			fclose($filePointer);
			$rtn_message = "[".$term_info."]の必須項目が未入力です。未入力項目：".$blank_list;
			$this->OutputLog("ERROR3", "import_term.log",$rtn_message);
			$session->set(self::SES_REQUIRED_KEY, $rtn_message);
			$status = 206;
			return $status;
		}

		if(!$extra_check){
			fclose($filePointer);
			$rtn_message = "[".$term_info."]に不要な項目が入っています。項目：".$extra_list;
			$this->OutputLog("ERROR3", "import_term.log",$rtn_message);
			$session->set(self::SES_REQUIRED_KEY, $rtn_message);
			$status = 206;
			return $status;
		}

		// 見出しチェック
		$headerRecordSet = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'versionId' => $entityVersion->getId(),
				'deleteFlag' => FALSE
		),
				array('id' => 'ASC'));

		if ($data[7] != ""){
			//小見出しID取得
			$header_id = $this->getHeaderId($headerRecordSet, $data[3], $data[4], $data[5], $data[6], $data[7]);
		}elseif($data[6] != ""){
			//中見出しID取得
			$header_id = $this->getHeaderId($headerRecordSet, $data[3], $data[4], $data[5], $data[6]);
		}elseif($data[5] != ""){
			//大見出しID取得
			$header_id = $this->getHeaderId($headerRecordSet, $data[3], $data[4], $data[5]);
		}elseif($data[4] != ""){
			//章見出しID取得
			$header_id = $this->getHeaderId($headerRecordSet, $data[3], $data[4]);
		}elseif($data[3] != ""){
			//編見出しID取得
			$header_id = $this->getHeaderId($headerRecordSet, $data[3]);
		}

		$header_str = "";
		if($data[3] != ""){
			$header_str .= "編見出し：".$data[3].",";
		}
		if($data[4] != ""){
			$header_str .= "章見出し：".$data[4].",";
		}
		if($data[5] != ""){
			$header_str .= "大見出し：".$data[5].",";
		}
		if($data[6] != ""){
			$header_str .= "中見出し：".$data[6].",";
		}
		if($data[7] != ""){
			$header_str .= "小見出し：".$data[7].",";
		}

		if($header_id == 0){
			fclose($filePointer);
			$rtn_message = "マスタに登録のない見出しが登録されています。：".$header_str;
			$this->OutputLog("ERROR5", "import_term.log",$rtn_message);
			$session->set(self::SES_REQUIRED_KEY, $rtn_message);
			$status = 208;
			return $status;
		}

		if(!$import_new){
			$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:MainTerm')->findOneBy(array(
					'curriculumId' => $entityVersion->getId(),
					'termId' => intval(substr($data[0], 1)),
					'deleteFlag' => FALSE
			));
			if(!$entity){
				fclose($filePointer);
				$rtn_message = "上書きする教科・版に存在しない主用語IDです。：[".$data[0]."]";
				$this->OutputLog("ERROR4", "import_term.log", $rtn_message);
				$session->set(self::SES_REQUIRED_KEY, $rtn_message);
				$status = 207;
				return $status;
			}
		}

		// センター頻度スラッシュチェック
		$center_check = true;
		$center_list = "";
		$center_filed = array(11,12,13,14,15,16,17,18,19,20);
		// 主用語
		foreach($center_filed as $required_idx){
			if(preg_match('/^(\d+)\/(\d+)$/', $data[$required_idx])||preg_match('/(\d+)月(\d+)日/', $data[$required_idx])){
			}else{
				$center_list .= $header[$required_idx].",";
				$center_check = false;
			}
		}
		$center_filed_sub = array(33,34,35,36,37,38,39,40,41,42);
		// サブ用語
		for($idx=0;$idx<5;$idx++){
			if($data[31+$idx*17] != ""){
				foreach($center_filed_sub as $required_idx){
					if(preg_match('/^(\d+)\/(\d+)$/', $data[$required_idx+$idx*17])||preg_match('/(\d+)月(\d+)日/', $data[$required_idx+$idx*17])){
					}else{
						$center_list .= $header[$required_idx+$idx*17].",";
						$center_check = false;
					}
				}
			}
		}
		$center_filed_syn = array(135,136,137,138,139,140,141,142,143,144);
		// 同対類用語
		for($idx=0;$idx<11;$idx++){
			if($data[133+$idx*18] != ""){
				foreach($center_filed_syn as $required_idx){
					if(preg_match('/^(\d+)\/(\d+)$/', $data[$required_idx+$idx*18])||preg_match('/(\d+)月(\d+)日/', $data[$required_idx+$idx*18])){
					}else{
						$center_list .= $header[$required_idx+$idx*18].",";
						$center_check = false;
					}
				}
			}
		}
		$center_filed_exp = array(120,121,122,123,124,125,126,127,128,129);
		// 解説内さくいん用語
		if($data[117] != ""){
			foreach($center_filed_exp as $required_idx){
				$arr_exp = explode(";",$data[$required_idx]);

				foreach($arr_exp as $ele_exp){
					if(preg_match('/^(\d+)\/(\d+)$/', $ele_exp)||preg_match('/(\d+)月(\d+)日/', $ele_exp)){
					}else{
						$center_list .= $header[$required_idx].",";
						$center_check = false;
					}
				}
			}
		}

		if(!$center_check){
			fclose($filePointer);
			$rtn_message = "[".$term_info."]のセンター頻度項目にスラッシュをいれてください：".$center_list;
			$this->OutputLog("ERROR3", "import_term.log",$rtn_message);
			$session->set(self::SES_REQUIRED_KEY, $rtn_message);
			$status = 206;
			return $status;
		}

		// 解説内さくいん用語　項目数チェック
		$exp_term = [];
		if(preg_match_all('/《c_SAK》(.*?)《\/c_SAK》/u', $data[115], $match_data, PREG_SET_ORDER)){
			foreach($match_data as $main_explain_ele){
				array_push($exp_term, $main_explain_ele[1]);
			}
		}

		$cnt_check_exp = array(116,117,118,119,120,121,122,123,124,125,126,127,128,129,130);
		$cnt_check_list = "";
		$cnt_check = true;
		if($data[117] != ""){
			foreach($cnt_check_exp as $checked_idx){
				$arr_exp = explode(";",$data[$checked_idx]);

				if(count($exp_term) != count($arr_exp)){
					$cnt_check_list .= $header[$checked_idx].",";
					$cnt_check = false;
				}
			}
		}

		if(!$cnt_check){
			fclose($filePointer);
			$rtn_message = "[".$term_info."]の解説内さくいん用語の項目数が一致していません：".$cnt_check_list;
			$this->OutputLog("ERROR3", "import_term.log",$rtn_message);
			$session->set(self::SES_REQUIRED_KEY, $rtn_message);
			$status = 211;
			return $status;
		}

		// 指矢印用語 版IDチェック
		for($idx=0;$idx<3;$idx++){
			if($data[329+$idx] != ""){
				$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:MainTerm')->findOneBy(array(
						'curriculumId' => $entityVersion->getId(),
						'mainTerm' => $data[329+$idx],
						'deleteFlag' => FALSE
				));

				if(!$entity){
					fclose($filePointer);
					$rtn_message = "[".$term_info."]に異なる版の指矢印用語IDが設定されています：".$data[329+$idx];
					$this->OutputLog("ERROR3", "import_term.log", $rtn_message);
					$session->set(self::SES_REQUIRED_KEY, $rtn_message);
					$status = 215;
					return $status;
				}
			}
		}

		// 新語登録チェック
		if($import_new){
			// 用語ID空欄チェック
			$exists_subid = false;
			for($idx=0;$idx<5;$idx++){
				if($data[30+$idx*17] != ""){
					$exists_subid = true;
				}
			}

			$arr_exp_id = explode(";",$data[116]);
			foreach ($arr_exp_id as $ele_exp_id){
				if($ele_exp_id != ""){
					$exists_subid = true;
				}
			}

			for($idx=0;$idx<11;$idx++){
				if($data[132+$idx*18] != ""){
					$exists_subid = true;
				}
			}

			if(($data[0] != "")||($exists_subid)){
				fclose($filePointer);
				$rtn_message = "[".$data[9]."]にIDが入力されています。CSVをご確認ください";
				$this->OutputLog("ERROR3", "import_term.log",$rtn_message);
				$session->set(self::SES_REQUIRED_KEY, $rtn_message);
				$status = 212;
				return $status;
			}

			// 用語重複チェック
			$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:MainTerm')->findOneBy(array(
					'curriculumId' => $entityVersion->getId(),
					'mainTerm' => $data[9],
					'deleteFlag' => FALSE
			));
			if($entity){
				fclose($filePointer);
				$rtn_message = "既に登録されている用語です。：[".$data[9]."]";
				$this->OutputLog("ERROR3", "import_term.log", $rtn_message);
				$session->set(self::SES_REQUIRED_KEY, $rtn_message);
				$status = 213;
				return $status;
			}

			for($idx=0;$idx<5;$idx++){
				if($data[31+$idx*17] != ""){
					$recordset = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:SubTerm')->getSubTermByCurriculumId($entityVersion->getId(),$data[9],$data[31+$idx*17]);
					if(count($recordset) > 0){
						fclose($filePointer);
						$rtn_message = "主用語[".$term_info."]に既に登録されているサブ用語があります。：[".$data[31+$idx*17]."]";
						$this->OutputLog("ERROR3", "import_term.log", $rtn_message);
						$session->set(self::SES_REQUIRED_KEY, $rtn_message);
						$status = 214;
						return $status;
					}
				}
			}
		}

		return $status;
	}

	private function getHeaderId($headerRecordSet,$hen,$sho = null,$dai = null,$chu = null,$ko = null){
		$belongHen = false;
		$belongSho = false;
		$belongDai = false;
		$belongChu = false;

		foreach ($headerRecordSet as $headerRec){
			if(($sho == null)&&($dai == null)&&($chu == null)&&($ko == null)){
				// 編見出し
				if($headerRec->getName() == $hen){
					return $headerRec->getId();
				}
			}elseif(($dai == null)&&($chu == null)&&($ko == null)){
				// 章見出し
				if($headerRec->getName() == $hen){
					$belongHen = true;
				}
				if(($headerRec->getName() == $sho)&&($belongHen == true)){
					return $headerRec->getId();
				}
			}elseif(($chu == null)&&($ko == null)){
				// 大見出し
				if($headerRec->getName() == $hen){
					$belongHen = true;
				}
				if($headerRec->getName() == $sho){
					$belongSho = true;
				}
				if(($headerRec->getName() == $dai)&&($belongHen == true)&&($belongSho == true)){
					return $headerRec->getId();
				}
			}elseif($ko == null){
				// 中見出し
				if($headerRec->getName() == $hen){
					$belongHen = true;
				}
				if($headerRec->getName() == $sho){
					$belongSho = true;
				}
				if(($headerRec->getName() == $dai)||($dai == "")){
					$belongDai = true;
				}
				if(($headerRec->getName() == $chu)&&($belongHen == true)&&($belongSho == true)&&($belongDai == true)){
					return $headerRec->getId();
				}
			}else{
				// 小見出し
				if($headerRec->getName() == $hen){
					$belongHen = true;
				}
				if($headerRec->getName() == $sho){
					$belongSho = true;
				}
				if($headerRec->getName() == $dai){
					$belongDai = true;
				}
				if($headerRec->getName() == $chu){
					$belongChu = true;
				}
				if(($headerRec->getName() == $ko)&&($belongHen == true)&&($belongSho == true)&&($belongDai == true)&&($belongChu == true)){
					return $headerRec->getId();
				}
			}
		}
		return 0;
	}

	private function insertMainTerm($data,$em,$update_all,$handle,$handle_center,$entityVersion,$user,$newTermId,$header_id){

		// センター頻度の取得
		$year = $entityVersion->getYear();
		$center_freq_sum = 0;
		for($idx=0;$idx<10;$idx++){
			$arr_freq = $this->splitCenterFreq($data[11+$idx]);

			if(count($arr_freq) == 2){
				$center_freq_sum += intval($arr_freq[0]) + intval($arr_freq[1]);
			}

			$sql = "INSERT INTO `Center` (`id`, `main_term_id`, `sub_term_id`, `yougo_flag`, `year`, `main_exam`, `sub_exam`, `create_date`, `modify_date`, `delete_date`, `delete_flag`) VALUES";
			$sql .= "(null,";
			$sql .= $newTermId.",";
			$sql .= "0,";
			$sql .= "1,";
			$sql .= $year.",";
			$sql .= intval($arr_freq[0]).",";
			$sql .= intval($arr_freq[1]).",";
			$sql .= "NOW(),";
			$sql .= "null,";
			$sql .= "null,";
			$sql .= "0);";

			fputs($handle_center, $sql."\n");
			$year++;
		}

		// 区切り文字IDの取得
		if(($data[22] == "")||($data[22] == "なし")){
			$delimiter = 0;
		}elseif($data[22] == "と"){
			$delimiter = 1;
		}elseif($data[22] == "，"){
			$delimiter = 2;
		}elseif($data[22] == "・"){
			$delimiter = 3;
		}elseif($data[22] == "／"){
			$delimiter = 4;
		}elseif($data[22] == "（"){
			$delimiter = 5;
		}else{
			throw new Exception("[".$data[9]."]の区切り文字が間違っています。：".$data[22], 209);
		}

		$sql = "INSERT INTO `MainTerm` (`id`, `term_id`, `curriculum_id`, `header_id`, `print_order`, `main_term`, `red_letter`, `text_frequency`, `center_frequency`, `news_exam`, `delimiter`, `western_language`, `birth_year`, `kana`, `index_add_letter`, `index_kana`, `index_original`, `index_original_kana`, `index_abbreviation`, `nombre`, `term_explain`, `handover`, `illust_filename`, `illust_caption`, `illust_kana`, `illust_nombre`, `user_id`, `create_date`, `modify_date`, `delete_date`, `delete_flag`, `nombre_bold`) VALUES";
		$sql .= "(null,";
		$sql .= $newTermId.",";
		$sql .= $entityVersion->getId().",";
		$sql .= $header_id.",";
		$sql .= $data[8].",";
		$sql .= "'".$data[9]."',";
		$sql .= "0,";
		$sql .= (($data[10] == "") ? "0" : $data[10]) .",";
		$sql .= $center_freq_sum.",";
		$sql .= (($data[21] == 'N') ? "1" : "0").",";
		$sql .= "'".$delimiter."',";
		$sql .= "'".$data[23]."',";
		$sql .= "'".$data[24]."',";
		$sql .= "'',";
		$sql .= "'".$data[25]."',";
		$sql .= "'".$data[26]."',";
		$sql .= "'".$data[28]."',"; // 原典資料索引
		$sql .= "'".$data[27]."',"; // 原典資料索引よみ
		$sql .= "'".$data[29]."',";
		$sql .= "0,";
		$sql .= "'".$data[115]."',";
		$sql .= "'".$data[332]."',";
		$sql .= "'".$data[333]."',";
		$sql .= "'".$data[334]."',";
		$sql .= "'".$data[335]."',";
		$sql .= "0,";
		$sql .= "'".$user->getUserId()."',";
		$sql .= "NOW(),";
		$sql .= "null,";
		$sql .= "null,";
		$sql .= "0,";
		$sql .= (($data[336] == '●') ? "1" : "0").");";

		fputs($handle, $sql."\n");

	}

	private function updateMainTerm($data,$em,$update_all,$handle,$handle_center,$entityVersion,$header_id){
		// センター頻度の年別データ
		$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
				'mainTermId' => intval(substr($data[0], 1)),
				'yougoFlag' => '1',
				'deleteFlag' => FALSE
		),
				array('year' => 'ASC'));

		$year = $entityVersion->getYear();
		$idx = 0;
		$center_freq_sum = 0;
		foreach ($entityCenter as $entityCenterRec){
			if($entityCenterRec->getYear() == ($year+$idx)){
				$arr_freq = $this->splitCenterFreq($data[11+$idx]);

				if(count($arr_freq) == 2){
					$center_freq_sum += intval($arr_freq[0]) + intval($arr_freq[1]);

					$sql = "UPDATE `Center` SET `main_exam` = " . $arr_freq[0] . ",`sub_exam` = " . $arr_freq[1];
					$sql .= ",`modify_date` = NOW() WHERE `main_term_id` = ".intval(substr($data[0], 1))." AND `yougo_flag` = 1 AND `year` = " . ($year+$idx) . " AND `delete_flag` = 0;";
					fputs($handle_center, $sql."\n");
				}
			}
			$idx++;
		}

		// センター頻度未登録の場合
		if(!$entityCenter){
			for ($idx=0;$idx<10;$idx++){
				$arr_freq = $this->splitCenterFreq($data[11+$idx]);

				if(count($arr_freq) == 2){
					$center_freq_sum += intval($arr_freq[0]) + intval($arr_freq[1]);
				}
			}
		}

		$sql = "UPDATE `MainTerm` SET `text_frequency` = " . (($data[10] == "") ? "0" : $data[10]) . ",`center_frequency` = " . $center_freq_sum . ",`news_exam` = " . ($data[21] == "N" ? 1 : 0);

		if($update_all){
			if(($data[22] == "")||($data[22] == "なし")){
				$delimiter = 0;
			}elseif($data[22] == "と"){
				$delimiter = 1;
			}elseif($data[22] == "，"){
				$delimiter = 2;
			}elseif($data[22] == "・"){
				$delimiter = 3;
			}elseif($data[22] == "／"){
				$delimiter = 4;
			}elseif($data[22] == "（"){
				$delimiter = 5;
			}else{
				throw new Exception("[".$data[0]."]の区切り文字が間違っています。：".$data[22], 209);
			}

			$sql .= ",`header_id` = " . $header_id . ",`print_order` = " . $data[8] . ",`main_term` = '" . $data[9] . "',`delimiter` = '" . $delimiter . "',`western_language` = '" . $data[23] . "',`birth_year` = '" . $data[24] . "',`index_add_letter` = '" . $data[25] . "'";
			$sql .= ",`index_kana` = '" . $data[26] . "',`index_original` = '" . $data[28] . "',`index_original_kana` = '" . $data[27] . "',`index_abbreviation` = '" . $data[29] . "',`term_explain` = '" . $data[115] . "',`handover` = '" . $data[332] . "'";
			$sql .= ",`illust_filename` = '" . $data[333] . "',`illust_caption` = '" . $data[334] . "',`illust_kana` = '" . $data[335] . "',`nombre_bold` = " . (($data[336] == "●") ? 1 : 0);
		}

		$sql .= ",`modify_date` = NOW() WHERE `term_id` = ".intval(substr($data[0], 1))." AND `delete_flag` = 0;";
		fputs($handle, $sql."\n");

		// センター頻度未登録の場合
		if(!$entityCenter){
			for ($idx=0;$idx<10;$idx++){
				$entityCenter = new Center();

				$entityCenter->setMainTermId(intval(substr($data[0], 1)));
				$entityCenter->setSubTermId(0);
				$entityCenter->setYougoFlag(1);
				$entityCenter->setYear($year+$idx);

				$arr_freq = $this->splitCenterFreq($data[11+$idx]);
				if(count($arr_freq) == 2){
					$entityCenter->setMainExam(intval($arr_freq[0]));
					$entityCenter->setSubExam(intval($arr_freq[1]));
				}else{
					$entityCenter->setMainExam(0);
					$entityCenter->setSubExam(0);
				}

				$em->persist($entityCenter);
				$em->flush();
			}
		}

	}

	private function insertSubTerm($data,$offset,$em,$update_all,$handle,$handle_center,$entityVersion,$user,$newTermId,&$newSubTermId){
		$this->get('logger')->error("***1***");
		// 用語が設定されていない場合
		if($data[31+$offset] == ""){
			return false;
		}
		$this->get('logger')->error("***2***");
		$newSubTermId++;

		// センター頻度の取得
		$year = $entityVersion->getYear();
		$center_freq_sum = 0;
		for($idx=0;$idx<10;$idx++){
			$arr_freq = $this->splitCenterFreq($data[33+$idx+$offset]);

			if(count($arr_freq) == 2){
				$center_freq_sum += intval($arr_freq[0]) + intval($arr_freq[1]);
			}

			$sql = "INSERT INTO `Center` (`id`, `main_term_id`, `sub_term_id`, `yougo_flag`, `year`, `main_exam`, `sub_exam`, `create_date`, `modify_date`, `delete_date`, `delete_flag`) VALUES";
			$sql .= "(null,";
			$sql .= $newTermId.",";
			$sql .= $newSubTermId.",";
			$sql .= "2,";
			$sql .= $year.",";
			$sql .= intval($arr_freq[0]).",";
			$sql .= intval($arr_freq[1]).",";
			$sql .= "NOW(),";
			$sql .= "null,";
			$sql .= "null,";
			$sql .= "0);";
			$this->get('logger')->error("******".$sql);
			fputs($handle_center, $sql."\n");
			$year++;
		}
		$this->get('logger')->error("***3***");
		if(($data[44+$offset] == "")||($data[44+$offset] == "なし")){
			$delimiter = 0;
		}elseif($data[44+$offset] == "と"){
			$delimiter = 1;
		}elseif($data[44+$offset] == "，"){
			$delimiter = 2;
		}elseif($data[44+$offset] == "・"){
			$delimiter = 3;
		}elseif($data[44+$offset] == "／"){
			$delimiter = 4;
		}elseif($data[44+$offset] == "（"){
			$delimiter = 5;
		}elseif($data[44+$offset] == "）"){
			$delimiter = 6;
		}elseif($data[44+$offset] == "）と"){
			$delimiter = 8;
		}else{
			throw new Exception("[".$data[9]."]の区切り文字が間違っています。：".$data[44+$offset], 209);
		}
		$this->get('logger')->error("***4***");
		$sql = "INSERT INTO `SubTerm` (`id`, `main_term_id`, `sub_term`, `red_letter`, `text_frequency`, `center_frequency`, `news_exam`, `delimiter`, `kana`, `delimiter_kana`, `index_add_letter`, `index_kana`, `nombre`, `create_date`, `modify_date`, `delete_date`, `delete_flag`) VALUES";
		$sql .= "(".$newSubTermId.",";
		$sql .= $newTermId.",";
		$sql .= "'".$data[31+$offset]."',";
		$sql .= "0,";
		$sql .= (($data[32+$offset] == "") ? "0" : $data[32+$offset]) .",";
		$sql .= $center_freq_sum.",";
		$sql .= (($data[43+$offset] == "N") ? "1" : "0").",";
		$sql .= "'".$delimiter."',";
		$sql .= "'',";
		$sql .= "'',";
		$sql .= "'".$data[45+$offset]."',";
		$sql .= "'".$data[46+$offset]."',";
		$sql .= "0,";
		$sql .= "NOW(),";
		$sql .= "null,";
		$sql .= "null,";
		$sql .= "0);";
		$this->get('logger')->error("***5***");
		fputs($handle, $sql."\n");
	}

	private function updateSubTerm($data,$offset,$em,$update_all,$handle,$handle_center,$entityVersion){
		// 用語が設定されていない場合
		if($data[31+$offset] == ""){
			return false;
		}

		$sub_id = "";
		if($data[30+$offset] != ""){
			$sub_id = ltrim(substr($data[30+$offset], 1),'0');
		}

		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:SubTerm')->findOneBy(array(
				'id' =>$sub_id,
				'mainTermId' =>intval(substr($data[0], 1)),
				'deleteFlag' => FALSE
		));

		$update_mode = 'update';
		if(!$entity){
			if($sub_id == ""){
				// 用語IDが設定されていない場合、新規登録
				$entity = new SubTerm();
				$update_mode = 'new';
			}else{
				// 削除済IDと未登録IDの判別
				$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:SubTerm')->findOneBy(array(
						'id' =>$sub_id,
						'mainTermId' =>intval(substr($data[0], 1))
				));

				if($entity){
					// 削除済ID
					throw new Exception("[".$data[0]."]削除済のIDです。追加・新規登録の場合はIDを空欄にしてください：".$data[30+$offset], 210);
				}else{
					// 未登録ID
					throw new Exception("[".$data[0]."]の追加項目にIDが入力されています。CSVをご確認ください：".$data[30+$offset], 210);
				}
			}
		}

		// センター頻度の年別データ
		$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
				'mainTermId' => intval(substr($data[0], 1)),
				'subTermId' => $sub_id,
				'yougoFlag' => '2',
				'deleteFlag' => FALSE
		),
		array('year' => 'ASC'));

		$year = $entityVersion->getYear();
		$idx = 0;
		$center_freq_sum = 0;
		foreach ($entityCenter as $entityCenterRec){
			if($entityCenterRec->getYear() == ($year+$idx)){
				$arr_freq = $this->splitCenterFreq($data[33+$idx+$offset]);

				if(count($arr_freq) == 2){
					$center_freq_sum += intval($arr_freq[0]) + intval($arr_freq[1]);

					$sql = "UPDATE `Center` SET `main_exam` = " . $arr_freq[0] . ",`sub_exam` = " . $arr_freq[1];
					$sql .= ",`modify_date` = NOW() WHERE `main_term_id` = ".intval(substr($data[0], 1))." AND `sub_term_id` = ".$sub_id." AND `yougo_flag` = 2 AND `year` = " . ($year+$idx) . " AND `delete_flag` = 0;";
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
		$entity->setTextFrequency($data[32+$offset] == "" ? 0 : $data[32+$offset]);
		$entity->setCenterFrequency($center_freq_sum);
		$entity->setNewsExam($data[43+$offset] == "N" ? 1 : 0);

		$sql = "UPDATE `SubTerm` SET `text_frequency` = " . ($data[32+$offset] == "" ? "0" : $data[32+$offset]) . ",`center_frequency` = " . $center_freq_sum . ",`news_exam` = " . ($data[43+$offset] == "N" ? 1 : 0);

		if($update_all){
			// サブ用語情報の更新
			$entity->setSubTerm($data[31+$offset]);

			if(($data[44+$offset] == "")||($data[44+$offset] == "なし")){
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
			}else{
				throw new Exception("[".$data[0]."]の区切り文字が間違っています。：".$data[44+$offset], 209);
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
				}else{
					$entityCenter->setMainExam(0);
					$entityCenter->setSubExam(0);
				}

				$em->persist($entityCenter);
				$em->flush();
			}
		}

		if($update_mode == 'update'){
			$sql .= ",`modify_date` = NOW() WHERE `id` = ".$sub_id." AND `delete_flag` = 0;";
			fputs($handle, $sql."\n");

			$rtn_id = $sub_id;
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
					$sql = "UPDATE `".$table."` SET `delete_flag` = 1,`modify_date` = NOW(),`delete_date` = NOW()";
					$sql .= " WHERE `id` = ".$entity_rec->getId().";";
					fputs($handle, $sql."\n");

					$sql = "UPDATE `Center` SET `delete_flag` = 1,`modify_date` = NOW(),`delete_date` = NOW()";
					$sql .= " WHERE `main_term_id` = " . intval(substr($data[0], 1)) . " AND `sub_term_id` = " . $entity_rec->getId() . " AND `yougo_flag` = " . $yougo_flag .";";
					fputs($handle_center, $sql."\n");
				}

			}
		}
	}

	private function insertSynTerm($data,$offset,$em,$update_all,$handle,$handle_center,$entityVersion,$user,$newTermId,&$newSynTermId){
		// 用語が設定されていない場合
		if($data[133+$offset] == ""){
			return false;
		}

		$newSynTermId++;

		// センター頻度の取得
		$year = $entityVersion->getYear();
		$center_freq_sum = 0;
		for($idx=0;$idx<10;$idx++){
			$arr_freq = $this->splitCenterFreq($data[135+$idx+$offset]);

			if(count($arr_freq) == 2){
				$center_freq_sum += intval($arr_freq[0]) + intval($arr_freq[1]);
			}

			$sql = "INSERT INTO `Center` (`id`, `main_term_id`, `sub_term_id`, `yougo_flag`, `year`, `main_exam`, `sub_exam`, `create_date`, `modify_date`, `delete_date`, `delete_flag`) VALUES";
			$sql .= "(null,";
			$sql .= $newTermId.",";
			$sql .= $newSynTermId.",";
			$sql .= "3,";
			$sql .= $year.",";
			$sql .= intval($arr_freq[0]).",";
			$sql .= intval($arr_freq[1]).",";
			$sql .= "NOW(),";
			$sql .= "null,";
			$sql .= "null,";
			$sql .= "0);";

			fputs($handle_center, $sql."\n");
			$year++;
		}

		if($data[131+$offset] == "同"){
			$synid = 1;
		}elseif($data[131+$offset] == "対"){
			$synid = 2;
		}else{
			$synid = 3;
		}

		if(($data[146+$offset] == "")||($data[146+$offset] == "なし")){
			$delimiter = 0;
		}elseif($data[146+$offset] == "　"){
			$delimiter = 1;
		}elseif($data[146+$offset] == "（"){
			$delimiter = 2;
		}elseif($data[146+$offset] == "）"){
			$delimiter = 3;
		}elseif($data[146+$offset] == "改行"){
			$delimiter = 4;
		}elseif($data[146+$offset] == "改行＋（"){
			$delimiter = 5;
		}elseif($data[146+$offset] == "）＋改行"){
			$delimiter = 6;
		}else{
			throw new Exception("[".$data[9]."]の区切り文字が間違っています。：".$data[146+$offset], 209);
		}

		$sql = "INSERT INTO `Synonym` (`id`, `main_term_id`, `term`, `red_letter`, `synonym_id`, `text_frequency`, `center_frequency`, `news_exam`, `delimiter`, `kana`, `index_add_letter`, `index_kana`, `nombre`, `create_date`, `modify_date`, `delete_date`, `delete_flag`) VALUES";
		$sql .= "(".$newSynTermId.",";
		$sql .= $newTermId.",";
		$sql .= "'".$data[133+$offset]."',";
		$sql .= "0,";
		$sql .= $synid.",";
		$sql .= (($data[134+$offset] == "") ? "0" : $data[134+$offset]) .",";
		$sql .= $center_freq_sum.",";
		$sql .= (($data[145+$offset] == "N") ? "1" : "0").",";
		$sql .= "'".$delimiter."',";
		$sql .= "'',";
		$sql .= "'".$data[147+$offset]."',";
		$sql .= "'".$data[148+$offset]."',";
		$sql .= "0,";
		$sql .= "NOW(),";
		$sql .= "null,";
		$sql .= "null,";
		$sql .= "0);";

		fputs($handle, $sql."\n");
	}

	private function updateSynTerm($data,$offset,$em,$update_all,$handle,$handle_center,$entityVersion){
		// 用語が設定されていない場合
		if($data[133+$offset] == ""){
			return false;
		}

		$syn_id = "";
		if($data[132+$offset] != ""){
			$syn_id = ltrim(substr($data[132+$offset], 1),'0');
		}

		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Synonym')->findOneBy(array(
				'id' =>$syn_id,
				'mainTermId' =>intval(substr($data[0], 1)),
				'deleteFlag' => FALSE
		));

		$update_mode = 'update';
		if(!$entity){
			if($syn_id == ""){
				// 用語IDが設定されていない場合、新規登録
				$entity = new Synonym();
				$update_mode = 'new';
			}else{
				// 削除済IDと未登録IDの判別
				$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Synonym')->findOneBy(array(
						'id' =>$syn_id,
						'mainTermId' =>intval(substr($data[0], 1))
				));

				if($entity){
					// 削除済ID
					throw new Exception("[".$data[0]."]削除済のIDです。追加・新規登録の場合はIDを空欄にしてください：".$data[132+$offset], 210);
				}else{
					// 未登録ID
					throw new Exception("[".$data[0]."]の追加項目にIDが入力されています。CSVをご確認ください：".$data[132+$offset], 210);
				}
			}
		}

		// センター頻度の年別データ
		$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
				'mainTermId' => intval(substr($data[0], 1)),
				'subTermId' => $syn_id,
				'yougoFlag' => '3',
				'deleteFlag' => FALSE
		),
		array('year' => 'ASC'));

		$year = $entityVersion->getYear();
		$idx = 0;
		$center_freq_sum = 0;
		foreach ($entityCenter as $entityCenterRec){
			if($entityCenterRec->getYear() == ($year+$idx)){
				$arr_freq = $this->splitCenterFreq($data[135+$idx+$offset]);

				if(count($arr_freq) == 2){
					$center_freq_sum += intval($arr_freq[0]) + intval($arr_freq[1]);

					$sql = "UPDATE `Center` SET `main_exam` = " . $arr_freq[0] . ",`sub_exam` = " . $arr_freq[1];
					$sql .= ",`modify_date` = NOW() WHERE `main_term_id` = ".intval(substr($data[0], 1))." AND `sub_term_id` = ".$syn_id." AND `yougo_flag` = 3 AND `year` = " . ($year+$idx) . " AND `delete_flag` = 0;";
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
		$entity->setTextFrequency($data[134+$offset] == "" ? 0 : $data[134+$offset]);
		$entity->setCenterFrequency($center_freq_sum);
		$entity->setNewsExam($data[145+$offset] == "N" ? 1 : 0);

		$sql = "UPDATE `Synonym` SET `text_frequency` = " . ($data[134+$offset] == "" ? "0" : $data[134+$offset]) . ",`center_frequency` = " . $center_freq_sum . ",`news_exam` = " . ($data[145+$offset] == "N" ? 1 : 0);

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

			if(($data[146+$offset] == "")||($data[146+$offset] == "なし")){
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
			}else{
				throw new Exception("[".$data[0]."]の区切り文字が間違っています。：".$data[146+$offset], 209);
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
				}else{
					$entityCenter->setMainExam(0);
					$entityCenter->setSubExam(0);
				}

				$em->persist($entityCenter);
				$em->flush();
			}
		}

		if($update_mode == 'update'){
			$sql .= ",`modify_date` = NOW() WHERE `id` = ".$syn_id." AND `delete_flag` = 0;";
			fputs($handle, $sql."\n");

			$rtn_id = $syn_id;
		}else{
			$rtn_id = $new_id;
		}

		return $rtn_id;
	}

	private function insertExpTerm($data,$em,$update_all,$handle,$handle_center,$entityVersion,$user,$newTermId,&$newExpTermId){
		$rtn_exp_id = [];
		// 用語が設定されていない場合
		if($data[117] == ""){
			return $rtn_exp_id;
		}

		$exp = [];
		$exp['exp_id'] = explode(";",$data[116]);
		$exp['exp_index_kana'] = explode(";",$data[117]);
		$exp['exp_term'] = explode(";",$data[118]);
		$exp['exp_text_frequency'] = explode(";",$data[119]);

		$idx = 0;
		for($i = $entityVersion->getYear(); $i <= $entityVersion->getYear()+9; $i++){
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
		foreach($exp['exp_index_kana'] as $exp_id_ele){
			if($data[116] == ""){
				$exp_id = "";
			}else{
				$exp_id = $exp['exp_id'][$elem];
			}

			$newExpTermId++;

			// センター頻度の取得
			$year = $entityVersion->getYear();
			$center_freq_sum = 0;
			for($idx=0;$idx<10;$idx++){
				$arr_freq = $this->splitCenterFreq($exp['exp_center_frequency'.$year][$elem]);

				if(count($arr_freq) == 2){
					$center_freq_sum += intval($arr_freq[0]) + intval($arr_freq[1]);
				}

				$sql = "INSERT INTO `Center` (`id`, `main_term_id`, `sub_term_id`, `yougo_flag`, `year`, `main_exam`, `sub_exam`, `create_date`, `modify_date`, `delete_date`, `delete_flag`) VALUES";
				$sql .= "(null,";
				$sql .= $newTermId.",";
				$sql .= $newExpTermId.",";
				$sql .= "4,";
				$sql .= $year.",";
				$sql .= intval($arr_freq[0]).",";
				$sql .= intval($arr_freq[1]).",";
				$sql .= "NOW(),";
				$sql .= "null,";
				$sql .= "null,";
				$sql .= "0);";

				fputs($handle_center, $sql."\n");
				$year++;
			}

			$sql = "INSERT INTO `ExplainIndex` (`id`, `main_term_id`, `index_term`, `index_add_letter`, `index_kana`, `nombre`, `create_date`, `modify_date`, `delete_date`, `delete_flag`, `text_frequency`, `center_frequency`, `news_exam`) VALUES";
			$sql .= "(".$newExpTermId.",";
			$sql .= $newTermId.",";
			$sql .= "'".$exp_term[$elem]."',";
			$sql .= "'".(($data[118] == "") ? "" : $exp['exp_term'][$elem])."',";
			$sql .= "'".$exp_id_ele."',";
			$sql .= "0,";
			$sql .= "NOW(),";
			$sql .= "null,";
			$sql .= "null,";
			$sql .= "0,";
			$sql .= ((($data[119] == "")||($exp['exp_text_frequency'][$elem] == "")) ? "0" : $exp['exp_text_frequency'][$elem]) .",";
			$sql .= $center_freq_sum.",";
			$sql .= (($exp['exp_news_exam'][$elem] == "N") ? "1" : "0").");";

			fputs($handle, $sql."\n");

			$elem++;
		}
	}

	private function updateExpTerm($data,$em,$update_all,$handle,$handle_center,$entityVersion){
		$rtn_exp_id = [];
		// 用語が設定されていない場合
		if($data[117] == ""){
			return $rtn_exp_id;
		}

		$exp = [];
		$exp['exp_id'] = explode(";",$data[116]);
		$exp['exp_index_kana'] = explode(";",$data[117]);
		$exp['exp_term'] = explode(";",$data[118]);
		$exp['exp_text_frequency'] = explode(";",$data[119]);

		$idx = 0;
		for($i = $entityVersion->getYear(); $i <= $entityVersion->getYear()+9; $i++){
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
		foreach($exp['exp_index_kana'] as $exp_id_ele){
			if($data[116] == ""){
				$exp_id = "";
			}else{
				$exp_id = ltrim(substr($exp['exp_id'][$elem], 1),'0');
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
					// 削除済IDと未登録IDの判別
					$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:ExplainIndex')->findOneBy(array(
							'id' =>$exp_id,
							'mainTermId' =>intval(substr($data[0], 1))
					));

					if($entity){
						// 削除済ID
						throw new Exception("[".$data[0]."]削除済のIDです。追加・新規登録の場合はIDを空欄にしてください：".$exp['exp_id'][$elem], 210);
					}else{
						// 未登録ID
						throw new Exception("[".$data[0]."]の追加項目にIDが入力されています。CSVをご確認ください：".$exp['exp_id'][$elem], 210);
					}
				}
			}
			$this->get('logger')->error("***updateExpTerm:1***");

			// センター頻度の年別データ
			$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
					'mainTermId' => intval(substr($data[0], 1)),
					'subTermId' => intval($exp_id),
					'yougoFlag' => '4',
					'deleteFlag' => FALSE
			),
					array('year' => 'ASC'));

			$year = $entityVersion->getYear();
			$idx = 0;
			$center_freq_sum = 0;
			foreach ($entityCenter as $entityCenterRec){
				if($entityCenterRec->getYear() == ($year+$idx)){
					$arr_freq = $this->splitCenterFreq($exp['exp_center_frequency'.($year+$idx)][$elem]);

					if(count($arr_freq) == 2){
						$center_freq_sum += intval($arr_freq[0]) + intval($arr_freq[1]);

						$sql = "UPDATE `Center` SET `main_exam` = " . $arr_freq[0] . ",`sub_exam` = " . $arr_freq[1];
						$sql .= ",`modify_date` = NOW() WHERE `main_term_id` = ".intval(substr($data[0], 1))." AND `sub_term_id` = ".intval($exp_id)." AND `yougo_flag` = 4 AND `year` = " . ($year+$idx) . " AND `delete_flag` = 0;";
						fputs($handle_center, $sql."\n");
					}
				}
				$idx++;
			}

			$this->get('logger')->error("***updateExpTerm:2***");
			// センター頻度未登録の場合
			if(!$entityCenter){
				for ($idx=0;$idx<10;$idx++){
					$arr_freq = $this->splitCenterFreq($exp['exp_center_frequency'.($year+$idx)][$elem]);

					if(count($arr_freq) == 2){
						$center_freq_sum += intval($arr_freq[0]) + intval($arr_freq[1]);
					}
				}
			}

			$this->get('logger')->error("***updateExpTerm:3***");
			// 解説内用語情報の更新(頻度)
			$entity->setTextFrequency((($data[119] == "")||($exp['exp_text_frequency'][$elem] == "")) ? 0 : $exp['exp_text_frequency'][$elem]);
			$entity->setCenterFrequency($center_freq_sum);
			$entity->setNewsExam($exp['exp_news_exam'][$elem] == "N" ? 1 : 0);

			$sql = "UPDATE `ExplainIndex` SET `text_frequency` = " . ((($data[119] == "")||($exp['exp_text_frequency'][$elem] == "")) ? "0" : $exp['exp_text_frequency'][$elem]) . ",`center_frequency` = " . $center_freq_sum . ",`news_exam` = " . ($exp['exp_news_exam'][$elem] == "N" ? 1 : 0);

			$this->get('logger')->error("***updateExpTerm:4***");
			if($update_all){
				// 解説内用語情報の更新
				$entity->setIndexAddLetter(($data[118] == "") ? "" : $exp['exp_term'][$elem]);
				$entity->setIndexKana($exp['exp_index_kana'][$elem]);

				if($update_mode == 'new'){
					$entity->setIndexTerm($exp_term[$elem]);
					$entity->setMainTermId(intval(substr($data[0], 1)));
					$entity->setNombre(0);
					$em->persist($entity);
					$em->flush();

				}else{
					$sql .= ",`index_add_letter` = '" . ($data[118] == "") ? "" : $exp['exp_term'][$elem] . "',`index_kana` = '" . $exp['exp_index_kana'][$elem] . "'";
				}
				$this->get('logger')->error("***updateExpTerm:5***");
			}

			$new_id = $entity->getId();
			array_push($rtn_exp_id,$new_id);

			$this->get('logger')->error("***updateExpTerm:6***");
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
					}else{
						$entityCenter->setMainExam(0);
						$entityCenter->setSubExam(0);
					}

					$em->persist($entityCenter);
					$em->flush();
				}
				$this->get('logger')->error("***updateExpTerm:7***");
			}

			if($update_mode == 'update'){
				$sql .= ",`modify_date` = NOW() WHERE `id` = ".intval($exp_id)." AND `delete_flag` = 0;";
				fputs($handle, $sql."\n");
			}

			$elem++;
		}

		return $rtn_exp_id;

	}

	private function insertRefTerm($data,$offset,$em,$handle, $newTermId){

		if($data[329+$offset] == ""){
			return false;
		}

		$sql = "INSERT INTO `Refer` (`id`, `main_term_id`, `refer_term_id`, `nombre`, `create_date`, `modify_date`, `delete_date`, `delete_flag`) VALUES";
		$sql .= "(null,";
		$sql .= $newTermId.",";
		$sql .= intval(substr($data[329+$offset], 1)).",";
		$sql .= "0,";
		$sql .= "NOW(),";
		$sql .= "null,";
		$sql .= "null,";
		$sql .= "0);";

		fputs($handle, $sql."\n");

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

					$sql = "UPDATE ".$table." SET `delete_flag` = 1,`modify_date` = NOW(),`delete_date` = NOW()";
					$sql .= " WHERE `main_term_id` = ". intval(substr($data[0], 1)) . " AND `refer_term_id` = ".$entity_rec->getReferTermId().";";
					fputs($handle, $sql."\n");
				}

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

	private function registerUploadHistory($em, $versionId,$userid,$files,$contents){
		try {
			foreach($files as $ele_file){
				$em->getConnection()->beginTransaction();
				$entity = new Upload();
				$entity->setVersionId($versionId);
				$entity->setUserId($userid);
				$entity->setFileName($ele_file['name']);
				$entity->setCreateDate(new \DateTime());
				$entity->setContents($contents);

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