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

		$header_list = $this->getDoctrine()->getEntityManager()->getRepository('CCKCommonBundle:Header')->findBy(array(
				'versionId' => $version,
				'deleteFlag' => FALSE
		));
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
	 * @Route("/master/group/delete", name="client.master.group.delete")
	 * @Method("POST|GET")
	 * @Template("GIMICClientBundle:Master:group.html.twig")
	 */
	public function deleteGroupAction(Request $request){
		$session = $request->getSession();
		if(!$request->request->has('group_id')){
			return $this->redirect($this->generateUrl('client.master.group'));
		}

		// transaction
		$em = $this->get('doctrine.orm.entity_manager');
		$em->getConnection()->beginTransaction();

		try {
			// 更新対象データの取得
			$entity = $this->getDoctrine()->getManager()->getRepository('GIMICCommonBundle:Group')->findOneBy(array(
					'id' =>$request->request->get('group_id'),
					'deleteFlag' => FALSE
			));

			if($entity){
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

		return $this->redirect($this->generateUrl('client.master.group'));
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