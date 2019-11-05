<?php

declare(strict_types=1);

namespace Alchemy\RemoteAuthBundle\Security\Firewall;

use Alchemy\RemoteAuthBundle\Security\RequestHelper;
use Alchemy\RemoteAuthBundle\Security\Token\RemoteAuthToken;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class RemoteAuthListener
{
    protected $tokenStorage;
    protected $authenticationManager;

    public function __construct(TokenStorageInterface $tokenStorage, AuthenticationManagerInterface $authenticationManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
    }

    public function __invoke(RequestEvent $event)
    {
        $request = $event->getRequest();

        $accessToken = RequestHelper::getAccessTokenFromRequest($request);
        if (empty($accessToken)) {
            return;
        }

        $token = new RemoteAuthToken($accessToken);

        try {
            $authToken = $this->authenticationManager->authenticate($token);
            $this->tokenStorage->setToken($authToken);

            return;
        } catch (AuthenticationException $failed) {
            $token = $this->tokenStorage->getToken();
            if ($token instanceof RemoteAuthToken) {
                $this->tokenStorage->setToken(null);
            }
            return;
        }
    }
}