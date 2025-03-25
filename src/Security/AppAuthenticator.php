<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\HttpFoundation\Response;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'login';

    public function __construct(
        private RouterInterface $router,
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {}

    public function authenticate(Request $request): Passport
    {
        // Stocke l'URL dans la session
        $request->getSession()->set('redirect_to', $request->headers->get('referer'));

        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $csrfToken = $request->request->get('_csrf_token');

        // Ajout des badges pour l'authentification
        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('log_in', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // Créer la réponse HTTP
        $response = new Response();
        $response->setContent(json_encode([
            'error' => true,
            'message' => 'Échec de l\'authentification, veuillez vérifier vos identifiants.'
        ]));
        $response->headers->set('Content-Type', 'application/json');
        $response->setStatusCode(401); // Code de statut HTTP pour une authentification échouée

        return $response;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        // Récupérer l'URL de redirection stockée dans la session
        $redirectTo = $request->getSession()->get('redirect_to', '/');  // Redirige vers la page demandée ou vers la page par défaut

        // Après la connexion, redirige l'utilisateur vers l'URL stockée
        return new RedirectResponse($redirectTo);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate(self::LOGIN_ROUTE);
    }
}
