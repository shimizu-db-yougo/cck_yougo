<?php
namespace CCK\CommonBundle\Auth;

use CCK\CommonBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class SettingAuth extends AbstractGuardAuthenticator
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var string
     */
    private $message = 'please check inputs. ';

    /**
     * SettingAuth constructor.
     *
     * @param $em
     * @param $router
     */
    public function __construct($em, $router)
    {
        $this->em = $em;
        $this->router = $router;
    }

    /**
     * Returns a response that directs the user to authenticate.
     *
     * @param Request                 $request       The request that resulted in an AuthenticationException
     * @param AuthenticationException $authException The exception that started the authentication process
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $url = $this->router->generate('client.login');
        return new RedirectResponse($url);
    }

    /**
     * Get the authentication credentials from the request and return them
     * as any type (e.g. an associate array). If you return null, authentication
     * will be skipped.
     *
     * @param Request $request
     * @return mixed|null
     */
    public function getCredentials(Request $request)
    {
        $url = $this->router->generate('client.login');
        if ($request->getPathInfo() != $url || $request->getMethod() !== 'POST') {
            return null;
        }
        $user_id = $request->request->get('_user_id');
        $request->getSession()->set(Security::LAST_USERNAME, $user_id);
        return array(
            'user_id' => $user_id,
            'password' => $request->request->get('_password'),
        );
    }

    /**
     * Return a UserInterface object based on the credentials.
     *
     * @param mixed                 $credentials
     * @param UserProviderInterface $userProvider
     * @throws AuthenticationException
     * @return UserInterface|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (!isset($credentials['user_id'])) {
            return null;
        }
        $user_id = $credentials['user_id'];
        $user = $this->em->getRepository(User::class)
            ->findOneBy(['user_id' => $user_id]);

        if ($user) {
            return $user;
        }
        throw new CustomUserMessageAuthenticationException($this->message);
    }

    /**
     * Returns true if the credentials are valid.
     *
     * @param mixed         $credentials
     * @param UserInterface $user
     * @return bool
     * @throws AuthenticationException
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        $password = $credentials['password'];
        if (password_verify($password, $user->getPassword())) {
            return true;
        }
        throw new CustomUserMessageAuthenticationException($this->message);
    }

    /**
     * Called when authentication executed, but failed (e.g. wrong user_id password).
     *
     * @param Request                 $request
     * @param AuthenticationException $exception
     * @return Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        $url = $this->router->generate('client.login');
        return new RedirectResponse($url);
    }

    /**
     * Called when authentication executed and was successful!
     *
     * @param Request        $request
     * @param TokenInterface $token
     * @param string         $providerKey The provider (i.e. firewall) key
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $url = $this->router->generate('initialize');
        return new RedirectResponse($url);
    }

    /**
     * Does this method support remember me cookies?
     *
     * @return bool
     */
    public function supportsRememberMe()
    {
        return false;
    }
}
