<?php

namespace Mmd\Bundle\GithubApiCacheBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Predis\Client as PredisClient;

class DefaultController extends Controller
{
    private $tokenRegex = '/^[0-9a-f]+$/';

    private $pathRegex = '/^[0-9a-zA-Z]+(\/[0-9a-zA-Z]+)*$/';

    private $predisConnection;

    private $cacheExpireSeconds = 3600;

    /**
     * @return PredisClient
     */
    private function getPredis()
    {
        if (!$this->predisConnection) {
            $this->predisConnection = new PredisClient(
                array(
                    'scheme' => $this->container->getParameter('mmd_github_api_cache.redis.scheme'),
                    'host'   => $this->container->getParameter('mmd_github_api_cache.redis.host'),
                    'port'   => $this->container->getParameter('mmd_github_api_cache.redis.port'),
                ),
                $this->container->getParameter('mmd_github_api_cache.redis.options')
            );
        }

        return $this->predisConnection;
    }

    /**
     * @param string $path
     * @return array|null
     */
    private function getCache($path)
    {
        $key = md5($path);

        return json_decode(
            $this->getPredis()->get($key),
            true
        );
    }

    /**
     * @param string $path
     * @param array $response
     */
    private function setCache($path, $response)
    {
        $key = md5($path);

        $this->getPredis()->set(
            $key,
            json_encode($response)
        );

        $this->getPredis()->expire(
            $key,
            $this->cacheExpireSeconds
        );
    }

    public function indexAction()
    {
        return $this->render('MmdGithubApiCacheBundle:Default:index.html.twig', array());
    }

    public function apiAction(Request $request, $path)
    {
        $response = array(
            'code' => 500,
            'content' => array(
                'message' => 'Unknown'
            ),
        );

        do {
            if (!preg_match($this->pathRegex, $path)) {
                $response['code'] = 400;
                $response['content']['message'] = 'Path not allowed';
                break;
            }

            if ($cachedResponse = $this->getCache($path)) {
                $response = $cachedResponse;
                unset($cachedResponse);
                break;
            }

            {
                $token = $this->container->getParameter('mmd_github_api_cache.token');

                if (!preg_match($this->tokenRegex, $token)) {
                    $response['content']['message'] = 'Invalid token parameter';
                    break;
                }
            }

            /**
             * @var \Buzz\Browser $buzz
             */
            $buzz = $this->get('buzz');

            /**
             * @var \Buzz\Message\Response $apiResponse
             */
            $apiResponse = $buzz->get('https://api.github.com/' . $path, array(
                'Authorization' => 'token '. $token,
                'User-Agent' => 'API-Cache', // https://developer.github.com/v3/#user-agent-required
            ));

            $response['code'] = $apiResponse->getStatusCode();
            $response['content'] = $apiResponse->getContent();

            unset($apiResponse);

            if ($response['code'] === 200) {
                $this->setCache($path, $response);
            }
        } while(false);

        return new Response(
            is_array($response['content'])
                ? json_encode($response['content'], defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0)
                : $response['content'],
            $response['code']
        );
    }
}
