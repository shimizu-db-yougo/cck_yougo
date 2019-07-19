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

	/**
	 * @var session genko id key
	 */
	const SES_GENKO_ID_KEY = "ses_genko_id_key";

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

		// 版名
		/*if($request->query->has('han')){
			$han = $request->query->get('han');
		}else{
			$han = $session->get(self::SES_SEARCH_HAN_KEY);
		}*/

		$page = ($request->query->has('page') && $request->query->get('page') != '') ? $request->query->get('page') : 1; //pageのGETパラメータを直接設定(デフォルト1)

		$entities = array();
		//$entities = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:MainTerm')->getYougoList($hid, $genko_id, $han, $charge_order, $go, $contents, $kikaku, $kikaku_shousai, null, $charge_writer, $charge_collect, null, $status, $deliveried, null, $charge_interview, $charge_order_with_collect,$user->getGroupId(),$charge_collect_id);

		$per_page = 20;
		// pagination
		$pagination = $this->createPagination($request, $entities, $per_page, $page);

		// session key
		//$session->set(self::SES_SEARCH_HAN_KEY, $han);

		$cur_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findBy(array(
				'deleteFlag' => FALSE
		));
		$ver_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findBy(array(
				'deleteFlag' => FALSE
		));
		$hen_list=array();
		$sho_list=array();
		$dai_list=array();
		$chu_list=array();
		$ko_list=array();
		$nombre='';
		$center='';
		$news_exam='';
		$term='';

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
				'nombre' => $nombre,
				'center' => $center,
				'news_exam' => $news_exam,
				'term' => $term,
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
	 * session data remove
	 */
	private function sessionRemove($request){
		$session = $request->getSession();
		$session->remove(self::SES_REQUEST_GENKO_PARAMS);
	}
}
