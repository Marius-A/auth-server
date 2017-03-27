<?php
/**
 * Created by PhpStorm.
 * User: andrei
 * Date: 01.07.2016
 * Time: 11:38
 */

namespace AuthManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProxyController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    function indexAction(Request $request)
    {
        $response = new Response();

        $url = $request->query->get('url');
        if (!$this->validateUrl($url)) {
            $response->setStatusCode(Response::HTTP_FORBIDDEN);

            return $response;
        }

        $method = $request->getMethod();
        $headers = $request->headers->all();
        $body = $request->getContent();

        return $response;
    }

    protected function validateUrl ($url)
    {
        $domains = $this->getParameter('proxy_domains');
        $parsedUrl = parse_url($url);
        foreach ($domains as $domain) {
            if ($domain == $parsedUrl['host']) {
                return true;
            }
        }
        return false;
    }
}