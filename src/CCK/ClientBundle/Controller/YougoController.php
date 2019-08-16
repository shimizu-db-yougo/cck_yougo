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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use CCK\CommonBundle\Entity\MainTerm;
use CCK\CommonBundle\Entity\SubTerm;
use CCK\CommonBundle\Entity\Synonym;
use CCK\CommonBundle\Entity\Refer;

/**
 * yougo controller.
 * 用語管理コントローラー
 *
 */
class YougoController extends BaseController {

	/**
	 * @var session key
	 */
	const SES_REQUEST_GENKO_PARAMS = "ses_request_genko_params_key";

	const SES_SEARCH_CURRICULUM_KEY = "ses_search_curriculum_key";
	const SES_SEARCH_VERSION_KEY = "ses_search_version_key";
	const SES_SEARCH_HEN_KEY = "ses_search_hen_key";
	const SES_SEARCH_SHO_KEY = "ses_search_sho_key";
	const SES_SEARCH_DAI_KEY = "ses_search_dai_key";
	const SES_SEARCH_CHU_KEY = "ses_search_chu_key";
	const SES_SEARCH_KO_KEY = "ses_search_ko_key";
	const SES_SEARCH_NOMBRE_KEY = "ses_search_nombre_key";
	const SES_SEARCH_TEXT_FREQ_KEY = "ses_search_text_freq_key";
	const SES_SEARCH_CENTER_FREQ_KEY = "ses_search_center_freq_key";
	const SES_SEARCH_NEWS_EXAM_KEY = "ses_search_news_exam_key";
	const SES_SEARCH_TERM_KEY = "ses_search_term_key";
	const SES_SEARCH_LIST_COUNT_KEY = "ses_search_list_count_key";
	const SES_SORT_ORDER_KEY = "ses_sort_order_key";
	const SES_SORT_FIELD_KEY = "ses_sort_field_key";

	const SES_SEARCH_RESPONCE_TERM_ID_KEY = "ses_search_responce_term_id_key";

	/**
	 * genko page session key
	 */
	const SES_GENKO_PAGE_KEY = "ses_genko_page_key";

	/**
	 * @Route("/", name="client.yougo.list")
	 */
	public function listAction(Request $request){
		// session remove
		$this->sessionRemove($request);
		return $this->redirect($this->generateUrl('client.yougo.index'));
	}

