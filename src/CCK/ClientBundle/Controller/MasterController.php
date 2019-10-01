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
use Symfony\Component\BrowserKit\Response;
use CCK\CommonBundle\Entity\Header;
use CCK\CommonBundle\Entity\Version;
use CCK\CommonBundle\Entity\User;

/**
 * Master controller.
 * マスタ管理コントローラー
 *
 */
class MasterController extends BaseController {

	/**
	 * @var session key
	 */
	/**
	 * @var unknown
	 */
	const SES_STATUS_SEARCH_GROUP_KEY = "ses_status_search_group_key";

	/**
	 * @var unknown
	 */
	const SES_USER_SEARCH_KEYWORD_KEY = "ses_user_search_keyword_key";

	/**
	 * @var unknown
	 */
	const SES_USER_SEARCH_GROUP_KEY = "ses_user_search_group_key";

	private $response;

	/**
	 * @Route("/master/index", name="client.master.index")
	 * @Template("CCKClientBundle:master:index.html.twig")
	 */
	public function indexAction(Request $request){
		// session remove
		$this->removeSession($request);
		// session
		$session = $request->getSession();

		$this->searchSessionRemove($request);

		// get user information
		$user = $this->getUser();

		$cur_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findBy(array(
				'deleteFlag' => FALSE
		));
		$ver_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findBy(array(
				'deleteFlag' => FALSE
		));

		return array(
			'cur_list' => $cur_list,
			'ver_list' => $ver_list,
		);
	}

	/**
	 * @Route("/master/header", name="client.master.header")
	 * @Template("CCKClientBundle:master:header.html.twig")
	 */
	public function headerAction(Request $request){
		// session
		$session = $request->getSession();

		// get user information
		$user = $this->getUser();

		$page = ($request->query->has('page') && $request->query->get('page') != '') ? $request->query->get('page') : 1;

		$curriculum_list = $this->getDoctrine()->getEntityManager()->getRepository('CCKCommonBundle:Curriculum')->getCurriculumVersionList();
		$pagination = $this->createPagination($request, $curriculum_list, 30, $page);

		$cur_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findBy(array(
				'deleteFlag' => FALSE
		));
		$ver_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findBy(array(
				'deleteFlag' => FALSE
		));

		return array(
				'pagination' => $pagination,
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
		);
	}

