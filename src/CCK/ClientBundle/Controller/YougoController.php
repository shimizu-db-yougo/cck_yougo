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
				'list_count' => $list_count
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
	 * @Route("/preview/{id}", name="client.yougo.preview")
	 * @Template()
	 */
	public function previewAction(Request $request, $id) {

		$id = (int) $id;

		$em = $this->getDoctrine()->getManager();

		$entity = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoList(null, null, null, null, null, null, null, null, null, null, null, null, null, null, $id);

		if(!$entity){
			return $this->redirect($this->generateUrl('client.yougo.list'));
		}

		$cur_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findBy(array(
				'deleteFlag' => FALSE
		));
		$ver_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findBy(array(
				'deleteFlag' => FALSE
		));

		return array(
				'yougoEntity' => $entity,
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
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

	}
}
