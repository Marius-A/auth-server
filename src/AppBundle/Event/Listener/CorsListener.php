<?php

namespace AppBundle\Event\Listener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class handling Cross Origin Resource Sharing
 * @package AppBundle\Event\Listener
 */
class CorsListener
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($this->isOriginAllowed($this->getOriginHeader($event->getRequest()))) {
            $this->addCorsHeaders($event->getResponse());
        }
    }

    protected function getOriginHeader(Request $request)
    {
        return $request->headers->get('origin');
    }

    protected function isOriginAllowed($origin)
    {
        return true;
    }

    protected function addCorsHeaders(Response $response)
    {
        $responseHeaders = $response->headers;
        $responseHeaders->set('Access-Control-Allow-Origin', '*');
        $responseHeaders->set('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, PATCH, OPTIONS');
        $responseHeaders->set('Access-Control-Allow-Headers', 'Authorization, Accept, Accept-Language, Connection, Content-Length, Content-Type, Cookie, Host, Origin, Referer, User-Agent');
    }
}