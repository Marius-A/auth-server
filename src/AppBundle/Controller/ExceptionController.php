<?php

namespace AppBundle\Controller;

use AppBundle\Exception\ApiException;
use OAuth2\OAuth2ServerException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use \Symfony\Bundle\TwigBundle\Controller\ExceptionController as BaseExceptionController;


class ExceptionController extends BaseExceptionController
{
    /**
     * Converts an Exception to a Response.
     *
     * A "showException" request parameter can be used to force display of an error page (when set to false) or
     * the exception page (when true). If it is not present, the "debug" value passed into the constructor will
     * be used.
     *
     * @param Request              $request   The request
     * @param FlattenException     $exception A FlattenException instance
     * @param DebugLoggerInterface $logger    A DebugLoggerInterface instance
     *
     * @return Response
     *
     * @throws \InvalidArgumentException When the exception template does not exist
     */
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        $currentContent = $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1));
        $showException = $request->attributes->get('showException', $this->debug); // As opposed to an additional parameter, this maintains BC

        $code = $exception->getCode();
        if ($code === 0) {
            $code = $exception->getStatusCode();
        }

        $message = ($code == Response::HTTP_INTERNAL_SERVER_ERROR) ? null : json_decode($exception->getMessage());

        if (json_last_error() !== JSON_ERROR_NONE) {
            $message = array($code => $exception->getMessage());
        }
        $statusText = isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '';
        if (empty($message)) {
            $message = array($code => $statusText);
        }

        return new Response(
            $this->twig->render(
                (string) $this->findTemplate($request, $request->getRequestFormat(), $code, $showException),
                array(
                    'status_code' => $code,
                    'status_text' => $message,
                    'exception' => $exception,
                    'logger' => $logger,
                    'currentContent' => $currentContent,
                )
            ),
            $code
        );
    }

    protected function isAcceptedException(\Exception $exception)
    {
        return (
            $exception instanceof ApiException ||
            $exception instanceof AccessDeniedHttpException ||
            $exception instanceof OAuth2ServerException ||
            $exception instanceof AuthenticationException

        );
    }
}