	/**
	 * @Route("/master/header/list/{version}", name="client.master.header.list")
	 * @Template("CCKClientBundle:master:header_list.html.twig")
	 */
	public function headerListAction(Request $request, $version){
		// session
		$session = $request->getSession();

		// get user information
		$user = $this->getUser();

		$page = ($request->query->has('page') && $request->query->get('page') != '') ? $request->query->get('page') : 1;

		$header_list = $this->getDoctrine()->getEntityManager()->getRepository('CCKCommonBundle:Header')->getAllMidashi($version);
		$pagination = $this->createPagination($request, $header_list, 30, $page);

		$hen_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'versionId' => $version,
				'headerId' => '1',
				'deleteFlag' => FALSE
		));

		// 教科・版名の取得
		$entityVersion = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findOneBy(array(
				'id' => $version,
				'deleteFlag' => FALSE
		));
		$entityCurriculum = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findOneBy(array(
				'id' => $entityVersion->getCurriculumId(),
				'deleteFlag' => FALSE
		));


		$cur_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findBy(array(
				'deleteFlag' => FALSE
		));
		$ver_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findBy(array(
				'deleteFlag' => FALSE
		));

		return array(
				'pagination' => $pagination,
				'version' => $version,
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
				'hen_list' => $hen_list,
				'version_name' => $entityVersion->getName(),
				'curriculum_name' => $entityCurriculum->getName(),
		);
	}

	/**
	 * @Route("/master/header/new", name="client.master.header.new")
	 * @Method("POST|GET")
	 * @Template("CCKClientBundle:master:header_new.html.twig")
	 */
	public function newHeaderAction(Request $request){
		$session = $request->getSession();
		if(!$request->request->has('name')){
			return $this->redirect($this->generateUrl('client.master.header'));
		}

		$version = $request->request->get('version');
		$level = $request->request->get('level');
		$hen = $request->request->get('hen');
		$sho = $request->request->get('sho');
		$dai = $request->request->get('dai');
		$chu = $request->request->get('chu');

		// transaction
		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		$entity = new Header();

		try {
			$entity->setVersionId($version);
			$entity->setHeaderId($level);

			$emanage = $this->getDoctrine()->getManager();

			if($level == 1){
				$headerIdRecord = $emanage->getRepository('CCKCommonBundle:Header')->getNextHeaderId($version,$level,'hen');
				if(count($headerIdRecord) > 0){
					$next_id = $headerIdRecord[0]['max_id'] + 1;
				}else{
					$next_id = 1;
				}

				$entity->setHen($next_id);
				$entity->setSho(0);
				$entity->setDai(0);
				$entity->setChu(0);
				$entity->setKo(0);
			}elseif($level == 2){
				$headerIdRecord = $emanage->getRepository('CCKCommonBundle:Header')->getNextHeaderId($version,$level,'sho',$hen);
				if(count($headerIdRecord) > 0){
					$next_id = $headerIdRecord[0]['max_id'] + 1;
				}else{
					$next_id = 1;
				}

				$entity->setHen($hen);
				$entity->setSho($next_id);
				$entity->setDai(0);
				$entity->setChu(0);
				$entity->setKo(0);
			}elseif($level == 3){
				$headerIdRecord = $emanage->getRepository('CCKCommonBundle:Header')->getNextHeaderId($version,$level,'dai',$hen,$sho);
				if(count($headerIdRecord) > 0){
					$next_id = $headerIdRecord[0]['max_id'] + 1;
				}else{
					$next_id = 1;
				}

				$entity->setHen($hen);
				$entity->setSho($sho);
				$entity->setDai($next_id);
				$entity->setChu(0);
				$entity->setKo(0);
			}elseif($level == 4){
				$headerIdRecord = $emanage->getRepository('CCKCommonBundle:Header')->getNextHeaderId($version,$level,'chu',$hen,$sho,$dai);
				if(count($headerIdRecord) > 0){
					$next_id = $headerIdRecord[0]['max_id'] + 1;
				}else{
					$next_id = 1;
				}

				$entity->setHen($hen);
				$entity->setSho($sho);
				$entity->setDai($dai);
				$entity->setChu($next_id);
				$entity->setKo(0);
			}else{
				$headerIdRecord = $emanage->getRepository('CCKCommonBundle:Header')->getNextHeaderId($version,$level,'ko',$hen,$sho,$dai,$chu);
				if(count($headerIdRecord) > 0){
					$next_id = $headerIdRecord[0]['max_id'] + 1;
				}else{
					$next_id = 1;
				}

				$entity->setHen($hen);
				$entity->setSho($sho);
				$entity->setDai($dai);
				$entity->setChu($chu);
				$entity->setKo($next_id);
			}

			$entity->setName($request->request->get('name'));
			$entity->setSort(0);

			$em->persist($entity);
			$em->flush();
			$em->getConnection()->commit();

		} catch (\Exception $e){
			// もし、DBに登録失敗した場合rollbackする
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());
		}

		return $this->redirect($this->generateUrl('client.master.header.list', array('version' => $version)));
	}


	/**
	 * @Route("/master/header/update", name="client.master.header.update")
	 * @Method("POST|GET")
	 * @Template("CCKClientBundle:master:header_list.html.twig")
	 */
	public function updateHeaderAction(Request $request){
		$session = $request->getSession();
		if(!$request->request->has('id')){
			return $this->redirect($this->generateUrl('client.master.header'));
		}

		$version = $request->request->get('version');
		$id = (int) $request->request->get('id');
		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findOneBy(array(
				'id' =>$id,
				'deleteFlag' => FALSE
		));
		if(!$entity){
			return $this->redirect($this->generateUrl('client.master.header.list', array('version' => $version)));
		}

		// transaction
		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		try {
			if($request->request->has('header_name')){
				$entity->setName($request->request->get('header_name'));
			}

			$em->flush();
			$em->getConnection()->commit();

		} catch (\Exception $e){
			// もし、DBに登録失敗した場合rollbackする
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());
		}

		return $this->redirect($this->generateUrl('client.master.header.list', array('version' => $version)));
	}

	/**
	 * @Route("/master/header/sort", name="client.master.header.sort")
	 * @Method("POST|GET")
	 * @Template("CCKClientBundle:master:header_list.html.twig")
	 */
	public function sortHeaderAction(Request $request){
		$session = $request->getSession();
		if(!$request->request->has('version')){
			return $this->redirect($this->generateUrl('client.master.header'));
		}

		$version = $request->request->get('version');
		$level = $request->request->get('level');
		$hen = $request->request->get('hen');
		$sho = $request->request->get('sho');
		$dai = $request->request->get('dai');
		$chu = $request->request->get('chu');
		$ko = $request->request->get('ko');
		$header_order_list = $request->request->get('header_order_list');
		$header_order_list = explode(",", $header_order_list);

		$this->get('logger')->error("***header_order_list***");
		$this->get('logger')->error(serialize($header_order_list));

		$emanage = $this->getDoctrine()->getManager();

		$arrHeaderIdSet = array();
		foreach ($header_order_list as $header_order_elem){
			if($level == '1'){
				$entityHeaderSet = $emanage->getRepository('CCKCommonBundle:Header')->getSortUpdateHeader($version, (int)$level, $header_order_elem, $sho, $dai, $chu, $ko);
			}elseif ($level == '2'){
				$entityHeaderSet = $emanage->getRepository('CCKCommonBundle:Header')->getSortUpdateHeader($version, (int)$level, $hen, $header_order_elem, $dai, $chu, $ko);
			}elseif ($level == '3'){
				$entityHeaderSet = $emanage->getRepository('CCKCommonBundle:Header')->getSortUpdateHeader($version, (int)$level, $hen, $sho, $header_order_elem, $chu, $ko);
			}elseif ($level == '4'){
				$entityHeaderSet = $emanage->getRepository('CCKCommonBundle:Header')->getSortUpdateHeader($version, (int)$level, $hen, $sho, $dai, $header_order_elem, $ko);
			}elseif ($level == '5'){
				$entityHeaderSet = $emanage->getRepository('CCKCommonBundle:Header')->getSortUpdateHeader($version, (int)$level, $hen, $sho, $dai, $chu, $header_order_elem);
			}

			if(!$entityHeaderSet){
				return $this->redirect($this->generateUrl('client.master.header.list', array('version' => $version)));
			}

			$arrHeaderId = array();
			foreach ($entityHeaderSet as $entityHeader){
				array_push($arrHeaderId,$entityHeader['id']);
			}

			$this->get('logger')->error("***header_order_elem***");
			$this->get('logger')->error($header_order_elem);
			$this->get('logger')->error("***arrHeaderId***");
			$this->get('logger')->error(serialize($arrHeaderId));

			array_push($arrHeaderIdSet,$arrHeaderId);
		}

		$this->get('logger')->error("***arrHeaderIdSet***");
		$this->get('logger')->error(serialize($arrHeaderIdSet));

		// transaction
		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		try {
			$idx = 1;
			foreach ($arrHeaderIdSet as $arrHeaderIdRec) {
				$this->get('logger')->error("***header_order_list_IDX***");
				$this->get('logger')->error($header_order_list[$idx-1]);

				if($level == '1'){
					$field_name = "hen";
				}elseif($level == '2'){
					$field_name = "sho";
				}elseif($level == '3'){
					$field_name = "dai";
				}elseif($level == '4'){
					$field_name = "chu";
				}elseif($level == '5'){
					$field_name = "ko";
				}
				$emanage->getRepository('CCKCommonBundle:Header')->updateHeaderId($version,implode(',',$arrHeaderIdRec),$field_name,$idx);

				$idx++;
			}

			$em->flush();
			$em->getConnection()->commit();

		} catch (\Exception $e){
			// もし、DBに登録失敗した場合rollbackする
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());
		}

		return $this->redirect($this->generateUrl('client.master.header.list', array('version' => $version)));
	}

	/**
	 * @Route("/master/header/delete", name="client.master.header.delete")
	 * @Method("POST|GET")
	 * @Template("CCKClientBundle:master:header_list.html.twig")
	 */
	public function deleteHeaderAction(Request $request){
		$session = $request->getSession();
		if(!$request->request->has('version')){
			$response = new JsonResponse(array("return_cd" => false, "name" => 'parameter err'));
			return $response;
		}

		$version = $request->request->get('version');
		$header_id = $request->request->get('header_id');
		$header_kubun = $request->request->get('header_kubun');
		$hen = $request->request->get('hen');
		$sho = $request->request->get('sho');
		$dai = $request->request->get('dai');
		$chu = $request->request->get('chu');
		$ko = $request->request->get('ko');

		// 削除する見出しに紐づくサブ見出しIDの取得
		$entityHeaderSet = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->getSortUpdateHeader($version, $header_kubun, $hen, $sho, $dai, $chu, $ko);

		$list_header_id = '';
		foreach($entityHeaderSet as $header_ele){
			$list_header_id .= $header_ele['id'] . ',';
		}

		if(strlen($list_header_id)>0){$list_header_id = substr($list_header_id, 0, strlen($list_header_id)-1);}

		$this->get('logger')->error("**headerID***".$list_header_id);

		// 用語DBが登録された見出しは削除しない
		$term = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:MainTerm')->getYougoListByHeader($version, $list_header_id);

		if($term){
			$response = new JsonResponse(array("return_cd" => false, "name" => 'data exist err'));
			return $response;
		}

		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Header')->findOneBy(array(
				'versionId' => $version,
				'id' => $header_id,
				'deleteFlag' => FALSE
		));

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
	 * @Route("/master/curriculum", name="client.master.curriculum")
	 * @Template("CCKClientBundle:master:curriculum.html.twig")
	 */
	public function curriculumAction(Request $request){
		// session
		$session = $request->getSession();

		// get user information
		$user = $this->getUser();

		$page = ($request->query->has('page') && $request->query->get('page') != '') ? $request->query->get('page') : 1;

		$cur_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findBy(array(
				'deleteFlag' => FALSE
		));
		$ver_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findBy(array(
				'deleteFlag' => FALSE
		));

		return array(
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
		);
	}

	/**
	 * @Route("/master/curriculum/delete/{id}", name="client.master.cur.delete")
	 * @Method("POST|GET")
	 * @Template("CCKClientBundle:master:curriculum.html.twig")
	 */
	public function deleteAction(Request $request, $id){
		// get user information
		$user = $this->getUser();

		$id = (int) $id;

		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findOneBy(array(
				'id' =>$id,
				'deleteFlag' => FALSE
		));
		if(!$entity){
			return $this->redirect($this->generateUrl('client.master.curriculum'));
		}
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

		return $this->redirect($this->generateUrl('client.master.curriculum'));
	}

	/**
	 * @Route("/master/curriculum/duplication", name="client.master.cur.duplication")
	 * @Method("POST|GET")
	 * @Template("CCKClientBundle:master:curriculum.html.twig")
	 */
	public function duplicateAction(Request $request){
		$session = $request->getSession();
		if(!$request->request->has('id')){
			return $this->redirect($this->generateUrl('client.master.curriculum'));
		}

		$id = $request->request->get('id');

		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();
		$max_ver = $em->getRepository('CCKCommonBundle:Version')->getRecentVersion($id);

		$maintermRecordSet = $em->getRepository('CCKCommonBundle:MainTerm')->findBy(array(
				'curriculumId' => $max_ver['id'],
				'deleteFlag' => FALSE
		));

		try{
			$entity = new Version();

			$entity->setCurriculumId($id);
			$entity->setName($request->request->get('cur_name'));
			$em->persist($entity);
			$em->flush();

			foreach($maintermRecordSet as $mainterm){
				// 用語データの複製
				$newTermId = $this->copyMainTerm($em, $mainterm, $entity->getId());

				$entityExp = $em->getRepository('CCKCommonBundle:ExplainIndex')->getExplainTerms($mainterm->getTermId());
				$entitySub = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSubterm($mainterm->getTermId());
				$entitySyn = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSynonym($mainterm->getTermId());
				$entityRef = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfRefer($mainterm->getTermId());
				$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
						'mainTermId' => $mainterm->getTermId(),
						'deleteFlag' => FALSE
						),
						array('id' => 'ASC','yougoFlag' => 'ASC','subTermId' => 'ASC','year' => 'ASC'));

				$this->copyExpTerm($em, $entityExp, $newTermId);
				$newSubId = $this->copySubTerm($em, $entitySub, $newTermId);
				$newSynId = $this->copySynTerm($em, $entitySyn, $newTermId);
				$this->copyRefTerm($em, $entityRef, $newTermId);
				$this->copyCenterData($em, $entityCenter, $newTermId, $newSubId, $newSynId);

			}

			$em->getConnection()->commit();
		} catch (\Exception $e){
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

		}
		return $this->redirect($this->generateUrl('client.master.curriculum'));
	}

	/**
	 * @Route("/master/user", name="client.master.user")
	 * @Template("CCKClientBundle:master:user.html.twig")
	 */
	public function userAction(Request $request){
		// session
		$session = $request->getSession();

		// get user information
		$user = $this->getUser();

		$page = ($request->query->has('page') && $request->query->get('page') != '') ? $request->query->get('page') : 1;

		$user_list = $this->getDoctrine()->getEntityManager()->getRepository('CCKCommonBundle:User')->findBy(array(
				'deleteFlag' => FALSE
		));
		$pagination = $this->createPagination($request, $user_list, 30, $page);

		$cur_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findBy(array(
				'deleteFlag' => FALSE
		));
		$ver_list = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findBy(array(
				'deleteFlag' => FALSE
		));

		return array(
				'pagination' => $pagination,
				'currentUser'	=> ['user_id' => $this->getUser()->getUserId(), 'name' => $this->getUser()->getName()],
				'closeGenko' => $user->getUserId(),
				'cur_list' => $cur_list,
				'ver_list' => $ver_list,
		);
	}

	/**
	 * @Route("/master/user/new", name="client.master.user.new")
	 * @Method("POST|GET")
	 * @Template("CCKClientBundle:master:user.html.twig")
	 */
	public function newUserAction(Request $request){
		$session = $request->getSession();

		// transaction
		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		try {
			$user_id = $request->request->get('user_id');
			$password = $request->request->get('password');
			$name = $request->request->get('name');

			$user_obj = new User();

			$user_obj->setUserId($user_id);

			$encoder = $this->container->get('security.password_encoder');
			$encoded = $encoder->encodePassword($user_obj, $request->request->get('password'));
			$user_obj->setPassword($encoded);

			$user_obj->setName($name);
			$user_obj->setListCnt(0);

			$em->persist($user_obj);
			$em->flush();
			$em->getConnection()->commit();
		} catch (\Exception $e){
			// もし、DBに登録失敗した場合rollbackする
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());
		}

		return $this->redirect($this->generateUrl('client.master.user'));
	}


	/**
	 * @Route("/master/user/update", name="client.master.user.update")
	 * @Method("POST|GET")
	 * @Template("CCKClientBundle:,master:user.html.twig")
	 */
	public function updateUserAction(Request $request){
		$session = $request->getSession();
		if(!$request->request->has('id')){
			return $this->redirect($this->generateUrl('client.master.user'));
		}

		$id = (int) $request->request->get('id');
		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:User')->findOneBy(array(
				'id' =>$id,
				'deleteFlag' => FALSE
		));
		if(!$entity){
			return $this->redirect($this->generateUrl('client.master.user'));
		}

		// transaction
		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		try {
			if($request->request->has('user_id')){
				$entity->setUserId($request->request->get('user_id'));
			}
			if($request->request->has('password')){
				$encoder = $this->container->get('security.password_encoder');
				$encoded = $encoder->encodePassword($entity, $request->request->get('password'));
				$entity->setPassword($encoded);
			}
			if($request->request->has('name')){
				$entity->setName($request->request->get('name'));
			}

			$em->flush();
			$em->getConnection()->commit();

		} catch (\Exception $e){
			// もし、DBに登録失敗した場合rollbackする
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());
		}

		return $this->redirect($this->generateUrl('client.master.user'));
	}

	/**
	 * @Route("/master/user/delete/{id}", name="client.master.user.delete")
	 * @Method("POST|GET")
	 * @Template("CCKClientBundle:master:user.html.twig")
	 */
	public function deleteUserAction(Request $request, $id){

		$id = (int) $id;

		// transaction
		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		try {
			// 更新対象データの取得
			$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:User')->findOneBy(array(
					'id' =>$id,
					'deleteFlag' => FALSE
			));

			if($entity){
				$entity->setModifyDate(new \DateTime());
				$entity->setDeleteDate(new \DateTime());
				$entity->setDeleteFlag(true);
			}

			$em->flush();
			$em->getConnection()->commit();
		} catch (\Exception $e){
			// もし、DBに登録失敗した場合rollbackする
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());
		}

		return $this->redirect($this->generateUrl('client.master.user'));
	}

	/**
	 * @Route("/master/user/ajax", name="client.master.user.ajax")
	 */
	public function getUserAjaxAction(Request $request){
		$user = $this->getUser();

		$user_id = "";
		if($request->request->has('user_id')){
			$user_id = $request->request->get('user_id');
		}

		// 自分のユーザIDは重複チェックから省く
		$self_user_id = "";
		if($request->request->has('index_id')){
			$index_id = $request->request->get('index_id');
			if($index_id != ""){
				$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:User')->findOneBy(array(
						'id' =>$index_id,
						'deleteFlag' => FALSE
				));

				if($entity){
					$self_user_id = $entity->getUserId();
				}
			}
		}

		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:User')->findOneBy(array(
				'user_id' =>$user_id
		));

		$is_used = '';
		if(($entity)&&($self_user_id != $user_id)){
			$is_used = 'user_id';
		}

		$response = new JsonResponse($is_used);

		return $response;
	}

	/**
	 * session remove
	 */
	private function removeSession($request){
		$session = $request->getSession();
		$session->remove(self::SES_STATUS_SEARCH_GROUP_KEY);
		$session->remove(self::SES_USER_SEARCH_KEYWORD_KEY);
		$session->remove(self::SES_USER_SEARCH_GROUP_KEY);
	}

}