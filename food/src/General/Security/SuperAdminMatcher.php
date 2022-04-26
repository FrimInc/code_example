<?php

namespace App\General\Security;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class SuperAdminMatcher implements RequestMatcherInterface
{
    protected AuthorizationCheckerInterface $authorizationChecker;

    /**
     * @param \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return bool
     */
    public function matches(Request $request)
    {
        return $this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN');
    }
}
