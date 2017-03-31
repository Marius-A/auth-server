<?php
namespace AuthManagerBundle\Controller;


use FOS\OAuthServerBundle\Model\Token;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Message\RequestInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ManagerController extends Controller
{
    /**
     * @return string
     */
    function indexAction()
    {
        return $this->render('AuthManagerBundle::app.html.twig');
    }

    /**
     * @param Request $request
     * @return Response
     */
    function loginAction(Request $request)
    {

        $clientId = $this->getParameter('auth_client_id');
        $clientSecret = $this->getParameter('auth_client_secret');
        $username = $request->get('username');
        $password = $request->get('password');
        $token = $request->get('token');

        $requestData = array(
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => 'all'
        );

        if ($token) {
            $requestData = array_merge($requestData, array(
                'grant_type' => 'refresh_token',
                'refresh_token' => $token
            ));
        } else {
            $requestData = array_merge($requestData, array(
                'grant_type' => 'password',
                'username' => $username,
                'password' => $password
            ));
        }

        $client = new Client();
        try
        {
            try {
                /** @var GuzzleResponse $gReponse */
                $gReponse = $client->post('http://auth.mariusiliescu.me/app_dev.php/oauth/v2/token',
                    array(
                        'form_params' => $requestData
                    )
                );
            } catch (BadResponseException $e) {
                /** @var Response $gReponse */
                $gReponse = $e->getResponse();
            }

        }catch (\Exception $ex){
            die($ex->getMessage());
        }
        $response = new Response();
        $response->setStatusCode($gReponse->getStatusCode());
        $response->setContent($gReponse->getBody());
        $response->headers->add($gReponse->getHeaders());

        return $response;
    }

    /**
     * @return Response
     */
    public function configAction ()
    {
        $parameters = array(
            "services" => json_encode(array(
                "auth" => $this->getParameter('auth_base_url')
            ))
        );
        $response = new Response($this->renderView('AuthManagerBundle::config.js.twig', $parameters));
        $response->headers->set('Content-Type','text/javascript');
        return $response;
    }

    /**
     * @param string $method
     * @param null $uri
     * @param array $headers
     * @param null $body
     * @param array $options
     * @return RequestInterface
     */
    protected function createRequest($method = 'GET', $uri = null, $headers = array(), $body = null, $options = array())
    {
        $client = new Client();
        return $client->createRequest($method, $uri, $headers, $body, $options);
    }
}