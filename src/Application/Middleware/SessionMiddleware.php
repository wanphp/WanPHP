<?php
declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class SessionMiddleware implements Middleware
{

  /**
   * @param CacheInterface $cache
   * @param $sessionName
   */
  public function __construct(private readonly CacheInterface $cache, private $sessionName)
  {
  }

  /**
   * @param Request $request
   * @param RequestHandler $handler
   * @return Response
   */
  public function process(Request $request, RequestHandler $handler): Response
  {
    $ssiToken = $request->getHeaderLine('X-Wps') ?: ($request->getQueryParams()['tk'] ?? '');
    if ($ssiToken != '') {
      try {
        $session_id = $this->cache->get($ssiToken);
        if ($session_id) session_id($session_id);
        //session_set_cookie_params(600, '/');
      } catch (InvalidArgumentException $e) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write('Invalid request!' . $e->getMessage());
        return $response->withHeader('Content-Type', 'text/plain')->withStatus(403);
      }
    }
    if ($this->sessionName) session_name($this->sessionName);
    session_start();

    $authorization = $request->getHeaderLine('Authorization') ?? null;
    if ($authorization) {
      $request = $request->withAttribute('session', $_SESSION);
    }

    return $handler->handle($request);
  }
}
