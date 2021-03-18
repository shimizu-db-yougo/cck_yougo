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
use CCK\CommonBundle\Entity\ExplainIndex;
use CCK\CommonBundle\Entity\ExplainIndexTmp;
use CCK\CommonBundle\Entity\Center;
use CCK\CommonBundle\Entity\CenterTmp;

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
	const SES_SEARCH_SUB_TERM_KEY = "ses_search_sub_term_key";
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
		if($curriculum == '0'){
			// 検索条件のクリアボタン押下時に
			// ・版IDのsessionが残る事
			// ・版プルダウンがdisabledで値が送信されない事により版IDだけで検索される状態を避ける
			$session->remove(self::SES_SEARCH_VERSION_KEY);
		}
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
			$session->remove(self::SES_SEARCH_NEWS_EXAM_KEY);
			$news_exam = $session->get(self::SES_SEARCH_NEWS_EXAM_KEY);
		}

		// 用語
		if($request->query->has('term')){
			$term = $request->query->get('term');
		}else{
			$term = $session->get(self::SES_SEARCH_TERM_KEY);
		}

		// サブ用語
		if($request->query->has('sub_term')){
			$sub_term = $request->query->get('sub_term');
		}else{
			$sub_term = $session->get(self::SES_SEARCH_SUB_TERM_KEY);
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

		// 用語を未編集にする
		$this->closeGenko($user->getUserId());

		$sort_field = '';
		$sort_order = '';
		$sort_order_link = $session->get(self::SES_SORT_ORDER_KEY);
		$sort_field = $session->get(self::SES_SORT_FIELD_KEY);

		if($sort_order_link == 'up'){
			$sort_order = ' ASC';
		}else{
			$sort_order = ' DESC';
		}

		if(($request->query->has('field'))&&(!$request->query->has('page'))){
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
		$entities = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:MainTerm')->getYougoList($curriculum, $version, $hen, $sho, $dai, $chu, $ko, $nombre, $text_freq, $center_freq, $news_exam, $term, $sort_field, $sort_order, null, $sub_term);

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
		$session->set(self::SES_SEARCH_SUB_TERM_KEY, $sub_term);
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
		$dai_list = array();
		if((isset($version))&&(isset($hen))&&(isset($sho))){
			$dai_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
					'versionId' => $version,
					'hen' => $hen,
					'sho' => $sho,
					'headerId' => '3',
					'deleteFlag' => FALSE
			));
		}
		$chu_list = array();
		if((isset($version))&&(isset($hen))&&(isset($sho))&&(isset($dai))){
			$chu_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
					'versionId' => $version,
					'hen' => $hen,
					'sho' => $sho,
					'dai' => $dai,
					'headerId' => '4',
					'deleteFlag' => FALSE
			));
		}
		$ko_list = array();
		if((isset($version))&&(isset($hen))&&(isset($sho))&&(isset($dai))&&(isset($chu))){
			$ko_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
					'versionId' => $version,
					'hen' => $hen,
					'sho' => $sho,
					'dai' => $dai,
					'chu' => $chu,
					'headerId' => '5',
					'deleteFlag' => FALSE
			));
		}

		return array(
				'pagination' => $pagination,
				'cur_page' => $page,
				'sum_page' => floor(count($entities) / $list_count) + 1,
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
				'sub_term' => $sub_term,
				'list_count' => $list_count,
				'sort_order' => $sort_order_link,
				'sort_field' => $sort_field,
				'term_cnt' => count($entities)
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
	 * @Route("/yougo/term/ajax", name="client.yougo.term.ajax")
	 */
	public function getTermAjaxAction(Request $request){
		if($request->request->has('hen')){
			$term_id = $request->request->get('term_id');
			$main_term = $request->request->get('main_term');
			$ver = $request->request->get('version');
			$hen = $request->request->get('hen');

			$param_header = array('hen' => $hen,'deleteFlag' => FALSE);
			if($request->request->has('sho')){
				$param_header = array_merge($param_header, array('sho' => $request->request->get('sho')));
			}
			if($request->request->has('dai')){
				$param_header = array_merge($param_header, array('dai' => $request->request->get('dai')));
			}
			if($request->request->has('chu')){
				$param_header = array_merge($param_header, array('chu' => $request->request->get('chu')));
			}
			$header = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy($param_header);
			$list_header_id = '';
			foreach($header as $header_ele){
				$list_header_id .= $header_ele->getId() . ',';
			}

			if(strlen($list_header_id)>0){$list_header_id = substr($list_header_id, 0, strlen($list_header_id)-1);}
			$term = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:MainTerm')->getYougoListByHeader($ver, $list_header_id);

			$this->get('logger')->error("***term_id***".$term_id);
			$this->get('logger')->error("***term_list***".serialize($term));

			// 見出し変更により改修対象の主用語を掲載順リストに表示できなくなるので、先頭に主用語を付与する
			if($term_id != ''){
				$term_id = ltrim(substr($term_id, 1),'0'); // 用語ID"M00XXXX"先頭の"M00"を削除
				$is_meinterm_exist = false;
				foreach ($term as $term_ele){
					if($term_ele['id'] == $term_id){
						$is_meinterm_exist = true;
						break;
					}
				}
				if(!$is_meinterm_exist){
					$term_add = array();
					array_push($term_add,array('id'=>$term_id,'name'=>$main_term));
					foreach ($term as $term_ele){
						array_push($term_add,$term_ele);
					}
					$term = $term_add;
				}
				$this->get('logger')->error("***term_list(tsuika)***".serialize($term_add));
			}

			$response = new JsonResponse($term);
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

		// センター頻度　Centerテーブルより取得
		$entityVer = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findOneBy(array(
				'id' => $entity['curriculum_id'],
				'deleteFlag' => FALSE
		));

		$sum_center_main = $this->summaryCenterFreqMain($entity['term_id'], $entityVer->getYear());

		$arr_sub = $this->summaryCenterFreqSub($entity['term_id'], '2', $entityVer->getYear(), $entitySub);

		$arr_syn = $this->summaryCenterFreqSub($entity['term_id'], '3', $entityVer->getYear(), $entitySyn);

		return array(
				'yougo' => $entity,
				'subterm' => $entitySub,
				'synonym' => $entitySyn,
				'refer' => $entityRef,
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
				'center_main' => $sum_center_main,
				'center_sub' => $arr_sub,
				'center_syn' => $arr_syn,
				'ranka' => $entityVer->getRankA(),
				'rankb' => $entityVer->getRankB(),
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

		$arr_main_center = array();
		$arr_sub_center = array();
		$arr_syn_center = array();
		foreach($arr_term_id as $term_id){
			// 主用語
			$entity = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetail($term_id);

			if(!$entity){
				return $this->redirect($this->generateUrl('client.yougo.list'));
			}else{
				$entityVer = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findOneBy(array(
						'id' => $entity['curriculum_id'],
						'deleteFlag' => FALSE
				));

				$entity['ranka'] = $entityVer->getRankA();
				$entity['rankb'] = $entityVer->getRankB();

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

			// センター頻度　Centerテーブルより取得
			$sum_center_main = $this->summaryCenterFreqMain($entity['term_id'], $entityVer->getYear());
			array_push($arr_main_center,$sum_center_main);

			$arr_sub = $this->summaryCenterFreqSub($entity['term_id'], '2', $entityVer->getYear(), $entitySub);
			array_push($arr_sub_center,$arr_sub);

			$arr_syn = $this->summaryCenterFreqSub($entity['term_id'], '3', $entityVer->getYear(), $entitySyn);
			array_push($arr_syn_center,$arr_syn);
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
				'center_main' => $arr_main_center,
				'center_sub' => $arr_sub_center,
				'center_syn' => $arr_syn_center,
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

		if(empty($session->get(self::SES_SEARCH_VERSION_KEY))){
			$ver = '0';
		}else{
			$ver = $session->get(self::SES_SEARCH_VERSION_KEY);
		}
		$hen_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->getHenMidashi($ver);

		// 掲載順に表示する用語
		$printOrderList = $em->getRepository('CCKCommonBundle:MainTerm')->getPrintOrderList();

		return array(
				'term_id' => '',
				'yougo' => $entityMain,
				'yougo_sub' => $entitySub,
				'yougo_syn' => $entitySyn,
				'yougo_ref' => $entityRef,
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
				'hen_list' => $hen_list,
				'sho_list' => array(),
				'dai_list' => array(),
				'chu_list' => array(),
				'ko_list' => array(),
				'curriculum' => '',
				'version' => '',
				'select_hen' => '',
				'select_sho' => '',
				'select_dai' => '',
				'select_chu' => '',
				'select_ko' => '',
				'print_order_list' => $printOrderList,
				'main_term_list' => '',
				'center_main' => '',
				'center_sub' => '',
				'center_syn' => '',
				'ranka' => '',
				'rankb' => '',
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

		if($this->savePrintOrder($request,$ret) == false){
			$response = new JsonResponse($ret);
			return $response;
		}

		if($this->saveExplainIndex($request,$ret) == false){
			$response = new JsonResponse($ret);
			return $response;
		}

		if($this->saveExplainCenter($request,$ret) == false){
			$response = new JsonResponse($ret);
			return $response;
		}

		// 解説内用語ID存在チェック
		if($this->checkExplainId($request,$ret) == false){
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
			$entityHeader = $em->getRepository('CCKCommonBundle:Header')->findOneBy(array(
					'hen' => $request->request->get('hen'),
					'sho' => $request->request->get('sho'),
					'dai' => $request->request->get('dai'),
					'chu' => $request->request->get('chu'),
					'ko' => $request->request->get('ko')
			));

			$entity->setHeaderId($entityHeader->getId());

			$entity->setMainTerm($main_term);
			$entity->setRedLetter(($request->request->get('red_letter') == 'true') ? true : false);
			$entity->setKana($request->request->get('kana'));
			$entity->setKanaExistFlag(($request->request->get('kana_exist') == 'true') ? true : false);
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
			$entity->setNombreBold(($request->request->get('nombre_bold') == 'true') ? true : false);
			$entity->setIllustFilename($request->request->get('illust_filename'));
			$entity->setIllustCaption($request->request->get('illust_caption'));
			$entity->setIllustKana($request->request->get('illust_kana'));
			$entity->setHandover($request->request->get('handover'));
			$entity->setUserId($this->getUser()->getUserId());

			if($update_mode == 'new'){
				$entity->setCurriculumId($request->request->get('version'));
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
		$update_mode = $request->request->get('update_mode');

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
				$entity->setTextFrequency($subterm['text_freq'][$idx]);
				$entity->setCenterFrequency($subterm['center_freq'][$idx]);
				$entity->setNewsExam(($subterm['news_exam'][$idx] == 'true') ? true : false);
				$entity->setDelimiter($subterm['delimiter'][$idx]);
				$entity->setIndexAddLetter($subterm['index_add_letter'][$idx]);
				$entity->setIndexKana($subterm['index_kana'][$idx]);

				$entity->setMainTermId($request->request->get('term_id'));
				$entity->setNombre($subterm['nombre'][$idx]);

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
		$update_mode = $request->request->get('update_mode');

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
				$entity->setTextFrequency($synterm['text_freq'][$idx]);
				$entity->setCenterFrequency($synterm['center_freq'][$idx]);
				$entity->setNewsExam(($synterm['news_exam'][$idx] == 'true') ? true : false);
				$entity->setDelimiter($synterm['delimiter'][$idx]);
				$entity->setIndexAddLetter($synterm['index_add_letter'][$idx]);
				$entity->setIndexKana($synterm['index_kana'][$idx]);

				$entity->setMainTermId($request->request->get('term_id'));
				$entity->setNombre($synterm['nombre'][$idx]);

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
		$update_mode = $request->request->get('update_mode');

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
			if(!$entity){
				$update_mode = 'new';
				$entity = new Refer();
			}

			$em->getConnection()->beginTransaction();

			try{
				$entity->setReferTermId($refterm['ref_term_id'][$idx]);

				if($update_mode == 'new'){
					$entity->setMainTermId($request->request->get('term_id'));
					$entity->setNombre($refterm['nombre'][$idx]);
					$em->persist($entity);
				}
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

	private function savePrintOrder($request,&$ret){
		$return_flag = true;

		$print_order_list = $request->request->get('print_order_list');
		$this->get('logger')->error('***print_order_list***');
		$this->get('logger')->error(serialize($print_order_list));

		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		$idx = 1;
		foreach ($print_order_list as $print_order){
			$entity = $em->getRepository('CCKCommonBundle:MainTerm')->findOneBy(array(
					'termId' => $print_order,
					'deleteFlag' => FALSE
			));

			try{
				$entity->setPrintOrder($idx);
				$idx++;

				$em->flush();
			} catch (\Exception $e){
				$em->getConnection()->rollback();
				$em->close();

				// log
				$this->get('logger')->error($e->getMessage());
				$this->get('logger')->error($e->getTraceAsString());

				$ret = ['result'=>'ng','error'=>'MainTermDB print_order error termId:'.$print_order];
				$return_flag = false;
				return $return_flag;
			}
		}
		$em->getConnection()->commit();

		return $return_flag;
	}

	private function saveExplainIndex($request,&$ret){
		$return_flag = true;
		// 解説内索引用語を一時テーブルよりコピー
		$em = $this->get('doctrine.orm.entity_manager');
		$entityTmp = $em->getRepository('CCKCommonBundle:ExplainIndexTmp')->findBy(array(
				'mainTermId' => $request->request->get('term_id')
		));

		foreach($entityTmp as $entity_rec){
			$entity = $em->getRepository('CCKCommonBundle:ExplainIndex')->findOneBy(array(
					'mainTermId' => $entity_rec->getMainTermId(),
					'indexTerm' => $entity_rec->getIndexTerm()
			));
			$update_mode = 'update';
			if(!$entity){
				$entity = new ExplainIndex();
				$update_mode = 'new';
			}

			$em->getConnection()->beginTransaction();

			try{
				$entity->setMainTermId($entity_rec->getMainTermId());
				$entity->setIndexTerm($entity_rec->getIndexTerm());
				$entity->setIndexAddLetter($entity_rec->getIndexAddLetter());
				$entity->setIndexKana($entity_rec->getIndexKana());
				$entity->setNombre($entity_rec->getNombre());
				$entity->setDeleteDate($entity_rec->getDeleteDate());
				$entity->setDeleteFlag($entity_rec->getDeleteFlag());
				$entity->setTextFrequency($entity_rec->getTextFrequency());
				$entity->setCenterFrequency($entity_rec->getCenterFrequency());
				$entity->setNewsExam($entity_rec->getNewsExam());

				if($update_mode == 'new'){
					$em->persist($entity);
				}
				$em->flush();
				$em->getConnection()->commit();
			} catch (\Exception $e){
				$em->getConnection()->rollback();
				$em->close();

				// log
				$this->get('logger')->error($e->getMessage());
				$this->get('logger')->error($e->getTraceAsString());

				$ret = ['result'=>'ng','error'=>'ExplainDB error id:'.$entity_rec->getIndexTerm()];
				$return_flag = false;
				return $return_flag;
			}
		}

		// 解説内さくいん用語サブ画面で登録ボタン押下
		if($request->request->get('update_mode_exp') == 'update'){
			// WEBに入力されなかった用語は削除
			$entity = $em->getRepository('CCKCommonBundle:ExplainIndex')->findBy(array(
					'mainTermId' => $request->request->get('term_id'),
					'deleteFlag' => FALSE
			));

			foreach($entity as $entity_rec){
				$index_term = $entity_rec->getIndexTerm();
				$this->get('logger')->error("***DB:yougo***".$index_term);

				$entity_tmp = $em->getRepository('CCKCommonBundle:ExplainIndexTmp')->findOneBy(array(
						'mainTermId' => $request->request->get('term_id'),
						'indexTerm' => $index_term,
						'deleteFlag' => FALSE
				));

				if(!$entity_tmp){
					$em->getConnection()->beginTransaction();

					try{
						$entity_rec->setDeleteFlag(true);
						$entity_rec->setModifyDate(new \DateTime());
						$entity_rec->setDeleteDate(new \DateTime());

						$rtncd = $em->getRepository('CCKCommonBundle:Center')->deleteDataByMainId($request->request->get('term_id'),$entity_rec->getId(),'4');

						$em->flush();
						$em->getConnection()->commit();
					} catch (\Exception $e){
						$em->getConnection()->rollback();
						$em->close();

						// log
						$this->get('logger')->error($e->getMessage());
						$this->get('logger')->error($e->getTraceAsString());

						$ret = ['result'=>'ng','error'=>'ExplainDB error id:'.$index_term];
						$response = new JsonResponse($ret);
						return $response;
					}
				}

			}
		}

		return $return_flag;
	}

	private function saveExplainCenter($request,&$ret){
		$return_flag = true;
		// センター頻度を一時テーブルよりコピー
		$em = $this->get('doctrine.orm.entity_manager');
		$entityTmp = $em->getRepository('CCKCommonBundle:CenterTmp')->findBy(array(
				'mainTermId' => $request->request->get('term_id')
		));

		foreach($entityTmp as $entity_rec){
			$entityExp = $em->getRepository('CCKCommonBundle:ExplainIndex')->findOneBy(array(
					'mainTermId' => $request->request->get('term_id'),
					'indexTerm' => $entity_rec->getIndexTerm(),
					'deleteFlag' => FALSE
			));

			$entity = $em->getRepository('CCKCommonBundle:Center')->findOneBy(array(
					'mainTermId' => $entity_rec->getMainTermId(),
					'subTermId' => $entityExp->getId(),
					'yougoFlag' => '4',
					'year' => $entity_rec->getYear()
			));

			$update_mode = 'update';
			if(!$entity){
				$entity = new Center();
				$update_mode = 'new';
			}

			$em->getConnection()->beginTransaction();

			try{
				$entity->setMainTermId($entity_rec->getMainTermId());
				$entity->setSubTermId($entityExp->getId());
				$entity->setYougoFlag('4');
				$entity->setYear($entity_rec->getYear());
				$entity->setMainExam($entity_rec->getMainExam());
				$entity->setSubExam($entity_rec->getSubExam());
				$entity->setDeleteDate($entity_rec->getDeleteDate());
				$entity->setDeleteFlag($entity_rec->getDeleteFlag());

				if($update_mode == 'new'){
					$em->persist($entity);
				}
				$em->flush();
				$em->getConnection()->commit();
			} catch (\Exception $e){
				$em->getConnection()->rollback();
				$em->close();

				// log
				$this->get('logger')->error($e->getMessage());
				$this->get('logger')->error($e->getTraceAsString());

				$ret = ['result'=>'ng','error'=>'CenterDB error id:'.$entity_rec->getIndexTerm()];
				$return_flag = false;
				return $return_flag;
			}
		}

		return $return_flag;
	}

	private function checkExplainId($request,&$ret){
		$return_flag = true;

		$em = $this->get('doctrine.orm.entity_manager');
		$entity = $em->getRepository('CCKCommonBundle:ExplainIndex')->findBy(array(
				'mainTermId' => $request->request->get('term_id'),
				'deleteFlag' => FALSE
		));

		$arr_exp_indexTerm = [];
		foreach($entity as $entity_rec){
			array_push($arr_exp_indexTerm, $entity_rec->getIndexTerm());
		}

		$arr_not_exist_index_term = [];
		if(preg_match_all('/《c_SAK》(.*?)《\/c_SAK》/u', $request->request->get('term_explain'), $match_data, PREG_SET_ORDER)){
			foreach($match_data as $main_explain_ele){
				if(!in_array($main_explain_ele[1], $arr_exp_indexTerm)){
					array_push($arr_not_exist_index_term,$main_explain_ele[1]);
					$return_flag = false;
				}
			}
		}

		if(!$return_flag){
			$ret = ['result'=>'explain_warn','error'=>$arr_not_exist_index_term];
		}

		return $return_flag;
	}

	private function saveCenterFrequency($term_id,$main_term_id,$sub,$ver_id){
		$return_flag = true;

		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		if($sub == '1'){
			$main_term_id = ltrim($main_term_id,'0'); // 用語ID"00XXXX"先頭の"00"を削除
		}else{
			$main_term_id = ltrim(substr($main_term_id, 1),'0'); // 用語ID"M00XXXX"先頭の"M00"を削除
		}

		//センター頻度開始年の取得
		$entityVersion = $em->getRepository('CCKCommonBundle:Version')->findOneBy(array(
				'id' => $ver_id,
				'deleteFlag' => FALSE
		));

		if(!$entityVersion){
			$return_flag = false;
			return $return_flag;
		}

		$year = $entityVersion->getYear();

		// センター頻度開始年より古いデータを削除
		$rtncd = $em->getRepository('CCKCommonBundle:Center')->deleteOldData($main_term_id,$term_id,$sub,$year);

		$this->addCenterYearData($em, $main_term_id, $term_id, $sub, $year);

		// センター頻度開始年+10年後より新しいデータを削除
		$rtncd = $em->getRepository('CCKCommonBundle:Center')->deleteOverData($main_term_id,$term_id,$sub,$year+9);

		$em->getConnection()->commit();

		return $return_flag;
	}

	/**
	 * @Route("/yougo/center/update/ajax", name="client.yougo.center.update.ajax")
	 */
	public function getCenterUpdateAjaxAction(Request $request){
		if($request->request->has('term_id')){
			$term_id = $request->request->get('term_id');
			$center_tbl = $request->request->get('center_tbl');
			$yougo_flag = $request->request->get('yougo_flag');

			$em = $this->get('doctrine.orm.entity_manager');
			$em->getConnection()->beginTransaction();

			foreach ($center_tbl as $center_key=>$center_rec){

				$this->get('logger')->error('***center_update***');
				$this->get('logger')->error($center_key);
				$this->get('logger')->error($center_rec['main']);
				$this->get('logger')->error($center_rec['sub']);

				try{
					if($yougo_flag == '1'){
						$entity = $em->getRepository('CCKCommonBundle:Center')->findOneBy(array(
								'mainTermId' => $term_id,
								'yougoFlag' => $yougo_flag,
								'year' => $center_key,
								'deleteFlag' => FALSE
						));
					}elseif($yougo_flag == '4'){
						$entity = $em->getRepository('CCKCommonBundle:CenterTmp')->findOneBy(array(
								'indexTerm' => $term_id,
								'yougoFlag' => $yougo_flag,
								'year' => $center_key,
								'deleteFlag' => FALSE
						));
					}else{
						$entity = $em->getRepository('CCKCommonBundle:Center')->findOneBy(array(
								'subTermId' => $term_id,
								'yougoFlag' => $yougo_flag,
								'year' => $center_key,
								'deleteFlag' => FALSE
						));
					}

					if($entity){
						$entity->setMainExam($center_rec['main']);
						$entity->setSubExam($center_rec['sub']);
					}

					$em->flush();
				} catch (\Exception $e){
					$em->getConnection()->rollback();
					$em->close();

					// log
					$this->get('logger')->error($e->getMessage());
					$this->get('logger')->error($e->getTraceAsString());

					$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
					return $response;
				}
			}
			$em->getConnection()->commit();

			$response = new JsonResponse(JsonResponse::HTTP_OK);
		}else{
			$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
		}

		return $response;
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

		// 更新対象用語を編集中にする
		if($this->openGenko($entityMain->getTermId(), $user->getUserId()) == false){
			return $this->redirect($this->generateUrl('client.yougo.list'));
		}

		$entitySub = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSubterm($id);
		$entitySyn = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSynonym($id);
		$entityRef = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfRefer($id);

		$cur_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findBy(array(
				'deleteFlag' => FALSE
		));
		$ver_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findBy(array(
				'deleteFlag' => FALSE
		));

		// 選択する見出し
		$entityHeader = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findOneBy(array(
				'id' => $entityMain->getHeaderId(),
				'deleteFlag' => FALSE
		));

		$hen_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->getHenMidashi($entityMain->getCurriculumId());

		$sho_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->getShoMidashi($entityMain->getCurriculumId(), $entityHeader->getHen());

		$dai_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->getDaiMidashi($entityMain->getCurriculumId(), $entityHeader->getHen(), $entityHeader->getSho());

		$chu_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->getChuMidashi($entityMain->getCurriculumId(), $entityHeader->getHen(), $entityHeader->getSho(), $entityHeader->getDai());

		$ko_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->getKoMidashi($entityMain->getCurriculumId(), $entityHeader->getHen(), $entityHeader->getSho(), $entityHeader->getDai(), $entityHeader->getChu());

		// 選択する教科
		$entityVersion = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findOneBy(array(
				'id' => $entityMain->getCurriculumId(),
				'deleteFlag' => FALSE
		));

		// 掲載順に表示する用語
		$printOrderList = $em->getRepository('CCKCommonBundle:MainTerm')->getPrintOrderList($entityMain->getCurriculumId(), $entityMain->getHeaderId());

		// 指矢印選択に表示する用語
		$mainTermList = $em->getRepository('CCKCommonBundle:MainTerm')->findBy(array(
				'curriculumId' => $entityMain->getCurriculumId(),
				'headerId' => $entityMain->getHeaderId(),
				'deleteFlag' => false
		));

		// センター頻度　Centerテーブルより取得
		$sum_center_main = $this->summaryCenterFreqMain($entityMain->getTermId(), $entityVersion->getYear());

		$arr_sub = $this->summaryCenterFreqSub($entityMain->getTermId(), '2', $entityVersion->getYear(), $entitySub);

		$arr_syn = $this->summaryCenterFreqSub($entityMain->getTermId(), '3', $entityVersion->getYear(), $entitySyn);

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
				'center_main' => $sum_center_main,
				'center_sub' => $arr_sub,
				'center_syn' => $arr_syn,
				'ranka' => $entityVersion->getRankA(),
				'rankb' => $entityVersion->getRankB(),
		);
	}

	private function summaryCenterFreqMain($main_term_id, $start_year){
		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		$rtncd = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Center')->deleteOldData($main_term_id,'','1',$start_year);

		$this->addCenterYearData($em, $main_term_id, '', '1', $start_year);

		$rtncd = $em->getRepository('CCKCommonBundle:Center')->deleteOverData($main_term_id,'','1',$start_year+9);

		$em->getConnection()->commit();

		$center_point = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Center')->getCenterPoints($main_term_id,'1');
		$sum_center_main = 0;
		foreach ($center_point as $rec_center_point){
			$sum_center_main += $rec_center_point['mainExam'] + $rec_center_point['subExam'];
		}
		return $sum_center_main;
	}

	private function summaryCenterFreqSub($main_term_id, $yougo_flag, $start_year,$entitySub){
		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		$arr_sub = array();
		foreach ($entitySub as $rec_sub){
			$sum_center = 0;

			$rtncd = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Center')->deleteOldData($main_term_id,$rec_sub['id'],$yougo_flag,$start_year);

			$this->addCenterYearData($em, $main_term_id, $rec_sub['id'], $yougo_flag, $start_year);

			$rtncd = $em->getRepository('CCKCommonBundle:Center')->deleteOverData($main_term_id,$rec_sub['id'], $yougo_flag,$start_year+9);

			$center_point = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Center')->getCenterPoints($rec_sub['id'],$yougo_flag);
			foreach ($center_point as $rec_center_point){
				$sum_center += $rec_center_point['mainExam'] + $rec_center_point['subExam'];
			}
			$arr_sub += array_merge($arr_sub,array($rec_sub['id'] => $sum_center));
		}

		$em->getConnection()->commit();

		return $arr_sub;
	}

	private function addCenterYearData($em,$main_term_id,$sub_term_id,$sub,$year){

		for ($idx=0;$idx<10;$idx++){

			try{
				// 存在チェック
				if($sub == '1'){
					$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findOneBy(array(
							'mainTermId' => $main_term_id,
							'yougoFlag' =>  $sub,
							'year' => $year+$idx,
							'deleteFlag' => FALSE
					));
				}else{
					$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findOneBy(array(
							'mainTermId' => $main_term_id,
							'subTermId' => $sub_term_id,
							'yougoFlag' =>  $sub,
							'year' => $year+$idx,
							'deleteFlag' => FALSE
					));
				}

				if(!$entityCenter){
					$entity = new Center();

					$entity->setMainTermId($main_term_id);

					if($sub == '1'){
						$entity->setSubTermId(0);
					}else{
						$entity->setSubTermId($sub_term_id);
					}

					$entity->setYougoFlag($sub);
					$entity->setYear($year+$idx);
					$entity->setMainExam(0);
					$entity->setSubExam(0);

					$em->persist($entity);
					$em->flush();
				}

			} catch (\Exception $e){
				$em->getConnection()->rollback();
				$em->close();

				// log
				$this->get('logger')->error($e->getMessage());
				$this->get('logger')->error($e->getTraceAsString());

				$return_flag = false;
				return $return_flag;
			}
		}
	}




	/**
	 * @Route("/yougo/subterm/new/ajax", name="client.yougo.subterm.new.ajax")
	 */
	public function getSubtermNewAjaxAction(Request $request){
		$em = $this->getDoctrine()->getManager();

		$entitySub = new SubTerm();

		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		try{
			$entitySub->setMainTermId(0);
			$entitySub->setSubTerm("");
			$entitySub->setRedLetter(0);
			$entitySub->setKanaExistFlag(0);
			$entitySub->setTextFrequency(0);
			$entitySub->setCenterFrequency(0);
			$entitySub->setNewsExam(0);
			$entitySub->setDelimiter("0");
			$entitySub->setKana("");
			$entitySub->setDelimiterKana("0");
			$entitySub->setIndexAddLetter("");
			$entitySub->setIndexKana("");
			$entitySub->setNombre(0);

			$em->persist($entitySub);
			$em->flush();
			$em->getConnection()->commit();
		} catch (\Exception $e){
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
		}
		$entitySub = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSubterm($entitySub->getId(),true);
		$response = new JsonResponse(json_encode($entitySub[0]));

		return $response;
	}

	/**
	 * @Route("/yougo/synonym/new/ajax", name="client.yougo.synonym.new.ajax")
	 */
	public function getSynonymNewAjaxAction(Request $request){
		$em = $this->getDoctrine()->getManager();

		$entitySub = new Synonym();

		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		try{
			$entitySub->setMainTermId(0);
			$entitySub->setTerm("");
			$entitySub->setRedLetter(0);
			$entitySub->setSynonymId(0);
			$entitySub->setTextFrequency(0);
			$entitySub->setCenterFrequency(0);
			$entitySub->setNewsExam(0);
			$entitySub->setDelimiter("0");
			$entitySub->setKana("");
			$entitySub->setIndexAddLetter("");
			$entitySub->setIndexKana("");
			$entitySub->setNombre(0);

			$em->persist($entitySub);
			$em->flush();
			$em->getConnection()->commit();
		} catch (\Exception $e){
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
		}
		$entitySub = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSynonym($entitySub->getId(),true);
		$response = new JsonResponse(json_encode($entitySub[0]));

		return $response;
	}

	/**
	 * @Route("/yougo/center/ajax", name="client.yougo.center.ajax")
	 */
	public function getCenterAjaxAction(Request $request){
		if($request->request->has('term_id')){
			$term_id = $request->request->get('term_id');
			$main_term_id = $request->request->get('main_term_id');
			$yougo_flag = $request->request->get('yougo_flag');
			$ver_id = $request->request->get('ver_id');
			//$center_point = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Center')->getCenterPoints($term_id,$yougo_flag);

			//if(count($center_point)==0){
				if($this->saveCenterFrequency($term_id,$main_term_id,$yougo_flag,$ver_id) == false){
					$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
					return $response;
				}
				$center_point = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Center')->getCenterPoints($term_id,$yougo_flag);
			//}

			$response = new JsonResponse($center_point);
		}else{
			$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
		}

		return $response;
	}

	/**
	 * @Route("/yougo/center/tmp/ajax", name="client.yougo.center.tmp.ajax")
	 */
	public function getCenterTmpAjaxAction(Request $request){
		if($request->request->has('index_term')){
			$index_term = $request->request->get('index_term');
			$main_term_id = $request->request->get('main_term_id');
			$main_term_id = (int)ltrim(substr($main_term_id, 1),'0');

			$yougo_flag = $request->request->get('yougo_flag');
			$ver_id = $request->request->get('ver_id');

			$em = $this->get('doctrine.orm.entity_manager');
			$entity = $em->getRepository('CCKCommonBundle:ExplainIndex')->findOneBy(array(
					'mainTermId' => $main_term_id,
					'indexTerm' => $index_term,
					'deleteFlag' => FALSE
			));

			//センター頻度開始年の取得
			$entityVersion = $em->getRepository('CCKCommonBundle:Version')->findOneBy(array(
					'id' => $ver_id,
					'deleteFlag' => FALSE
			));

			if(!$entityVersion){
				$return_flag = false;
				return $return_flag;
			}

			$year = $entityVersion->getYear();

			$em->getConnection()->beginTransaction();

			try{
				$entityCenter = null;
				if($entity){
					$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
							'mainTermId' => $main_term_id,
							'subTermId' => $entity->getId(),
							'yougoFlag' => $yougo_flag,
							'deleteFlag' => FALSE
					));
				}

				$entityCenterTmp = $em->getRepository('CCKCommonBundle:CenterTmp')->findBy(array(
						'mainTermId' => $main_term_id,
						'indexTerm' => $index_term,
						'yougoFlag' => $yougo_flag,
						'deleteFlag' => FALSE
				));

				$tmp_exist = true;
				if(!$entityCenterTmp){
					$tmp_exist = false;
				}

				if($entityCenter){
					// 解説内索引用語が登録済のセンター頻度取得
					foreach($entityCenter as $entityCenterRec){
						if(!$tmp_exist){
							$entityCenterTmp = new CenterTmp();
							$entityCenterTmp->setMainTermId($entityCenterRec->getMainTermId());
							$entityCenterTmp->setIndexTerm($index_term);
							$entityCenterTmp->setYougoFlag($entityCenterRec->getYougoFlag());
							$entityCenterTmp->setYear($entityCenterRec->getYear());
							$entityCenterTmp->setMainExam($entityCenterRec->getMainExam());
							$entityCenterTmp->setSubExam($entityCenterRec->getSubExam());

							$em->persist($entityCenterTmp);
						}
					}

				}else{
					// 解説内索引用語が未登録の場合、初期値設定
					for ($idx=0;$idx<10;$idx++){
						if(!$tmp_exist){
							$entityCenterTmp = new CenterTmp();
							$entityCenterTmp->setMainTermId($main_term_id);
							$entityCenterTmp->setIndexTerm($index_term);
							$entityCenterTmp->setYougoFlag($yougo_flag);
							$entityCenterTmp->setYear($year+$idx);
							$entityCenterTmp->setMainExam(0);
							$entityCenterTmp->setSubExam(0);

							$em->persist($entityCenterTmp);

						}
					}
				}

				$em->flush();
				$em->getConnection()->commit();
			} catch (\Exception $e){
				$em->getConnection()->rollback();
				$em->close();

				// log
				$this->get('logger')->error($e->getMessage());
				$this->get('logger')->error($e->getTraceAsString());

				$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
			}

			$center_point = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:CenterTmp')->getCenterPoints($index_term,$yougo_flag);

			$response = new JsonResponse($center_point);
		}else{
			$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
		}

		return $response;
	}

	/**
	 * @Route("/yougo/explain/ajax", name="client.yougo.explain.ajax")
	 */
	public function getExplainAjaxAction(Request $request){
		if($request->request->has('term_id')){
			$term_id = $request->request->get('term_id');
			$explain = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:ExplainIndexTmp')->getExplainTerms($term_id);
			if(!$explain){
				$explain = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:ExplainIndex')->getExplainTerms($term_id);
			}

			$response = new JsonResponse($explain);
		}else{
			$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
		}

		return $response;
	}

	/**
	 * @Route("/explain/save/ajax", name="client.explain.save.ajax")
	 * @Method("POST")
	 */
	public function saveExplainAjaxAction(Request $request){
		$this->get('logger')->error("***saveExplainAjaxAction start***");
		$this->get('logger')->error(serialize($request->request->get('term_id')));
		//$this->get('logger')->error(serialize($request->request->get('index_term')));
		$this->get('logger')->error(serialize($request->request->get('index_add_letter')));

		$ret = ['result'=>'ok','error'=>''];

		if(!($request->request->has('index_term'))){
			$ret = ['result'=>'ng','error'=>'parameter error'];
			$response = new JsonResponse($ret);
			return $response;
		}

		$return_flag = true;

		$idx = 0;
		$em = $this->get('doctrine.orm.entity_manager');
		foreach($request->request->get('index_term') as $ele_expterm){
			//$this->get('logger')->error("***exp_term_elem***".serialize($ele_expterm));

			$entity = $em->getRepository('CCKCommonBundle:ExplainIndexTmp')->findOneBy(array(
					'mainTermId' => $request->request->get('term_id')[$idx],
					'indexTerm' => $ele_expterm,
					'deleteFlag' => FALSE
			));
			$update_mode = 'update';
			if(!$entity){
				$entity = new ExplainIndexTmp();
				$update_mode = 'new';
			}

			$em->getConnection()->beginTransaction();

			try{
				$entity->setMainTermId($request->request->get('term_id')[$idx]);
				$entity->setIndexTerm($ele_expterm);
				$entity->setIndexAddLetter($request->request->get('index_add_letter')[$idx]);
				$entity->setIndexKana($request->request->get('index_kana')[$idx]);

				$entity->setTextFrequency($request->request->get('text_freq')[$idx]);
				$entity->setCenterFrequency($request->request->get('center_freq')[$idx]);
				$entity->setNewsExam(($request->request->get('news_exam')[$idx] == 'true') ? true : false);

				$entity->setNombre($request->request->get('nombre')[$idx]);

				if($update_mode == 'new'){
					$em->persist($entity);
				}
				$em->flush();
				$em->getConnection()->commit();
			} catch (\Exception $e){
				$em->getConnection()->rollback();
				$em->close();

				// log
				$this->get('logger')->error($e->getMessage());
				$this->get('logger')->error($e->getTraceAsString());

				$ret = ['result'=>'ng','error'=>'ExplainDB error id:'.$ele_expterm];
				$response = new JsonResponse($ret);
				return $response;
			}
			$idx++;
		}

		// WEBに入力されなかった用語は削除
		$entity = $em->getRepository('CCKCommonBundle:ExplainIndexTmp')->findBy(array(
				'mainTermId' => $request->request->get('term_id')[0],
				'deleteFlag' => FALSE
		));

		foreach($entity as $entity_rec){
			$index_term = $entity_rec->getIndexTerm();
			$this->get('logger')->error("***DB:yougo***".$index_term);

			if(!in_array($index_term, $request->request->get('index_term'))){
				$entity = $em->getRepository('CCKCommonBundle:ExplainIndexTmp')->findOneBy(array(
						'mainTermId' => $request->request->get('term_id')[0],
						'indexTerm' => $index_term,
						'deleteFlag' => FALSE
				));

				$em->getConnection()->beginTransaction();

				try{
					$entity->setDeleteFlag(true);
					$entity->setModifyDate(new \DateTime());
					$entity->setDeleteDate(new \DateTime());

					$em->flush();
					$em->getConnection()->commit();
				} catch (\Exception $e){
					$em->getConnection()->rollback();
					$em->close();

					// log
					$this->get('logger')->error($e->getMessage());
					$this->get('logger')->error($e->getTraceAsString());

					$ret = ['result'=>'ng','error'=>'ExplainTmpDB error id:'.$index_term];
					$response = new JsonResponse($ret);
					return $response;
				}

				$entity = $em->getRepository('CCKCommonBundle:CenterTmp')->findBy(array(
						'mainTermId' => $request->request->get('term_id')[0],
						'indexTerm' => $index_term,
						'deleteFlag' => FALSE
				));

				$em->getConnection()->beginTransaction();

				try{
					foreach($entity as $entity_rec){
						$entity_rec->setDeleteFlag(true);
						$entity_rec->setModifyDate(new \DateTime());
						$entity_rec->setDeleteDate(new \DateTime());
					}

					$em->flush();
					$em->getConnection()->commit();
				} catch (\Exception $e){
					$em->getConnection()->rollback();
					$em->close();

					// log
					$this->get('logger')->error($e->getMessage());
					$this->get('logger')->error($e->getTraceAsString());

					$ret = ['result'=>'ng','error'=>'CenterTmpDB error id:'.$index_term];
					$response = new JsonResponse($ret);
					return $response;
				}
			}

		}

		// 主用語DBの用語解説の更新
		/*$entity = $em->getRepository('CCKCommonBundle:MainTerm')->findOneBy(array(
				'termId' => $request->request->get('term_id')[0],
				'deleteFlag' => FALSE
		));

		$em->getConnection()->beginTransaction();

		try{
			$entity->setTermExplain($request->request->get('mainterm_explain'));

			$em->flush();
			$em->getConnection()->commit();
		} catch (\Exception $e){
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			$ret = ['result'=>'ng','error'=>'MainTermDB error id:'.$entity->getMainTerm()];
			$response = new JsonResponse($ret);
			return $response;
		}*/

		$response = new JsonResponse($ret);
		return $response;
	}

	/**
	 * @Route("/explain/delete/ajax", name="client.explain.delete.ajax")
	 * @Method("POST")
	 */
	public function deleteExplainAjaxAction(Request $request){
		$this->get('logger')->error("***deleteExplainAjaxAction start***");
		$this->get('logger')->error(serialize($request->request->get('term_id')));

		$ret = ['result'=>'ok','error'=>''];

		if(!($request->request->has('term_id'))){
			$ret = ['result'=>'ng','error'=>'parameter error'];
			$response = new JsonResponse($ret);
			return $response;
		}

		$term_id = $request->request->get('term_id');

		// 一時テーブルに主用語IDに紐づく解説内用語が無い場合は即終了
		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:ExplainIndexTmp')->findBy(array(
				'mainTermId' =>$term_id,
				'deleteFlag' => FALSE
		));

		$entityCenter = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:CenterTmp')->findBy(array(
				'mainTermId' =>$term_id,
				'deleteFlag' => FALSE
		));

		if((!$entity)&&(!$entityCenter)){
			$response = new JsonResponse($ret);
			return $response;
		}

		$em = $this->get('doctrine.orm.entity_manager');
		// 解説内索引用語サブ画面で一時テーブルに保存された後、用語編集画面から戻った場合は削除
		$conn = $em->getConnection();
		$conn->beginTransaction();
		try {
			if($entity){
				$conn->delete('ExplainIndexTmp', array('main_term_id' => $term_id));
			}
			if($entityCenter){
				$conn->delete('CenterTmp', array('main_term_id' => $term_id));
			}
			$em->getConnection()->commit();
		}catch (\Exception $e){
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			$ret = ['result'=>'ng','error'=>'ExplainIndexTmpDB error id:'.$term_id];
			$response = new JsonResponse($ret);
			return $response;
		}
	}

	/**
	 * @Route("/center/delete/ajax", name="client.center.delete.ajax")
	 * @Method("POST")
	 */
	public function deleteCenterAjaxAction(Request $request){
		$this->get('logger')->error("***deleteCenterAjaxAction start***");
		$this->get('logger')->error(serialize($request->request->get('term_id')));
		$this->get('logger')->error(serialize($request->request->get('explain_id')));
		$this->get('logger')->error(serialize($request->request->get('index_term')));

		$ret = ['result'=>'ok','error'=>''];

		if(!($request->request->has('term_id'))){
			$ret = ['result'=>'ng','error'=>'parameter error'];
			$response = new JsonResponse($ret);
			return $response;
		}

		$term_id = $request->request->get('term_id');

		// 一時テーブルに主用語IDに紐づくセンター頻度が無い場合は即終了
		$entityCenter = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:CenterTmp')->findBy(array(
				'mainTermId' =>$term_id,
				'deleteFlag' => FALSE
		));

		if(!$entityCenter){
			$response = new JsonResponse($ret);
			return $response;
		}

		$idx = 0;
		$em = $this->get('doctrine.orm.entity_manager');
		$conn = $em->getConnection();

		foreach($request->request->get('index_term') as $ele_expterm){
			//$this->get('logger')->error("***exp_term_elem***".serialize($ele_expterm));

			$entityExpTmp = $em->getRepository('CCKCommonBundle:ExplainIndexTmp')->findOneBy(array(
					'mainTermId' => $term_id,
					'indexTerm' => $ele_expterm,
					'deleteFlag' => FALSE
			));

			if(!$entityExpTmp){
				continue;
			}

			$entityExp = $em->getRepository('CCKCommonBundle:ExplainIndex')->findOneBy(array(
					'mainTermId' => $term_id,
					'indexTerm' => $entityExpTmp->getIndexTerm(),
					'deleteFlag' => FALSE
			));

			$em->getConnection()->beginTransaction();

			try{
				if($entityExp){
					$entityExpTmp->setCenterFrequency($entityExp->getCenterFrequency());
				}else{
					$entityExpTmp->setCenterFrequency(0);
				}

				$em->flush();
				$em->getConnection()->commit();
			} catch (\Exception $e){
				$em->getConnection()->rollback();
				$em->close();

				// log
				$this->get('logger')->error($e->getMessage());
				$this->get('logger')->error($e->getTraceAsString());

				$ret = ['result'=>'ng','error'=>'ExplainIndexTmp error id:'.$ele_expterm];
				$response = new JsonResponse($ret);
				return $response;
			}
			$idx++;
		}

		// センター頻度サブ画面で一時テーブルに保存された後、解説内用語サブ画面から戻った場合は削除
		try {
			if($entityCenter){
				$conn->delete('CenterTmp', array('main_term_id' => $term_id));
			}
			$em->getConnection()->commit();
		}catch (\Exception $e){
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			$ret = ['result'=>'ng','error'=>'CenterTmpDB error id:'.$term_id];
			$response = new JsonResponse($ret);
			return $response;
		}
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
	 * @Route("/delete/{id}", name="client.yougo.delete")
	 * @Method("POST|GET")
	 * @Template("CCKClientBundle:yougo:index.html.twig")
	 */
	public function deleteAction(Request $request, $id){
		// get user information
		$user = $this->getUser();

		$id = (int) $id;

		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:MainTerm')->findOneBy(array(
				'id' =>$id,
				'deleteFlag' => FALSE
		));
		if(!$entity){
			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
		$entity->setDeleteFlag(true);
		$entity->setModifyDate(new \DateTime());
		$entity->setDeleteDate(new \DateTime());

		// 紐付くsubtermの検索
		$entity_sub = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:SubTerm')->findBy(array(
				'mainTermId' =>$entity->getTermId(),
				'deleteFlag' => FALSE
		));

		foreach($entity_sub as $entity_sub_rec){
			$entity_sub_rec->setDeleteFlag(true);
			$entity_sub_rec->setModifyDate(new \DateTime());
			$entity_sub_rec->setDeleteDate(new \DateTime());
		}

		// 紐付くsynonymの検索
		$entity_syn = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Synonym')->findBy(array(
				'mainTermId' =>$entity->getTermId(),
				'deleteFlag' => FALSE
		));

		foreach($entity_syn as $entity_syn_rec){
			$entity_syn_rec->setDeleteFlag(true);
			$entity_syn_rec->setModifyDate(new \DateTime());
			$entity_syn_rec->setDeleteDate(new \DateTime());
		}

		// 紐付くreferの検索
		$entity_ref = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Refer')->findBy(array(
				'mainTermId' =>$entity->getTermId(),
				'deleteFlag' => FALSE
		));

		foreach($entity_ref as $entity_ref_rec){
			$entity_ref_rec->setDeleteFlag(true);
			$entity_ref_rec->setModifyDate(new \DateTime());
			$entity_ref_rec->setDeleteDate(new \DateTime());
		}

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
		}

		$page = 1;
		if($request->query->has('page')){
			$page = $request->query->get('page');
		}

		return $this->redirect($this->generateUrl('client.yougo.index', array('page' => $page)));
	}

	/**
	 * @Route("/yougo/sub/delete/ajax", name="client.yougo.sub.delete.ajax")
	 */
	public function getSubDeleteAjaxAction(Request $request){
		if($request->request->has('id')){
			$id = $request->request->get('id');
			$table = $request->request->get('table');
			$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:'.$table)->findOneBy(array(
					'id' =>$id,
					'deleteFlag' => FALSE
			));

			$entity->setDeleteFlag(true);
			$entity->setModifyDate(new \DateTime());
			$entity->setDeleteDate(new \DateTime());

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
			}

			$response = new JsonResponse(JsonResponse::HTTP_OK);
		}else{
			$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
		}

		return $response;
	}

	/**
	 * @Route("/copy/{term_id}", name="client.yougo.copy")
	 * @Method("POST|GET")
	 */
	public function copyAction(Request $request, $term_id){
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

		$entityExp = $em->getRepository('CCKCommonBundle:ExplainIndex')->getExplainTerms($id);
		$entitySub = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSubterm($id);
		$entitySyn = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSynonym($id);
		$entityRef = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfRefer($id);
		$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
				'mainTermId' => $id,
				'deleteFlag' => FALSE
				),
				array('yougoFlag' => 'ASC','subTermId' => 'ASC','year' => 'ASC'));

		// 用語データの複製
		$newTermId = $this->copyMainTerm($em, $entityMain);
		$newExpId = $this->copyExpTerm($em, $entityExp, $newTermId);
		$newSubId = $this->copySubTerm($em, $entitySub, $newTermId);
		$this->get('logger')->error("***新規登録subid***".serialize($newSubId));
		$newSynId = $this->copySynTerm($em, $entitySyn, $newTermId);
		$this->copyRefTerm($em, $entityRef, $newTermId);
		$this->copyCenterData($em, $entityCenter, $newTermId, $newSubId, $newSynId, $newExpId);

		$cur_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findBy(array(
				'deleteFlag' => FALSE
		));
		$ver_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findBy(array(
				'deleteFlag' => FALSE
		));

		$page = 1;
		if($request->query->has('page')){
			$page = $request->query->get('page');
		}

		return $this->redirect($this->generateUrl('client.yougo.edit', array('term_id' => $newTermId)));
	}

	/**
	 * @Route("/yougo/vacant/ajax", name="client.yougo.vacant.ajax")
	 */
	public function getVacantAjaxAction(Request $request){
		$user = $this->getUser();

		$yougo_id = "";
		if($request->request->has('term_id')){
			$yougo_id = $request->request->get('term_id');
		}
		$user_id = "";
		if($request->request->has('user_id')){
			$user_id = $request->request->get('user_id');
		}

		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Vacant')->getOccupiedYougoData($yougo_id, $user_id);

		if(!$entity){
			$response = new JsonResponse(array("return_cd" => true, "name" => ''));
		}else{
			// 編集中ユーザ名の取得
			$edit_user = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:User')->findOneBy(array(
					'user_id' => $entity[0]['user_id'],
					'deleteFlag' => FALSE
			));

			$response = new JsonResponse(array("return_cd" => false, "name" => $edit_user->getName()));
		}

		return $response;
	}

	/**
	 * @Route("/yougo/logout", name="client.yougo.logout")
	 */
	public function yougoLogoutAction() {
		$user = $this->getUser();
		// 用語を未編集にする
		$this->closeGenko($user->getUserId());

		return $this->redirect($this->generateUrl('client.logout'));
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
		$session->remove(self::SES_SEARCH_SUB_TERM_KEY);
		$session->remove(self::SES_SEARCH_LIST_COUNT_KEY);
		$session->remove(self::SES_SORT_ORDER_KEY);
		$session->remove(self::SES_SORT_FIELD_KEY);
		$session->remove(self::SES_SEARCH_RESPONCE_TERM_ID_KEY);
	}
}