	/**
	 * @Route("/index", name="client.yougo.index")
	 * @Template()
	 */
	public function indexAction(Request $request) {
		// session
		$session = $request->getSession();

		// get user information
		$user = $this->getUser();

		// 教科
		if($request->query->has('curriculum')){
			$curriculum = $request->query->get('curriculum');
		}else{
			$curriculum = $session->get(self::SES_SEARCH_CURRICULUM_KEY);
		}

		// 版
		if($request->query->has('version')){
			$version = $request->query->get('version');
		}else{
			$version = $session->get(self::SES_SEARCH_VERSION_KEY);
		}

		// 編見出し
		if($request->query->has('hen')){
			$hen = $request->query->get('hen');
		}else{
			$hen = $session->get(self::SES_SEARCH_HEN_KEY);
		}

		// 章見出し
		if($request->query->has('sho')){
			$sho = $request->query->get('sho');
		}else{
			$sho = $session->get(self::SES_SEARCH_SHO_KEY);
		}

		// 大見出し
		if($request->query->has('dai')){
			$dai = $request->query->get('dai');
		}else{
			$dai = $session->get(self::SES_SEARCH_DAI_KEY);
		}

		// 中見出し
		if($request->query->has('chu')){
			$chu = $request->query->get('chu');
		}else{
			$chu = $session->get(self::SES_SEARCH_CHU_KEY);
		}

		// 小見出し
		if($request->query->has('ko')){
			$ko = $request->query->get('ko');
		}else{
			$ko = $session->get(self::SES_SEARCH_KO_KEY);
		}

		// ノンブル
		if($request->query->has('nombre')){
			$nombre = $request->query->get('nombre');
		}else{
			$nombre = $session->get(self::SES_SEARCH_NOMBRE_KEY);
		}

		// 教科書頻度
		if($request->query->has('text_freq')){
			$text_freq = $request->query->get('text_freq');
		}else{
			$text_freq = $session->get(self::SES_SEARCH_TEXT_FREQ_KEY);
		}

		// センター頻度
		if($request->query->has('center_freq')){
			$center_freq = $request->query->get('center_freq');
		}else{
			$center_freq = $session->get(self::SES_SEARCH_CENTER_FREQ_KEY);
		}

		// ニュース検定
		if($request->query->has('news_exam')){
			$news_exam = $request->query->get('news_exam');
		}else{
			$news_exam = $session->get(self::SES_SEARCH_NEWS_EXAM_KEY);
		}

		// 用語
		if($request->query->has('term')){
			$term = $request->query->get('term');
		}else{
			$term = $session->get(self::SES_SEARCH_TERM_KEY);
		}

		// 一覧表示件数
		if($request->query->has('list_count')){
			$list_count = $request->query->get('list_count');
		}else{
			$list_count = $session->get(self::SES_SEARCH_LIST_COUNT_KEY);
		}
		if(($list_count == '')||(!isset($list_count))){
			$list_count = 20;
		}

		$page = ($request->query->has('page') && $request->query->get('page') != '') ? $request->query->get('page') : 1; //pageのGETパラメータを直接設定(デフォルト1)

		$sort_field = '';
		$sort_order = '';
		if($request->request->has('sort_up')){
			$sort_field = $request->request->get('field');
			$sort_order = ' ASC';
		}
		if($request->request->has('sort_down')){
			$sort_field = $request->request->get('field');
			$sort_order = ' DESC';
		}

		$sort_order_link = $session->get(self::SES_SORT_ORDER_KEY);
		$sort_field = $session->get(self::SES_SORT_FIELD_KEY);
		if($request->query->has('field')){
			if(($sort_order_link == '')||($sort_order_link == 'down')){
				$sort_order_link = 'up';
				$sort_field = $request->query->get('field');
				$sort_order = ' ASC';
			}elseif($sort_order_link == 'up'){
				$sort_order_link = 'down';
				$sort_field = $request->query->get('field');
				$sort_order = ' DESC';
			}
			$sort_field = $request->query->get('field');
		}

		$entities = array();
		$entities = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:MainTerm')->getYougoList($curriculum, $version, $hen, $sho, $dai, $chu, $ko, $nombre, $text_freq, $center_freq, $news_exam, $term, $sort_field, $sort_order);

		// pagination
		$pagination = $this->createPagination($request, $entities, $list_count, $page);

		// session key
		$session->set(self::SES_SEARCH_CURRICULUM_KEY, $curriculum);
		$session->set(self::SES_SEARCH_VERSION_KEY, $version);
		$session->set(self::SES_SEARCH_HEN_KEY, $hen);
		$session->set(self::SES_SEARCH_SHO_KEY, $sho);
		$session->set(self::SES_SEARCH_DAI_KEY, $dai);
		$session->set(self::SES_SEARCH_CHU_KEY, $chu);
		$session->set(self::SES_SEARCH_KO_KEY, $ko);
		$session->set(self::SES_SEARCH_NOMBRE_KEY, $nombre);
		$session->set(self::SES_SEARCH_TEXT_FREQ_KEY, $text_freq);
		$session->set(self::SES_SEARCH_CENTER_FREQ_KEY, $center_freq);
		$session->set(self::SES_SEARCH_NEWS_EXAM_KEY, $news_exam);
		$session->set(self::SES_SEARCH_TERM_KEY, $term);
		$session->set(self::SES_SEARCH_LIST_COUNT_KEY, $list_count);
		$session->set(self::SES_SORT_ORDER_KEY, $sort_order_link);
		$session->set(self::SES_SORT_FIELD_KEY, $sort_field);

		$arr_term_id = array();
		foreach ($entities as $entitiy){
			array_push($arr_term_id,$entitiy['term_id']);
		}

		$session->set(self::SES_SEARCH_RESPONCE_TERM_ID_KEY, $arr_term_id);


		$cur_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findBy(array(
				'deleteFlag' => FALSE
		));
		$ver_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findBy(array(
				'deleteFlag' => FALSE
		));
		$hen_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'headerId' => '1',
				'deleteFlag' => FALSE
		));
		$sho_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'headerId' => '2',
				'deleteFlag' => FALSE
		));
		$dai_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'headerId' => '3',
				'deleteFlag' => FALSE
		));
		$chu_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'headerId' => '4',
				'deleteFlag' => FALSE
		));
		$ko_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'headerId' => '5',
				'deleteFlag' => FALSE
		));

		return array(
				'pagination' => $pagination,
				'cur_page' => $page,
				'currentUser' => ['user_id' => $this->getUser()->getUserId(), 'name' => $this->getUser()->getName()],
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
				'hen_list' => $hen_list,
				'sho_list' => $sho_list,
				'dai_list' => $dai_list,
				'chu_list' => $chu_list,
				'ko_list' => $ko_list,
				'curriculum' => $curriculum,
				'version' => $version,
				'search_hen' => $hen,
				'search_sho' => $sho,
				'search_dai' => $dai,
				'search_chu' => $chu,
				'search_ko' => $ko,
				'nombre' => $nombre,
				'text_freq' => $text_freq,
				'center_freq' => $center_freq,
				'news_exam' => ($news_exam) ? true : false,
				'term' => $term,
				'list_count' => $list_count,
				'sort_order' => $sort_order_link,
				'sort_field' => $sort_field
		);
	}

	/**
	 * @Route("/yougo/curriculum/ajax", name="client.yougo.curriculum.ajax")
	 */
	public function getCurriculumAjaxAction(Request $request){
		if($request->request->has('cur')){
			$cur = $request->request->get('cur');
			$ver = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->getVersions($cur);
			$response = new JsonResponse($ver);
		}else{
			$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
		}

		return $response;
	}

	/**
	 * @Route("/yougo/hen/ajax", name="client.yougo.hen.ajax")
	 */
	public function getHenAjaxAction(Request $request){
		if($request->request->has('version')){
			$ver = $request->request->get('version');
			$hen = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->getHenMidashi($ver);
			$response = new JsonResponse($hen);
		}else{
			$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
		}

		return $response;
	}

	/**
	 * @Route("/yougo/sho/ajax", name="client.yougo.sho.ajax")
	 */
	public function getShoAjaxAction(Request $request){
		if($request->request->has('hen')){
			$ver = $request->request->get('version');
			$hen = $request->request->get('hen');
			$sho = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->getShoMidashi($ver, $hen);
			$response = new JsonResponse($sho);
		}else{
			$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
		}

		return $response;
	}

	/**
	 * @Route("/yougo/dai/ajax", name="client.yougo.dai.ajax")
	 */
	public function getDaiAjaxAction(Request $request){
		if($request->request->has('sho')){
			$ver = $request->request->get('version');
			$hen = $request->request->get('hen');
			$sho = $request->request->get('sho');
			$dai = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->getDaiMidashi($ver, $hen, $sho);
			$response = new JsonResponse($dai);
		}else{
			$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
		}

		return $response;
	}

	/**
	 * @Route("/yougo/chu/ajax", name="client.yougo.chu.ajax")
	 */
	public function getChuAjaxAction(Request $request){
		if($request->request->has('dai')){
			$ver = $request->request->get('version');
			$hen = $request->request->get('hen');
			$sho = $request->request->get('sho');
			$dai = $request->request->get('dai');
			$chu = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->getChuMidashi($ver, $hen, $sho, $dai);
			$response = new JsonResponse($chu);
		}else{
			$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
		}

		return $response;
	}

	/**
	 * @Route("/yougo/ko/ajax", name="client.yougo.ko.ajax")
	 */
	public function getKoAjaxAction(Request $request){
		if($request->request->has('chu')){
			$ver = $request->request->get('version');
			$hen = $request->request->get('hen');
			$sho = $request->request->get('sho');
			$dai = $request->request->get('dai');
			$chu = $request->request->get('chu');
			$ko = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->getKoMidashi($ver, $hen, $sho, $dai, $chu);
			$response = new JsonResponse($ko);
		}else{
			$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
		}

		return $response;
	}

	/**
	 * @Route("/yougo/header/ajax", name="client.yougo.header.ajax")
	 */
	public function getHeaderAjaxAction(Request $request){
		if($request->request->has('id')){
			$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findOneBy(array(
					'id' => $request->request->get('id'),
					'deleteFlag' => FALSE
			));
			$response = new JsonResponse(['hen'=>$entity->getHen(),'sho'=>$entity->getSho(),'dai'=>$entity->getDai(),'chu'=>$entity->getChu(),'ko'=>$entity->getKo()]);
		}else{
			$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
		}

		return $response;
	}

	/**
	 * @Route("/preview/{term_id}", name="client.yougo.preview")
	 * @Template()
	 */
	public function previewAction(Request $request, $term_id) {

		$em = $this->getDoctrine()->getManager();

		// 主用語
		$entity = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetail($term_id);

		if(!$entity){
			return $this->redirect($this->generateUrl('client.yougo.list'));
		}

		// サブ用語
		$entitySub = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSubterm($term_id);

		// 指矢印用語
		$entityRef = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfRefer($term_id);

		// 同対類
		$entitySyn = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSynonym($term_id);

		$cur_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findBy(array(
				'deleteFlag' => FALSE
		));
		$ver_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findBy(array(
				'deleteFlag' => FALSE
		));

		return array(
				'yougo' => $entity,
				'subterm' => $entitySub,
				'synonym' => $entitySyn,
				'refer' => $entityRef,
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
		);
	}

	/**
	 * @Route("/preview_search", name="client.yougo.preview.search")
	 * @Template("CCKClientBundle:yougo:preview.search.html.twig")
	 */
	public function previewSearchAction(Request $request) {
		// session
		$session = $request->getSession();

		$em = $this->getDoctrine()->getManager();

		$arr_term_id = $session->get(self::SES_SEARCH_RESPONCE_TERM_ID_KEY);

		$arr_mainterm_entity = array();
		$arr_subterm_entity = array();
		$arr_refterm_entity = array();
		$arr_synterm_entity = array();
		foreach($arr_term_id as $term_id){
			// 主用語
			$entity = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetail($term_id);

			if(!$entity){
				return $this->redirect($this->generateUrl('client.yougo.list'));
			}else{
				array_push($arr_mainterm_entity, $entity);
			}

			// サブ用語
			$entitySub = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSubterm($term_id);
			array_push($arr_subterm_entity, $entitySub);

			// 指矢印用語
			$entityRef = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfRefer($term_id);
			array_push($arr_refterm_entity, $entityRef);

			// 同対類
			$entitySyn = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSynonym($term_id);
			array_push($arr_synterm_entity, $entitySyn);
		}

		$cur_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findBy(array(
				'deleteFlag' => FALSE
		));
		$ver_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findBy(array(
				'deleteFlag' => FALSE
		));

		return array(
				'arr_yougo' => $arr_mainterm_entity,
				'arr_subterm' => $arr_subterm_entity,
				'arr_synonym' => $arr_synterm_entity,
				'arr_refer' => $arr_refterm_entity,
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
		);
	}

	/**
	 * @Route("/new", name="client.yougo.new")
	 * @Method("POST|GET")
	 * @Template()
	 */
	public function newAction(Request $request){
		// 登録画面から遷移した場合
		if($request->request->has('add_main_term')){
			$main_term = $request->request->get('add_main_term');
			$sub_term = $request->request->get('add_sub_term');
			print($main_term);
			print_r($sub_term);

			//DB登録後、一覧画面へ遷移する
			exit();
		}


		$session = $request->getSession();

		// get user information
		$user = $this->getUser();

		$em = $this->getDoctrine()->getManager();

		$entityMain = new MainTerm();
		$entitySub = new SubTerm();
		$entitySyn = new Synonym();
		$entityRef = new Refer();

		// 用語IDの発番
		$maxTermIDRec = $em->getRepository('CCKCommonBundle:MainTerm')->getNewTermID();

		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		try{
			$entityMain->setTermId((int)$maxTermIDRec[0]['term_id'] + 1);
			$entityMain->setCurriculumId(0);
			$entityMain->setHeaderId(0);
			$entityMain->setPrintOrder(0);
			$entityMain->setRedLetter(0);
			$entityMain->setTextFrequency(0);
			$entityMain->setCenterFrequency(0);
			$entityMain->setNewsExam(0);
			$entityMain->setNombre(0);
			$entityMain->setIllustNombre(0);
			$entityMain->setDeleteFlag(true);

			$em->persist($entityMain);
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

		$cur_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findBy(array(
				'deleteFlag' => FALSE
		));
		$ver_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findBy(array(
				'deleteFlag' => FALSE
		));
		$hen_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'headerId' => '1',
				'deleteFlag' => FALSE
		));
		$sho_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'headerId' => '2',
				'deleteFlag' => FALSE
		));
		$dai_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'headerId' => '3',
				'deleteFlag' => FALSE
		));
		$chu_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'headerId' => '4',
				'deleteFlag' => FALSE
		));
		$ko_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'headerId' => '5',
				'deleteFlag' => FALSE
		));

		// 掲載順に表示する用語
		$printOrderList = $em->getRepository('CCKCommonBundle:MainTerm')->findBy(array(
				'deleteFlag' => false
		));

		return array(
				'term_id' => '',
				'yougo' => $entityMain,
				'yougo_sub' => $entitySub,
				'yougo_syn' => $entitySyn,
				'yougo_ref' => $entityRef,
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
				'hen_list' => $hen_list,
				'sho_list' => $sho_list,
				'dai_list' => $dai_list,
				'chu_list' => $chu_list,
				'ko_list' => $ko_list,
				'curriculum' => '',
				'version' => '',
				'select_hen' => '',
				'select_sho' => '',
				'select_dai' => '',
				'select_chu' => '',
				'select_ko' => '',
				'print_order_list' => $printOrderList,
				'main_term_list' => '',
		);
	}

	/**
	 * @Route("/edit/save/ajax", name="client.edit.save.ajax")
	 * @Method("POST")
	 */
	public function saveAjaxAction(Request $request){
		$this->get('logger')->error("***saveAjaxAction start***");
		$this->get('logger')->error($request->request->get('main_term'));

		$ret = ['result'=>'ok','error'=>''];

		if(!($request->request->has('main_term'))){
			$ret = ['result'=>'ng','error'=>'parameter error'];
			$response = new JsonResponse($ret);
			return $response;
		}

		if($this->saveMainTerm($request,$ret) == false){
			$response = new JsonResponse($ret);
			return $response;
		}

		if($this->saveSubTerm($request,$ret) == false){
			$response = new JsonResponse($ret);
			return $response;
		}

		if($this->saveSynTerm($request,$ret) == false){
			$response = new JsonResponse($ret);
			return $response;
		}

		if($this->saveRefTerm($request,$ret) == false){
			$response = new JsonResponse($ret);
			return $response;
		}

		$response = new JsonResponse($ret);
		return $response;
	}

	private function saveMainTerm($request,&$ret){
		$return_flag = true;

		$main_term = $request->request->get('main_term');
		$update_mode = $request->request->get('update_mode');

		$em = $this->get('doctrine.orm.entity_manager');
		if($update_mode == 'edit'){
			$entity = $em->getRepository('CCKCommonBundle:MainTerm')->findOneBy(array(
					'termId' => $request->request->get('term_id'),
					'deleteFlag' => FALSE
			));
		}else{
			$entity = $em->getRepository('CCKCommonBundle:MainTerm')->findOneBy(array(
					'termId' => $request->request->get('term_id')
			));
		}

		$em->getConnection()->beginTransaction();

		try{
			$entity->setMainTerm($main_term);
			$entity->setRedLetter(($request->request->get('red_letter') == 'true') ? true : false);
			$entity->setKana($request->request->get('kana'));
			$entity->setTextFrequency($request->request->get('text_freq'));
			$entity->setCenterFrequency($request->request->get('center_freq'));
			$entity->setNewsExam(($request->request->get('news_exam') == 'true') ? true : false);
			$entity->setDelimiter($request->request->get('delimiter'));
			$entity->setWesternLanguage($request->request->get('western_language'));
			$entity->setBirthYear($request->request->get('birth_year'));
			$entity->setIndexAddLetter($request->request->get('index_add_letter'));
			$entity->setIndexKana($request->request->get('index_kana'));
			$entity->setIndexOriginal($request->request->get('index_original'));
			$entity->setIndexOriginalKana($request->request->get('index_original_kana'));
			$entity->setIndexAbbreviation($request->request->get('index_abbreviation'));
			$entity->setTermExplain($request->request->get('term_explain'));
			$entity->setIllustFilename($request->request->get('illust_filename'));
			$entity->setIllustCaption($request->request->get('illust_caption'));
			$entity->setIllustKana($request->request->get('illust_kana'));
			$entity->setHandover($request->request->get('handover'));
			if($update_mode == 'new'){
				$entity->setCurriculumId(false);
				$entity->setUserId($this->getUser()->getUserId());
				$entity->setDeleteFlag(false);

			}

			$em->flush();
			$em->getConnection()->commit();
		} catch (\Exception $e){
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			$ret = ['result'=>'ng','error'=>'MainTermDB error termId:'.$request->request->get('term_id')];
			$return_flag = false;
		}
		return $return_flag;
	}

	private function saveSubTerm($request,&$ret){
		$return_flag = true;

		$subterm = $request->request->get('subterm');

		if(is_null($subterm)){
			return $return_flag;
		}

		$idx = 0;
		foreach($subterm['sub_term_id'] as $ele_subterm){
			$this->get('logger')->error("***sub_term_elem***".$ele_subterm);

			$em = $this->get('doctrine.orm.entity_manager');
			$entity = $em->getRepository('CCKCommonBundle:SubTerm')->findOneBy(array(
			'id' => $ele_subterm,
			'deleteFlag' => FALSE
			));

			$em->getConnection()->beginTransaction();

			try{
				$entity->setSubTerm($subterm['sub_term'][$idx]);
				$entity->setRedLetter(($subterm['red_letter'][$idx] == 'true') ? true : false);
				$entity->setKana($subterm['kana'][$idx]);
				$entity->setTextFrequency($subterm['text_freq'][$idx]);
				$entity->setCenterFrequency($subterm['center_freq'][$idx]);
				$entity->setNewsExam(($subterm['news_exam'][$idx] == 'true') ? true : false);
				$entity->setDelimiter($subterm['delimiter'][$idx]);
				$entity->setDelimiterKana($subterm['delimiter_kana'][$idx]);
				$entity->setIndexAddLetter($subterm['index_add_letter'][$idx]);
				$entity->setIndexKana($subterm['index_kana'][$idx]);

				$em->flush();
				$em->getConnection()->commit();
			} catch (\Exception $e){
				$em->getConnection()->rollback();
				$em->close();

				// log
				$this->get('logger')->error($e->getMessage());
				$this->get('logger')->error($e->getTraceAsString());

				$ret = ['result'=>'ng','error'=>'SubTermDB error sub_term_id:'.$ele_subterm];
				$return_flag = false;
				return $return_flag;
			}
			$idx++;
		}

		return $return_flag;
	}

	private function saveSynTerm($request,&$ret){
		$return_flag = true;

		$synterm = $request->request->get('synterm');

		if(is_null($synterm)){
			return $return_flag;
		}

		$idx = 0;
		foreach($synterm['syn_term_id'] as $ele_synterm){
			$this->get('logger')->error("***syn_term_elem***".$ele_synterm);

			$em = $this->get('doctrine.orm.entity_manager');
			$entity = $em->getRepository('CCKCommonBundle:Synonym')->findOneBy(array(
					'id' => $ele_synterm,
					'deleteFlag' => FALSE
			));

			$em->getConnection()->beginTransaction();

			try{
				$entity->setSynonymId($synterm['synonym_id'][$idx]);
				$entity->setTerm($synterm['term'][$idx]);
				$entity->setRedLetter(($synterm['red_letter'][$idx] == 'true') ? true : false);
				$entity->setTextFrequency($synterm['text_freq'][$idx]);
				$entity->setCenterFrequency($synterm['center_freq'][$idx]);
				$entity->setNewsExam(($synterm['news_exam'][$idx] == 'true') ? true : false);
				$entity->setDelimiter($synterm['delimiter'][$idx]);
				$entity->setIndexAddLetter($synterm['index_add_letter'][$idx]);
				$entity->setIndexKana($synterm['index_kana'][$idx]);

				$em->flush();
				$em->getConnection()->commit();
			} catch (\Exception $e){
				$em->getConnection()->rollback();
				$em->close();

				// log
				$this->get('logger')->error($e->getMessage());
				$this->get('logger')->error($e->getTraceAsString());

				$ret = ['result'=>'ng','error'=>'SynonymDB error id:'.$ele_synterm];
				$return_flag = false;
				return $return_flag;
			}
			$idx++;
		}

		return $return_flag;
	}

	private function saveRefTerm($request,&$ret){
		$return_flag = true;

		$refterm = $request->request->get('refterm');

		if(is_null($refterm)){
			return $return_flag;
		}

		$idx = 0;
		foreach($refterm['ref_idx'] as $ele_refterm){
			$this->get('logger')->error("***ref_idx_elem***".$ele_refterm);

			$em = $this->get('doctrine.orm.entity_manager');
			$entity = $em->getRepository('CCKCommonBundle:Refer')->findOneBy(array(
					'id' => $ele_refterm,
					'deleteFlag' => FALSE
			));

			$em->getConnection()->beginTransaction();

			try{
				$entity->setReferTermId($refterm['ref_term_id'][$idx]);

				$em->flush();
				$em->getConnection()->commit();
			} catch (\Exception $e){
				$em->getConnection()->rollback();
				$em->close();

				// log
				$this->get('logger')->error($e->getMessage());
				$this->get('logger')->error($e->getTraceAsString());

				$ret = ['result'=>'ng','error'=>'ReferDB error id:'.$ele_refterm];
				$return_flag = false;
				return $return_flag;
			}
			$idx++;
		}

		return $return_flag;
	}

	/**
	 * @Route("/edit/{term_id}", name="client.yougo.edit")
	 * @Method("POST|GET")
	 * @Template()
	 */
	public function editAction(Request $request, $term_id){
		// 登録画面から遷移した場合
		if($request->request->has('add_main_term')){
			$main_term = $request->request->get('add_main_term');
			$sub_term = $request->request->get('add_sub_term');
			print_r($main_term);
			print_r($sub_term);

			//DB登録後、一覧画面へ遷移する
			exit();
		}


		$session = $request->getSession();

		// get user information
		$user = $this->getUser();

		$id = (int) $term_id;

		$em = $this->getDoctrine()->getManager();

		$entityMain = $em->getRepository('CCKCommonBundle:MainTerm')->findOneBy(array(
				'termId' => $id,
				'deleteFlag' => false
		));

		if(!$entityMain){
			return $this->redirect($this->generateUrl('client.yougo.list'));
		}

		/*$entitySub = $em->getRepository('CCKCommonBundle:SubTerm')->findBy(array(
				'mainTermId' => $id,
				'deleteFlag' => false
		));*/
		$entitySub = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSubterm($id);
		$entitySyn = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSynonym($id);
		$entityRef = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfRefer($id);

		$cur_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findBy(array(
				'deleteFlag' => FALSE
		));
		$ver_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findBy(array(
				'deleteFlag' => FALSE
		));
		$hen_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'headerId' => '1',
				'deleteFlag' => FALSE
		));
		$sho_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'headerId' => '2',
				'deleteFlag' => FALSE
		));
		$dai_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'headerId' => '3',
				'deleteFlag' => FALSE
		));
		$chu_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'headerId' => '4',
				'deleteFlag' => FALSE
		));
		$ko_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'headerId' => '5',
				'deleteFlag' => FALSE
		));

		// 選択する教科
		$entityVersion = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findOneBy(array(
				'id' => $entityMain->getCurriculumId(),
				'deleteFlag' => FALSE
		));

		// 選択する見出し
		$entityHeader = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findOneBy(array(
				'id' => $entityMain->getHeaderId(),
				'deleteFlag' => FALSE
		));

		// 掲載順に表示する用語
		$printOrderList = $em->getRepository('CCKCommonBundle:MainTerm')->findBy(array(
				'headerId' => $entityMain->getHeaderId(),
				'deleteFlag' => false
		));

		// 指矢印選択に表示する用語
		$mainTermList = $em->getRepository('CCKCommonBundle:MainTerm')->findBy(array(
				'deleteFlag' => false
		));

		return array(
				'term_id' => $id,
				'yougo' => $entityMain,
				'yougo_sub' => $entitySub,
				'yougo_syn' => $entitySyn,
				'yougo_ref' => $entityRef,
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
				'hen_list' => $hen_list,
				'sho_list' => $sho_list,
				'dai_list' => $dai_list,
				'chu_list' => $chu_list,
				'ko_list' => $ko_list,
				'curriculum' => $entityVersion->getCurriculumId(),
				'version' => $entityMain->getCurriculumId(),
				'select_hen' => $entityHeader->getHen(),
				'select_sho' => $entityHeader->getSho(),
				'select_dai' => $entityHeader->getDai(),
				'select_chu' => $entityHeader->getChu(),
				'select_ko' => $entityHeader->getKo(),
				'print_order_list' => $printOrderList,
				'main_term_list' => $mainTermList,
		);
	}

	/**
	 * @Route("/yougo/center/ajax", name="client.yougo.center.ajax")
	 */
	public function getCenterAjaxAction(Request $request){
		if($request->request->has('term_id')){
			$term_id = $request->request->get('term_id');
			$yougo_flag = $request->request->get('yougo_flag');
			$center_point = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Center')->getCenterPoints($term_id,$yougo_flag);
			$response = new JsonResponse($center_point);
		}else{
			$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
		}

		return $response;
	}

	/**
	 * @Route("/edit/confirm", name="client.yougo.edit.confirm")
	 * @Method("POST|GET")
	 */
	public function editConfirmAction(Request $request){
		$this->get('logger')->error("***editConfirmAction start***");
		$session = $request->getSession();

		// get user information
		$user = $this->getUser();

		$em = $this->getDoctrine()->getManager();

		if($request->request->has('add_main_term')){
			$main_term = $request->request->get('add_main_term');
		}

		$add_akamoji = array();
		if($request->request->has('add_akamoji')){
			$add_akamoji = explode(",", $request->request->get('add_akamoji'));
		}

		$this->get('logger')->error("***editConfirmAction***");
		$this->get('logger')->error($main_term);
		print($main_term);
		exit();

	}

	/**
	 * @Route("/genko/yogotest", name="client.genko.yogotest")
	 * @Method("POST|GET")
	 * @Template()
	 */
	public function yogotestAction(Request $request){
		$session = $request->getSession();

		// get user information
		$user = $this->getUser();

		$em = $this->getDoctrine()->getManager();

		$yogo = array();
		if($request->request->has('add_yogo')){
			$yogo = explode(",", $request->request->get('add_yogo'));
		}

		$add_akamoji = array();
		if($request->request->has('add_akamoji')){
			$add_akamoji = explode(",", $request->request->get('add_akamoji'));
		}

		$add_hindo = array();
		if($request->request->has('add_hindo')){
			$add_hindo = explode(",", $request->request->get('add_hindo'));
		}

		$add_kaisetsu = array();
		if($request->request->has('add_kaisetsu')){
			$add_kaisetsu = explode(",", $request->request->get('add_kaisetsu'));
		}

		$add_center = array();
		if($request->request->has('add_center')){
			$add_center= explode(",", $request->request->get('add_center'));
		}

		$cur_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findBy(array(
				'deleteFlag' => FALSE
		));
		$ver_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findBy(array(
				'deleteFlag' => FALSE
		));

		return array(
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
				'yogos' => $yogo,
				'akamojis' => $add_akamoji,
				'hindos' => $add_hindo,
				'kaisetsus' => $add_kaisetsu,
				'centers' => $add_center,
		);
	}

	/**
	 * session data remove
	 */
	private function sessionRemove($request){
		$session = $request->getSession();
		$session->remove(self::SES_REQUEST_GENKO_PARAMS);
		$session->remove(self::SES_SEARCH_CURRICULUM_KEY);
		$session->remove(self::SES_SEARCH_VERSION_KEY);
		$session->remove(self::SES_SEARCH_HEN_KEY);
		$session->remove(self::SES_SEARCH_SHO_KEY);
		$session->remove(self::SES_SEARCH_DAI_KEY);
		$session->remove(self::SES_SEARCH_CHU_KEY);
		$session->remove(self::SES_SEARCH_KO_KEY);
		$session->remove(self::SES_SEARCH_NOMBRE_KEY);
		$session->remove(self::SES_SEARCH_TEXT_FREQ_KEY);
		$session->remove(self::SES_SEARCH_CENTER_FREQ_KEY);
		$session->remove(self::SES_SEARCH_NEWS_EXAM_KEY);
		$session->remove(self::SES_SEARCH_TERM_KEY);
		$session->remove(self::SES_SEARCH_LIST_COUNT_KEY);
		$session->remove(self::SES_SORT_ORDER_KEY);
		$session->remove(self::SES_SORT_FIELD_KEY);
		$session->remove(self::SES_SEARCH_RESPONCE_TERM_ID_KEY);
	}
}
