<?php
namespace CCK\ClientBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

/**
 * Security controller.
 *
 * @Route("/")
 */
class SecurityController extends Controller {

	/**
	 * @Route("/login", name="client.login")
	 * @Template()
	 */
	public function loginAction() {
		$user = $this->getUser();
		if ($user instanceof UserInterface) {
			return $this->redirectToRoute('initialize');
		}

		$authenticationUtils = $this->get('security.authentication_utils');

	    // get the login error if there is one
	    $error = $authenticationUtils->getLastAuthenticationError();

	    // last username entered by the user
	    $lastUsername = $authenticationUtils->getLastUsername();

		return array(
				'last_username' => $lastUsername,
				'error' => $error
		);
	}

	/**
	 * @Route("/login_check", name="client.login_check")
	 */
	public function loginCheckAction() {
		// this controller will not be executed,
		// as the route is handled by the Security system
	}

	/**
	 * @Route("/logout", name="client.logout")
	 */
	public function logoutAction() {
		// this controller will not be executed,
		// as the route is handled by the Security system
	}

	/**
	 * @Route("/timeout", name="client.timeout")
	 * @Template()
	 */
	public function timeoutAction() {
	}

	/**
	 * @Route("/reset_password", name="client.reset.password")
	 * @Template("CCKClientBundle:Security:reset-password.html.twig")
	 */
	public function resetPasswordAction(Request $request) {
		$session = $request->getSession();

		$address = '';
		$error = array();
		if($request->request->has('user_id')){
			$address = $request->request->get('user_id');

			if($address == ''){
				array_push($error, "メールアドレスを入力してください。");
			}

			if(!preg_match("<^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$>", $address)){
				array_push($error, "正しいメールアドレス形式で入力してください。");
			}

			if(count($error) == 0){
				$userEntity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:User')->findOneBy(array(
						'loginId' => $address,
						'deleteFlag' => FALSE
				));

				if(!$userEntity){
					array_push($error, "該当するユーザが登録されていません。");
				}
			}
		}

		if(($request->request->has('user_id'))&&(count($error) == 0)){

			try {
				$em = $this->get('doctrine.orm.entity_manager');
				$em->getConnection()->beginTransaction();

				//ランダム英数字でパスワードを生成
				$str = array_merge(range('a', 'z'), range('0', '9'));
				$r_str = null;
				for ($i = 0; $i < 8; $i++) {
					$r_str .= $str[rand(0, count($str)-1)];
				}
				$resendPassword = $r_str;

				$encoder = $this->container->get('security.password_encoder');
				$encoded = $encoder->encodePassword($userEntity, $resendPassword);
				$userEntity->setPassword($encoded);

				// 登録
				$em->flush();
				// 実行
				$em->getConnection()->commit();

				// パスワード再発行メール送信
				global $kernel;
				if ($kernel instanceOf \AppCache) {
					$kernel = $kernel->getKernel();
				}

				$mailtmpl = implode(PHP_EOL, array('「頼れるドクター」管理システムをご利用いただきありがとうございます。','メールアドレス:' . $address . 'のパスワードを再発行しました。','パスワードは下記になります。','password:' . $resendPassword ,'',
									'下記URLよりパスワードの再設定を行ってください。',$kernel->getContainer()->getParameter('domain_name') . 'update_password','',
									'――','株式会社ギミック　頼れるドクター編集部','TEL：03-6277-5939','EMAIL：bookcms.sa@cck.co.jp'));
				$mailsent = $em->getRepository('CCKCommonBundle:Smtp')->sendMail([$address], '【CCK】パスワード再発行のお知らせ', $mailtmpl);

			} catch(\Exception $e){
				// もし、DBに登録失敗した場合rollbackする
				$em->getConnection()->rollback();
				$em->close();
				// log
				$this->get('logger')->error($e->getMessage());
				$this->get('logger')->error($e->getTraceAsString());

				array_push($error, "メール送信エラー。");
			}

			if(count($error) == 0){
				array_push($error, "パスワード再設定用の仮パスワードをご登録のメールアドレス宛に送信しました。");
				array_push($error, "メールをご確認の上、再設定の手続きを行ってください。");
			}
		}

		return array(
				'error' => $error
		);
	}

	/**
	 * @Route("/update_password", name="client.update.password")
	 * @Template("CCKClientBundle:Security:update-password.html.twig")
	 */
	public function updatePasswordAction(Request $request) {
		$session = $request->getSession();
		// get user information
		$user = $this->getUser();

		$error = array();
		$address = '';
		if($request->request->has('user_id')){
			$address = $request->request->get('user_id');

			if($address == ''){
				array_push($error, "メールアドレスを入力してください。");
			}

			if(!preg_match("<^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$>", $address)){
				array_push($error, "正しいメールアドレス形式で入力してください。");
			}

			if(count($error) == 0){
				$userEntity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:User')->findOneBy(array(
						'loginId' => $address,
						'deleteFlag' => FALSE
				));

				if(!$userEntity){
					array_push($error, "該当するユーザが登録されていません。");
				}
			}
		}

		$current_password = '';
		if($request->request->has('current_password')){
			$current_password = $request->request->get('current_password');

			if($current_password == ''){
				array_push($error, "現在のパスワードを入力してください。");
			}

			if(!preg_match("/^[a-z0-9]+$/", $current_password)){
				array_push($error, "半角英数字で入力してください。");
			}

			if(count($error) == 0){
				$encoder = $this->container->get('security.password_encoder');
				$encoded = $encoder->encodePassword($userEntity, $current_password);

				$userEntity = $this->getDoctrine()->getManager()->getRepository('CCKCommonBundle:User')->findOneBy(array(
						'password' => $encoded,
						'deleteFlag' => FALSE
				));

				if(!$userEntity){
					array_push($error, "パスワードを確認してください。");
				}
			}
		}

		$password = '';
		if($request->request->has('password')){
			$password = $request->request->get('password');

			if($password == ''){
				array_push($error, "パスワードを入力してください。");
			}elseif((strlen($password) < 6)||(strlen($password) > 12)){
				array_push($error, "パスワードは6桁以上12桁以下で入力してください。");
			}

			if((!preg_match("/[a-z]/", $password))||
				(!preg_match("/[A-Z]/", $password))||
				(!preg_match("/[0-9]/", $password))||
				(!preg_match("/[ -\/:-@\[-`\{-\~]/", $password))){
				array_push($error, "パスワードは半角の英大文字・英小文字・数字・記号をすべて使用して入力してください。");
			}
		}

		$password_retype = '';
		if($request->request->has('password_retype')){
			$password_retype = $request->request->get('password_retype');

			if($password_retype == ''){
				array_push($error, "確認パスワードを入力してください。");
			}
		}

		if(($password != '')&&($password_retype != '')&&($password != $password_retype)){
			array_push($error, "確認パスワードが一致していません。");
		}

		$complete = false;
		if(($request->request->has('password'))&&(count($error) == 0)){

			try {
				$em = $this->get('doctrine.orm.entity_manager');
				$em->getConnection()->beginTransaction();

				$encoder = $this->container->get('security.password_encoder');
				$encoded = $encoder->encodePassword($userEntity, $password);
				$userEntity->setPassword($encoded);

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

				array_push($error, "DB接続エラー。");
			}

			if(count($error) == 0){
				array_push($error, "パスワードの変更が完了しました。");
				array_push($error, "ログイン画面から変更したパスワードでログインしてください。");
				$complete = true;
			}else{
				$complete = false;
			}
		}

		return array(
				'error' => $error,
				'complete' => $complete
		);
	}
}