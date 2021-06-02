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
use CCK\CommonBundle\Entity\Curriculum;

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
	 * @Route("/master/curriculum/new", name="client.master.cur.new")
	 * @Method("POST|GET")
	 * @Template("CCKClientBundle:master:curriculum.html.twig")
	 */
	public function newCurriculumAction(Request $request){
		$session = $request->getSession();

		// transaction
		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		try {
			$cur_name = $request->request->get('cur_name');
			$ver_name = $request->request->get('ver_name');
			$start_year = $request->request->get('start_year');
			$ranka = $request->request->get('ranka');
			$rankb = $request->request->get('rankb');

			$cur_obj = new Curriculum();
			$cur_obj->setName($cur_name);
			$cur_obj->setYear($start_year);
			$em->persist($cur_obj);
			$em->flush();

			$ver_obj = new Version();
			$ver_obj->setCurriculumId($cur_obj->getId());
			$ver_obj->setName($ver_name);
			$ver_obj->setYear($start_year);
			$ver_obj->setRankA($ranka);
			$ver_obj->setRankB($rankb);

			$em->persist($ver_obj);
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

		return $this->redirect($this->generateUrl('client.master.curriculum'));
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

		$cur_id = $entity->getCurriculumId();

		// transaction
		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		try {
			// 登録
			$em->flush();
			// 実行
			$em->getConnection()->commit();

			// 版マスタから削除したことによって、対象の教科に版データがなくなった場合は教科マスタから教科も削除する
			$entity_after = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findOneBy(array(
					'curriculumId' =>$cur_id,
					'deleteFlag' => FALSE
			));

			if(!$entity_after){
				$entity_cur = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findOneBy(array(
						'id' =>$cur_id,
						'deleteFlag' => FALSE
				));
				if(!$entity_cur){
					return $this->redirect($this->generateUrl('client.master.curriculum'));
				}
				$entity_cur->setDeleteFlag(true);
				$entity_cur->setModifyDate(new \DateTime());
				$entity_cur->setDeleteDate(new \DateTime());

				$em->getConnection()->beginTransaction();
				// 登録
				$em->flush();
				// 実行
				$em->getConnection()->commit();
			}

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
	 * @Route("/master/version/update", name="client.master.ver.update")
	 * @Method("POST|GET")
	 * @Template("CCKClientBundle:master:curriculum.html.twig")
	 */
	public function updateVersionAction(Request $request){
		$session = $request->getSession();
		if(!$request->request->has('id')){
			return $this->redirect($this->generateUrl('client.master.curriculum'));
		}

		$id = (int) $request->request->get('id');
		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findOneBy(array(
				'id' =>$id,
				'deleteFlag' => FALSE
		));
		if(!$entity){
			return $this->redirect($this->generateUrl('client.master.curriculum'));
		}

		// transaction
		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		try {
			if($request->request->has('startyear')){
				$entity->setYear($request->request->get('startyear'));
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

		return $this->redirect($this->generateUrl('client.master.curriculum'));
	}

	/**
	 * @Route("/master/textfreq/update", name="client.master.textfreq.update")
	 * @Method("POST|GET")
	 * @Template("CCKClientBundle:master:curriculum.html.twig")
	 */
	public function updateTextFreqAction(Request $request){
		$session = $request->getSession();
		if(!$request->request->has('id')){
			return $this->redirect($this->generateUrl('client.master.curriculum'));
		}

		$id = (int) $request->request->get('id');
		$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findOneBy(array(
				'id' =>$id,
				'deleteFlag' => FALSE
		));
		if(!$entity){
			return $this->redirect($this->generateUrl('client.master.curriculum'));
		}

		// transaction
		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		try {
			if($request->request->has('ranka')){
				$entity->setRankA($request->request->get('ranka'));
			}
			if($request->request->has('rankb')){
				$entity->setRankB($request->request->get('rankb'));
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

		$headerRecordSet = $em->getRepository('CCKCommonBundle:Header')->findBy(array(
				'versionId' => $max_ver['id'],
				'deleteFlag' => FALSE
		));

		try{
			$entity = new Version();

			$entity->setCurriculumId($id);
			$entity->setName($request->request->get('cur_name'));
			$em->persist($entity);
			$em->flush();

			$this->get('logger')->error("***用語複製処理:START***");

			$record_cnt = 0;
			foreach($maintermRecordSet as $mainterm){
				// 用語データの複製
				$newTermId = $this->copyMainTerm($em, $mainterm, $entity->getId());
				$term_id = $mainterm->getTermId();
				$this->get('logger')->error("***用語複製処理:用語ID=".$term_id.":COPY,MAIN***");

				$entityExp = $em->getRepository('CCKCommonBundle:ExplainIndex')->getExplainTerms($term_id);
				$entitySub = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSubterm($term_id);
				$entitySyn = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSynonym($term_id);
				$entityRef = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfRefer($term_id);
				$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
						'mainTermId' => $term_id,
						'deleteFlag' => FALSE
						),
						array('id' => 'ASC','yougoFlag' => 'ASC','subTermId' => 'ASC','year' => 'ASC'));

				$this->get('logger')->error("***用語複製処理:用語ID=".$term_id.":READ***");

				$this->copyExpTerm($em, $entityExp, $newTermId);
				$this->get('logger')->error("***用語複製処理:用語ID=".$term_id.":COPY,EXPLAIN***");
				$newSubId = $this->copySubTerm($em, $entitySub, $newTermId);
				$this->get('logger')->error("***用語複製処理:用語ID=".$term_id.":COPY,SUBTERM***");
				$newSynId = $this->copySynTerm($em, $entitySyn, $newTermId);
				$this->get('logger')->error("***用語複製処理:用語ID=".$term_id.":COPY,SYNONYM***");
				$this->copyRefTerm($em, $entityRef, $newTermId);
				$this->get('logger')->error("***用語複製処理:用語ID=".$term_id.":COPY,REFER***");
				$this->copyCenterDataByYear($em, $entityCenter, $newTermId, $newSubId, $newSynId, $request->request->get('startyear'));
				$this->get('logger')->error("***用語複製処理:用語ID=".$term_id.":COPY,CENTER***");

				$record_cnt = $record_cnt + 1;
				if($record_cnt > 99){
					$em->getConnection()->commit();
					$record_cnt = 0;
					$em->getConnection()->beginTransaction();
				}
			}

			foreach($headerRecordSet as $header){
				// 見出しデータの複製
				$this->copyHeader($em, $header, $entity->getId());
			}

			$this->get('logger')->error("***用語複製処理:END***");

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
	 * @Route("/master/curriculum/download", name="client.master.cur.download")
	 * @Method("POST|GET")
	 * @Template("CCKClientBundle:master:curriculum.html.twig")
	 */
	public function downloadAction(Request $request){
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

		$headerRecordSet = $em->getRepository('CCKCommonBundle:Header')->findBy(array(
				'versionId' => $max_ver['id'],
				'deleteFlag' => FALSE
		));

		try{
			// センター頻度開始年の更新
			$entityCurriculum = $em->getRepository('CCKCommonBundle:Curriculum')->findOneBy(array(
					'id' => $id,
					'deleteFlag' => FALSE
			));
			$entityCurriculum->setYear($request->request->get('startyear'));

			$entity = new Version();

			$entity->setCurriculumId($id);
			$entity->setName($request->request->get('cur_name'));
			$entity->setYear($request->request->get('startyear'));
			$entity->setRankA($request->request->get('ranka'));
			$entity->setRankB($request->request->get('rankb'));
			$em->persist($entity);
			$em->flush();

			// エクスポートファイルの定義
			$csvDir = $this->container->getParameter('duplicate')['dir_path'];

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

			$handleCenterFreqMain = $this->openExportFile("CenterFreqMain");
			$handleCenterFreqSub = $this->openExportFile("CenterFreqSub");
			$handleCenterFreqSyn = $this->openExportFile("CenterFreqSyn");
			$handleCenterFreqExp = $this->openExportFile("CenterFreqExp");

			$this->get('logger')->info("***用語複製処理:START***");

			// 用語IDの発番
			$maxTermIDRec = $em->getRepository('CCKCommonBundle:MainTerm')->getNewTermID();
			$newTermId = (int)$maxTermIDRec[0]['term_id'] + 1;
			$newTermIdStart = (int)$maxTermIDRec[0]['term_id'];

			$maxSubTermIDRec = $em->getRepository('CCKCommonBundle:SubTerm')->getNewTermID();
			$newSubTermId = (int)$maxSubTermIDRec[0]['id'] + 1;

			$maxSynTermIDRec = $em->getRepository('CCKCommonBundle:Synonym')->getNewTermID();
			$newSynTermId = (int)$maxSynTermIDRec[0]['id'] + 1;

			$maxExpTermIDRec = $em->getRepository('CCKCommonBundle:ExplainIndex')->getNewTermID();
			$newExpTermId = (int)$maxExpTermIDRec[0]['id'] + 1;

			$record_cnt = 0;
			foreach($maintermRecordSet as $mainterm){
				// 用語データの複製
				$newTermId = $this->exportMainTerm($handleMain[0], $mainterm, $newTermId, $entity->getId());
				$term_id = $mainterm->getTermId();
				$this->get('logger')->info("***用語複製処理:用語ID=".$term_id.":COPY,MAIN***");

				$entityExp = $em->getRepository('CCKCommonBundle:ExplainIndex')->getExplainTerms($term_id);
				$entitySub = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSubterm($term_id);
				$entitySyn = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSynonym($term_id);
				$entityRef = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfRefer($term_id);
				$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
						'mainTermId' => $term_id,
						'deleteFlag' => FALSE
				),
						array('yougoFlag' => 'ASC','subTermId' => 'ASC','year' => 'ASC'));

				$this->get('logger')->info("***用語複製処理:用語ID=".$term_id.":READ***");

				$newExpId = $this->exportExpTerm($handleExplain[0], $entityExp, $newTermId, $newExpTermId);
				$this->get('logger')->info("***用語複製処理:用語ID=".$term_id.":COPY,EXPLAIN".serialize($newExpId)."***");
				$newSubId = $this->exportSubTerm($handleSub[0], $entitySub, $newTermId, $newSubTermId);
				$this->get('logger')->info("***用語複製処理:用語ID=".$term_id.":COPY,SUBTERM".serialize($newSubId)."***");
				$newSynId = $this->exportSynTerm($handleSyn[0], $entitySyn, $newTermId, $newSynTermId);
				$this->get('logger')->info("***用語複製処理:用語ID=".$term_id.":COPY,SYNONYM".serialize($newSynId)."***");
				$this->exportRefTerm($handleRefer[0], $entityRef, $newTermId, $newTermIdStart);
				$this->get('logger')->info("***用語複製処理:用語ID=".$term_id.":COPY,REFER***");
				$arr_center_freq = $this->exportCenterDataByYear($handleCenter[0], $entityCenter, $newTermId, $newSubId, $newSynId, $newExpId, $request->request->get('startyear'));
				$this->get('logger')->info("***用語複製処理:用語ID=".$term_id.":COPY,CENTER***".serialize($arr_center_freq)."****");

				// センター頻度の更新SQL生成
				$this->exportCenterFreqMain($handleCenterFreqMain[0], $arr_center_freq[0]);
				$this->exportCenterFreqSub($handleCenterFreqSub[0], $arr_center_freq[1]);
				$this->exportCenterFreqSyn($handleCenterFreqSyn[0], $arr_center_freq[2]);
				$this->exportCenterFreqExp($handleCenterFreqExp[0], $arr_center_freq[3]);
				$this->get('logger')->info("***用語複製処理:用語ID=".$term_id.":COPY,CENTER_FREQ***");

				$newTermId++;
				if(count($newSubId) > 0){
					$newSubTermId = max($newSubId)+1;
				}
				if(count($newSynId) > 0){
					$newSynTermId = max($newSynId)+1;
				}
				if(count($newExpId) > 0){
					$newExpTermId = max($newExpId)+1;
				}
			}

			$handleHeader = $this->openExportFile("Header");
			foreach($headerRecordSet as $header){
				// 見出しデータの複製
				$this->exportHeader($handleHeader[0], $header, $entity->getId());
			}

			fclose($handleMain[0]);
			fclose($handleExplain[0]);
			fclose($handleSub[0]);
			fclose($handleSyn[0]);
			fclose($handleRefer[0]);
			fclose($handleCenter[0]);
			fclose($handleHeader[0]);

			fclose($handleCenterFreqMain[0]);
			fclose($handleCenterFreqSub[0]);
			fclose($handleCenterFreqSyn[0]);
			fclose($handleCenterFreqExp[0]);

			$rtn_cd = $this->importSQLFile($handleMain[1],$handleExplain[1],$handleSub[1],$handleSyn[1],$handleRefer[1],$handleCenter[1],$handleHeader[1],
					$handleCenterFreqMain[1],$handleCenterFreqSub[1],$handleCenterFreqSyn[1],$handleCenterFreqExp[1]);//***

			if($rtn_cd == false){
				throw new \Exception("importSQL error");
			}

			$this->get('logger')->info("***用語複製処理:END***");

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

	private function openExportFile($tablename){
		// CSV出力先の決定
		$csvName = $tablename.'_' . date('Ymd') . '_' . date('Hi') . '.csv';
		$csvDir = $this->container->getParameter('duplicate')['dir_path'];
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

	private function importSQLFile($handleMain,$handleExplain,$handleSub,$handleSyn,$handleRefer,$handleCenter,$handleHeader,$handleCenterFreqMain,$handleCenterFreqSub,$handleCenterFreqSyn,$handleCenterFreqExp){
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
		}

		$return_txt = exec($command_import . $handleHeader,$output,$retrun_ver);
		$this->get('logger')->info("***用語インポート:HEADER***" . $return_txt . ':' . $retrun_ver);

		if($retrun_ver != 0){
			return false;
		}

		$return_txt = exec($command_import . $handleCenterFreqMain,$output,$retrun_ver);
		$this->get('logger')->info("***用語インポート:CenterFreqMain***" . $return_txt . ':' . $retrun_ver);

		if($retrun_ver != 0){
			return false;
		}

		$return_txt = exec($command_import . $handleCenterFreqSub,$output,$retrun_ver);
		$this->get('logger')->info("***用語インポート:CenterFreqSub***" . $return_txt . ':' . $retrun_ver);

		if($retrun_ver != 0){
			return false;
		}

		$return_txt = exec($command_import . $handleCenterFreqSyn,$output,$retrun_ver);
		$this->get('logger')->info("***用語インポート:CenterFreqSyn***" . $return_txt . ':' . $retrun_ver);

		if($retrun_ver != 0){
			return false;
		}

		$return_txt = exec($command_import . $handleCenterFreqExp,$output,$retrun_ver);
		$this->get('logger')->info("***用語インポート:CenterFreqExp***" . $return_txt . ':' . $retrun_ver);

		if($retrun_ver != 0){
			return false;
		}else{
			return true;
		}

	}


	private function addZipFile($zip, $fileData){
		$addFilePath = file_get_contents($fileData[1]);
		$addFileName = $fileData[2];
		$zip->addFromString($addFileName , $addFilePath);

		return $zip;
	}

	/**
	 * @Route("/master/curriculum/duplication/ajax", name="client.master.duplicate.ajax")
	 */
	public function duplicateAjaxAction(Request $request){
		$session = $request->getSession();

		// response
		$response = new JsonResponse();
		// set response status
		$response->setStatusCode(JsonResponse::HTTP_OK);

		/*$cur_id = $request->request->get('id');
		$cur_name = $request->request->get('cur_name');
		$startyear = $request->request->get('startyear');*/

		$id = $request->request->get('id');

		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();
		$max_ver = $em->getRepository('CCKCommonBundle:Version')->getRecentVersion($id);

		$maintermRecordSet = $em->getRepository('CCKCommonBundle:MainTerm')->findBy(array(
				'curriculumId' => $max_ver['id'],
				'deleteFlag' => FALSE
		));

		$headerRecordSet = $em->getRepository('CCKCommonBundle:Header')->findBy(array(
				'versionId' => $max_ver['id'],
				'deleteFlag' => FALSE
		));

		try{
			$entity = new Version();

			$entity->setCurriculumId($id);
			$entity->setName($request->request->get('cur_name'));
			$em->persist($entity);
			$em->flush();

			$this->get('logger')->error("***用語複製処理:START***");

			$record_cnt = 0;
			foreach($maintermRecordSet as $mainterm){
				// 用語データの複製
				$newTermId = $this->copyMainTerm($em, $mainterm, $entity->getId());
				$term_id = $mainterm->getTermId();
				$this->get('logger')->error("***用語複製処理:用語ID=".$term_id.":COPY,MAIN***");

				$entityExp = $em->getRepository('CCKCommonBundle:ExplainIndex')->getExplainTerms($term_id);
				$entitySub = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSubterm($term_id);
				$entitySyn = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfSynonym($term_id);
				$entityRef = $em->getRepository('CCKCommonBundle:MainTerm')->getYougoDetailOfRefer($term_id);
				$entityCenter = $em->getRepository('CCKCommonBundle:Center')->findBy(array(
						'mainTermId' => $term_id,
						'deleteFlag' => FALSE
				),
						array('id' => 'ASC','yougoFlag' => 'ASC','subTermId' => 'ASC','year' => 'ASC'));

				$this->get('logger')->error("***用語複製処理:用語ID=".$term_id.":READ***");

				$this->copyExpTerm($em, $entityExp, $newTermId);
				$this->get('logger')->error("***用語複製処理:用語ID=".$term_id.":COPY,EXPLAIN***");
				$newSubId = $this->copySubTerm($em, $entitySub, $newTermId);
				$this->get('logger')->error("***用語複製処理:用語ID=".$term_id.":COPY,SUBTERM***");
				$newSynId = $this->copySynTerm($em, $entitySyn, $newTermId);
				$this->get('logger')->error("***用語複製処理:用語ID=".$term_id.":COPY,SYNONYM***");
				$this->copyRefTerm($em, $entityRef, $newTermId);
				$this->get('logger')->error("***用語複製処理:用語ID=".$term_id.":COPY,REFER***");
				$this->copyCenterDataByYear($em, $entityCenter, $newTermId, $newSubId, $newSynId, $request->request->get('startyear'));
				$this->get('logger')->error("***用語複製処理:用語ID=".$term_id.":COPY,CENTER***");

				$record_cnt = $record_cnt + 1;
				if($record_cnt > 99){
					$em->getConnection()->commit();
					$record_cnt = 0;
					$em->getConnection()->beginTransaction();
				}
			}

			foreach($headerRecordSet as $header){
				// 見出しデータの複製
				$this->copyHeader($em, $header, $entity->getId());
			}

			$this->get('logger')->error("***用語複製処理:END***");

			$em->getConnection()->commit();
		} catch (\Exception $e){
			$em->getConnection()->rollback();
			$em->close();

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			$response->setContent(json_encode(array('status' => '取り込みエラーがあります。')));
			return $response;
		}

		$response->setContent(json_encode(array('status' => '取り込み完了'.$cur_id.':'.$cur_name.':'.$startyear)));
		return $response;
	}

	public function exportMainTerm($handle, $entityMain, $newTermId, $newCurId){

		try{
			$sql = "INSERT INTO `MainTerm` (`id`, `term_id`, `curriculum_id`, `header_id`, `print_order`, `main_term`, `red_letter`, `text_frequency`, `center_frequency`, `news_exam`, `delimiter`, `western_language`, `birth_year`, `kana`, `index_add_letter`, `index_kana`, `index_original`, `index_original_kana`, `index_abbreviation`, `nombre`, `term_explain`, `handover`, `illust_filename`, `illust_caption`, `illust_kana`, `illust_nombre`, `user_id`, `create_date`, `modify_date`, `delete_date`, `delete_flag`) VALUES";
			$sql .= "(null,";
			$sql .= $newTermId.",";
			$sql .= $newCurId.",";
			$sql .= $entityMain->getHeaderId().",";
			$sql .= $entityMain->getPrintOrder().",";
			$sql .= "'".$entityMain->getMainTerm()."',";
			$sql .= (($entityMain->getRedLetter()) ? "1" : "0").",";
			$sql .= $entityMain->getTextFrequency().",";
			$sql .= $entityMain->getCenterFrequency().",";
			$sql .= (($entityMain->getNewsExam()) ? "1" : "0").",";
			$sql .= "'".$entityMain->getDelimiter()."',";
			$sql .= "'".$entityMain->getWesternLanguage()."',";
			$sql .= "'".$entityMain->getBirthYear()."',";
			$sql .= "'".$entityMain->getKana()."',";
			$sql .= "'".$entityMain->getIndexAddLetter()."',";
			$sql .= "'".$entityMain->getIndexKana()."',";
			$sql .= "'".$entityMain->getIndexOriginal()."',";
			$sql .= "'".$entityMain->getIndexOriginalKana()."',";
			$sql .= "'".$entityMain->getIndexAbbreviation()."',";
			$sql .= "0,";
			$sql .= "'".$entityMain->getTermExplain()."',";
			$sql .= "'".$entityMain->getHandover()."',";
			$sql .= "'".$entityMain->getIllustFilename()."',";
			$sql .= "'".$entityMain->getIllustCaption()."',";
			$sql .= "'".$entityMain->getIllustKana()."',";
			$sql .= $entityMain->getIllustNombre().",";
			$sql .= "'".$entityMain->getUserId()."',";
			$sql .= "NOW(),";
			$sql .= "null,";
			$sql .= "null,";
			$sql .= "0);";

			fputs($handle, $sql."\n");

		} catch (\Exception $e){

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}

		return $newTermId;
	}

	public function exportExpTerm($handle, $entityExp, $newTermId, $newId){

		try{

			$arr_rtn_id = array();
			foreach($entityExp as $entityExpRec){
				$sql = "INSERT INTO `ExplainIndex` (`id`, `main_term_id`, `index_term`, `index_add_letter`, `index_kana`, `nombre`, `create_date`, `modify_date`, `delete_date`, `delete_flag`, `text_frequency`, `center_frequency`, `news_exam`) VALUES";
				$sql .= "(".$newId.",";
				$sql .= $newTermId.",";
				$sql .= "'".$entityExpRec['indexTerm']."',";
				$sql .= "'".$entityExpRec['indexAddLetter']."',";
				$sql .= "'".$entityExpRec['indexKana']."',";
				$sql .= "0,";
				$sql .= "NOW(),";
				$sql .= "null,";
				$sql .= "null,";
				$sql .= "0,";
				$sql .= $entityExpRec['textFrequency'].",";
				$sql .= $entityExpRec['centerFrequency'].",";
				$sql .= (($entityExpRec['newsExam']) ? "1" : "0").");";

				fputs($handle, $sql."\n");

				array_push($arr_rtn_id, $newId);
				$newId++;
			}

		} catch (\Exception $e){

			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
		return $arr_rtn_id;
	}

	public function exportSubTerm($handle, $entitySub, $newTermId, $newId){

		try{

			$arr_rtn_id = array();
			foreach($entitySub as $entitySubRec){
				$sql = "INSERT INTO `SubTerm` (`id`, `main_term_id`, `sub_term`, `red_letter`, `text_frequency`, `center_frequency`, `news_exam`, `delimiter`, `kana`, `delimiter_kana`, `index_add_letter`, `index_kana`, `nombre`, `create_date`, `modify_date`, `delete_date`, `delete_flag`) VALUES";
				$sql .= "(".$newId.",";
				$sql .= $newTermId.",";
				$sql .= "'".$entitySubRec['sub_term']."',";
				$sql .= (($entitySubRec['red_letter']) ? "1" : "0").",";
				$sql .= $entitySubRec['text_frequency'].",";
				$sql .= $entitySubRec['center_frequency'].",";
				$sql .= (($entitySubRec['news_exam']) ? "1" : "0").",";
				$sql .= "'".$entitySubRec['delimiter']."',";
				$sql .= "'".$entitySubRec['kana']."',";
				$sql .= "'".$entitySubRec['delimiter_kana']."',";
				$sql .= "'".$entitySubRec['index_add_letter']."',";
				$sql .= "'".$entitySubRec['index_kana']."',";
				$sql .= "0,";
				$sql .= "NOW(),";
				$sql .= "null,";
				$sql .= "null,";
				$sql .= "0);";

				fputs($handle, $sql."\n");

				array_push($arr_rtn_id, $newId);
				$newId++;
			}

		} catch (\Exception $e){
			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
		return $arr_rtn_id;
	}

	public function exportSynTerm($handle, $entitySyn, $newTermId, $newId){

		try{

			$arr_rtn_id = array();
			foreach($entitySyn as $entitySynRec){
				$sql = "INSERT INTO `Synonym` (`id`, `main_term_id`, `term`, `red_letter`, `synonym_id`, `text_frequency`, `center_frequency`, `news_exam`, `delimiter`, `kana`, `index_add_letter`, `index_kana`, `nombre`, `create_date`, `modify_date`, `delete_date`, `delete_flag`) VALUES";
				$sql .= "(".$newId.",";
				$sql .= $newTermId.",";
				$sql .= "'".$entitySynRec['term']."',";
				$sql .= (($entitySynRec['red_letter']) ? "1" : "0").",";
				$sql .= $entitySynRec['synonym_id'].",";
				$sql .= $entitySynRec['text_frequency'].",";
				$sql .= $entitySynRec['center_frequency'].",";
				$sql .= (($entitySynRec['news_exam']) ? "1" : "0").",";
				$sql .= "'".$entitySynRec['delimiter']."',";
				$sql .= "'',";
				$sql .= "'".$entitySynRec['index_add_letter']."',";
				$sql .= "'".$entitySynRec['index_kana']."',";
				$sql .= "0,";
				$sql .= "NOW(),";
				$sql .= "null,";
				$sql .= "null,";
				$sql .= "0);";

				fputs($handle, $sql."\n");

				array_push($arr_rtn_id, $newId);
				$newId++;
			}

		} catch (\Exception $e){
			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
		return $arr_rtn_id;
	}

	public function exportRefTerm($handle, $entityRef, $newTermId, $newTermIdStart){

		try{

			foreach($entityRef as $entityRefRec){
				$sql = "INSERT INTO `Refer` (`id`, `main_term_id`, `refer_term_id`, `nombre`, `create_date`, `modify_date`, `delete_date`, `delete_flag`) VALUES";
				$sql .= "(null,";
				$sql .= $newTermId.",";
				$sql .= ($entityRefRec['refer_term_id'] + $newTermIdStart).",";
				$sql .= "0,";
				$sql .= "NOW(),";
				$sql .= "null,";
				$sql .= "null,";
				$sql .= "0);";

				fputs($handle, $sql."\n");
			}
		} catch (\Exception $e){
			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
	}

	public function exportCenterDataByYear($handle, $entityCenter, $newTermId, $newSubId, $newSynId, $newExpId, $wkYear){

		try{
			$idx = 0;
			$idx_sub = 0;
			$idx_syn = 0;
			$idx_exp = 0;

			$wkSubTermId = '0';
			$wkYougoFlag = 1;

			$wkStartYear = $wkYear;
			$wkEndYear = $wkYear + 10;

			$arr_freq_main = array();
			$arr_freq_sub = array();
			$arr_freq_syn = array();
			$arr_freq_exp = array();

			foreach($entityCenter as $entityCenterRec){
				$idx++;
				$this->get('logger')->error("★●".$idx.":".$entityCenterRec->getId());

				if($idx > 10){
					// DBから10件読み込んだ後、対象年がある場合は、初期データを登録する
					for($i = $wkYear; $wkYear < $wkEndYear; $wkYear++){
						$sql = "INSERT INTO `Center` (`id`, `main_term_id`, `sub_term_id`, `yougo_flag`, `year`, `main_exam`, `sub_exam`, `create_date`, `modify_date`, `delete_date`, `delete_flag`) VALUES";
						$sql .= "(null,";
						$sql .= $newTermId.",";
						$sql .= $wkSubTermId.",";
						$sql .= $wkYougoFlag.",";
						$sql .= $wkYear.",";
						$sql .= "0,";
						$sql .= "0,";
						$sql .= "NOW(),";
						$sql .= "null,";
						$sql .= "null,";
						$sql .= "0);";

						fputs($handle, $sql."\n");
						$this->get('logger')->error("★１".$sql);
					}

					// DBから10件読み込んだ後、センター頻度開始年+10以上の場合はスキップする
					if($wkYear >= $wkEndYear){
						$wkYear = $entityCenterRec->getYear();
						if($wkYear >= $wkEndYear){
							continue;
						}
					}

					$wkYear = $wkStartYear;
					$idx = 1;

				}

				$wkYougoFlag = $entityCenterRec->getYougoFlag();
				if($idx < 11){
					if($wkYear > $entityCenterRec->getYear()){
						// DBの実施年より対象年が大きい場合、スキップ
						$this->get('logger')->error("★4");
					}else{
						// DBの実施年と対象年が等しい場合、元データを複製する
						if($entityCenterRec->getYougoFlag() == 1){
							$wkSubTermId = '0';

							if(isset($arr_freq_main[$newTermId])){
								$arr_freq_main[$newTermId] += $entityCenterRec->getMainExam() + $entityCenterRec->getSubExam();
							}else{
								$arr_freq_main[$newTermId] = $entityCenterRec->getMainExam() + $entityCenterRec->getSubExam();
							}
							$this->get('logger')->error("★5");
						}elseif($entityCenterRec->getYougoFlag() == 2){
							$this->get('logger')->error("★9");
							$this->get('logger')->error("★9:".serialize($newSubId));

							$wkSubTermId = $newSubId[$idx_sub];

							$this->get('logger')->error("★10");

							if(isset($arr_freq_sub[$wkSubTermId])){
								$arr_freq_sub[$wkSubTermId] += $entityCenterRec->getMainExam() + $entityCenterRec->getSubExam();
							}else{
								$arr_freq_sub[$wkSubTermId] = $entityCenterRec->getMainExam() + $entityCenterRec->getSubExam();
							}

							$this->get('logger')->error("★11");

							if($idx == 10){
								$idx_sub++;
							}
							$this->get('logger')->error("★6");
						}elseif($entityCenterRec->getYougoFlag() == 3){
							$wkSubTermId = $newSynId[$idx_syn];

							if(isset($arr_freq_syn[$wkSubTermId])){
								$arr_freq_syn[$wkSubTermId] += $entityCenterRec->getMainExam() + $entityCenterRec->getSubExam();
							}else{
								$arr_freq_syn[$wkSubTermId] = $entityCenterRec->getMainExam() + $entityCenterRec->getSubExam();
							}

							if($idx == 10){
								$idx_syn++;
							}
							$this->get('logger')->error("★7");
						}else{
							$wkSubTermId = $newExpId[$idx_exp];

							if(isset($arr_freq_exp[$wkSubTermId])){
								$arr_freq_exp[$wkSubTermId] += $entityCenterRec->getMainExam() + $entityCenterRec->getSubExam();
							}else{
								$arr_freq_exp[$wkSubTermId] = $entityCenterRec->getMainExam() + $entityCenterRec->getSubExam();
							}

							if($idx == 10){
								$idx_exp++;
							}
							$this->get('logger')->error("★8");
						}

						for($i = $wkYear; $wkYear < $entityCenterRec->getYear(); $wkYear++){
							// DBの実施年より対象年が小さい場合、初期データを登録する
							$sql = "INSERT INTO `Center` (`id`, `main_term_id`, `sub_term_id`, `yougo_flag`, `year`, `main_exam`, `sub_exam`, `create_date`, `modify_date`, `delete_date`, `delete_flag`) VALUES";
							$sql .= "(null,";
							$sql .= $newTermId.",";
							$sql .= $wkSubTermId.",";
							$sql .= $wkYougoFlag.",";
							$sql .= $wkYear.",";
							$sql .= "0,";
							$sql .= "0,";
							$sql .= "NOW(),";
							$sql .= "null,";
							$sql .= "null,";
							$sql .= "0);";

							fputs($handle, $sql."\n");
							$this->get('logger')->error("★12".$sql);
							$idx++;
						}

						$sql = "INSERT INTO `Center` (`id`, `main_term_id`, `sub_term_id`, `yougo_flag`, `year`, `main_exam`, `sub_exam`, `create_date`, `modify_date`, `delete_date`, `delete_flag`) VALUES";
						$sql .= "(null,";
						$sql .= $newTermId.",";
						$sql .= $wkSubTermId.",";
						$sql .= $wkYougoFlag.",";
						$sql .= $entityCenterRec->getYear().",";
						$sql .= $entityCenterRec->getMainExam().",";
						$sql .= $entityCenterRec->getSubExam().",";
						$sql .= "NOW(),";
						$sql .= "null,";
						$sql .= "null,";
						$sql .= "0);";

						fputs($handle, $sql."\n");
						$this->get('logger')->error("★２".$sql);
						$wkYear++;
					}
				}
			}

			// DBから10件読み込んだ後、対象年がある場合は、初期データを登録する
			for($i = $wkYear; $wkYear < $wkEndYear; $wkYear++){
				$sql = "INSERT INTO `Center` (`id`, `main_term_id`, `sub_term_id`, `yougo_flag`, `year`, `main_exam`, `sub_exam`, `create_date`, `modify_date`, `delete_date`, `delete_flag`) VALUES";
				$sql .= "(null,";
				$sql .= $newTermId.",";
				$sql .= $wkSubTermId.",";
				$sql .= $wkYougoFlag.",";
				$sql .= $wkYear.",";
				$sql .= "0,";
				$sql .= "0,";
				$sql .= "NOW(),";
				$sql .= "null,";
				$sql .= "null,";
				$sql .= "0);";

				fputs($handle, $sql."\n");
				$this->get('logger')->error("★３".$sql);
			}

			// 画面に入力した開始年がDBに登録された年を超えていると頻度データの配列ができないので、初期値を設定
			if(empty($arr_freq_main)){
				$arr_freq_main[$newTermId] = 0;
			}
			if(empty($arr_freq_sub)){
				foreach($newSubId as $newSubId_ele){
					$arr_freq_sub[$newSubId_ele] = 0;
				}
			}
			if(empty($arr_freq_syn)){
				foreach($newSynId as $newSynId_ele){
					$arr_freq_syn[$newSynId_ele] = 0;
				}
			}
			if(empty($arr_freq_exp)){
				foreach($newExpId as $newExpId_ele){
					$arr_freq_exp[$newExpId_ele] = 0;
				}
			}

		} catch (\Exception $e){
			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());


			$this->get('logger')->error("★exception".$sql);
			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
		return array($arr_freq_main,$arr_freq_sub,$arr_freq_syn,$arr_freq_exp);
	}

	public function exportHeader($handle, $entityHeader, $newCurId){

		try{
			$sql = "SET NAMES utf8;";
			fputs($handle, $sql."\n");

			$sql = "INSERT INTO `Header` (`id`, `version_id`, `header_id`, `hen`, `sho`, `dai`, `chu`, `ko`, `name`, `sort`, `create_date`, `modify_date`, `delete_date`, `delete_flag`) VALUES ";
			$sql .= "(null,";
			$sql .= $newCurId.",";
			$sql .= $entityHeader->getHeaderId().",";
			$sql .= $entityHeader->getHen().",";
			$sql .= $entityHeader->getSho().",";
			$sql .= $entityHeader->getDai().",";
			$sql .= $entityHeader->getChu().",";
			$sql .= $entityHeader->getKo().",";
			$sql .= "'".$entityHeader->getName()."',";
			$sql .= $entityHeader->getSort().",";
			$sql .= "NOW(),";
			$sql .= "null,";
			$sql .= "null,";
			$sql .= "0);";

			fputs($handle, $sql."\n");

		} catch (\Exception $e){
			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
	}

	public function exportCenterFreqMain($handle, $arr_freq){

		try{
			$sql = "UPDATE `MainTerm` SET `center_frequency` = " . array_values($arr_freq)[0] . " WHERE `term_id` = ".array_keys($arr_freq)[0].";";
			fputs($handle, $sql."\n");
		} catch (\Exception $e){
			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
	}

	public function exportCenterFreqSub($handle, $arr_freq){

		try{
			foreach($arr_freq as $key=>$val) {
				$sql = "UPDATE `SubTerm` SET `center_frequency` = " . $val . " WHERE `id` = ".$key.";";
				fputs($handle, $sql."\n");
			}
		} catch (\Exception $e){
			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
	}

	public function exportCenterFreqSyn($handle, $arr_freq){

		try{
			foreach($arr_freq as $key=>$val) {
				$sql = "UPDATE `Synonym` SET `center_frequency` = " . $val . " WHERE `id` = ".$key.";";
				fputs($handle, $sql."\n");
			}
		} catch (\Exception $e){
			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
	}

	public function exportCenterFreqExp($handle, $arr_freq){

		try{
			foreach($arr_freq as $key=>$val) {
				$sql = "UPDATE `ExplainIndex` SET `center_frequency` = " . $val . " WHERE `id` = ".$key.";";
				fputs($handle, $sql."\n");
			}
		} catch (\Exception $e){
			// log
			$this->get('logger')->error($e->getMessage());
			$this->get('logger')->error($e->getTraceAsString());

			return $this->redirect($this->generateUrl('client.yougo.list'));
		}
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
			$roles = $request->request->get('roles');

			$user_obj = new User();

			$user_obj->setUserId($user_id);

			$encoder = $this->container->get('security.password_encoder');
			$encoded = $encoder->encodePassword($user_obj, $request->request->get('password'));
			$user_obj->setPassword($encoded);

			$user_obj->setName($name);
			$user_obj->setListCnt(0);
			$user_obj->setAuthority($roles);

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
			if($request->request->has('roles')){
				$entity->setAuthority($request->request->get('roles'));
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
	 * @Route("/master/curriculum/ajax", name="client.master.curriculum.ajax")
	 */
	public function getCurriculumAjaxAction(Request $request){
		$user = $this->getUser();

		if($request->request->has('cur_name')){
			$cur_name = $request->request->get('cur_name');

			$this->get('logger')->error("***Curriculum:Name***".$cur_name);

			$is_exists = false;
			$entity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Curriculum')->findOneBy(array(
					'name' =>$cur_name,
					'deleteFlag' => FALSE
			));

			$entity_ver = false;
			if($entity){
				$this->get('logger')->error("***Curriculum:Entity***".$entity->getId().":".$entity->getName());

				$entity_ver = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:Version')->findBy(array(
						'curriculumId' =>$entity->getId(),
						'deleteFlag' => FALSE
				));

				if($entity_ver){
					$this->get('logger')->error("***Version:Entity***".serialize($entity_ver));
				}
			}

			if(($entity)&&($entity_ver)){
				$is_exists = true;
			}

			$response = new JsonResponse($is_exists);
		}else{
			$response = new JsonResponse(array(), JsonResponse::HTTP_FORBIDDEN);
		}

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