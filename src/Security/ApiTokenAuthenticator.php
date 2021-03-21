<?php

namespace App\Security;

use App\Repository\ApiTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class ApiTokenAuthenticator extends AbstractGuardAuthenticator
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ApiTokenRepository */
    private $apiTokenRepository;

    /**
     * ApiTokenAuthenticator constructor.
     * @param ApiTokenRepository $apiTokenRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        ApiTokenRepository $apiTokenRepository,
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
        $this->apiTokenRepository = $apiTokenRepository;
    }

    /**
     * This detemince if this authenticator should authenticate or igone request
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request)
    {
        return $request->headers->has('Authorization')
            && 0 === strpos($request->headers->get('Authorization'), 'Bearer ');
    }

    /**
     * Get the credentials (token) from the request
     * @param Request $request
     * @return false|mixed|string
     */
    public function getCredentials(Request $request)
    {
        return $token = substr($request->headers->get('Authorization'), 7);
    }

    /**
     * Check if apiToken is set and valid
     * Return userId to checkCredentials()
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     * @return \App\Entity\User|UserInterface|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $apiToken = $this->apiTokenRepository->findOneBy([
            'token' => $credentials
        ]);

        if (!$apiToken) {
            throw new CustomUserMessageAuthenticationException('Invalid API token');
        }

        if ($apiToken->isExpired()) {
            throw new CustomUserMessageAuthenticationException('Api token is expired');
        }

        /** @var int $userId */
        return $userId = $apiToken->getUser();
    }

    /**
     * Return true, api token was valid, we do not check any credentials in this TokenAuthenticator
     * @param mixed $credentials
     * @param UserInterface $user
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    /**
     * Send 401, not authorized error
     * @param Request $request
     * @param AuthenticationException $exception
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new JsonResponse([
           'Message'. $exception->getMessageKey(),
        ], 401);
    }

    /**
     * Dont do anything during tokenAuthentication, just return true to let request through
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     * @return \Symfony\Component\HttpFoundation\Response|void|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // allow the request to continue
    }

    /**
     * Not used in this TokenAuthentication guard
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return \Symfony\Component\HttpFoundation\Response|void
     * @throws \Exception
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        throw new \Exception('Not used in TokenAuthenticator');
    }

    /**
     * @return false
     */
    public function supportsRememberMe()
    {
        return false;
    }
}
