<?php
declare(strict_types=1);

namespace App\Application\Handlers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpException;
use Slim\Handlers\ErrorHandler as SlimErrorHandler;
use WanPHP\Core\Exception\ValidationException;

class HttpErrorHandler extends SlimErrorHandler
{
  private bool $error = false;

  protected function respond(): Response
  {
    $statusCode = 500;
    if ($this->exception instanceof HttpException) $statusCode = $this->exception->getCode();
    $previous = $this->exception->getPrevious();
    if ($previous !== null) $statusCode = $previous->getCode() ?: $statusCode;
    $response = $this->responseFactory->createResponse($statusCode);
    $data = ['code' => $statusCode, 'message' => $this->exception->getMessage()];
    if (!$this->error) $this->logger->info($this->exception->getMessage(), [
      'path' => $this->request->getUri()->getPath(),
      'file' => $this->exception->getFile(),
      'line' => $this->exception->getLine(),
      //'trace' => $this->exception->getTraceAsString()
    ]);
    if ($previous instanceof ValidationException) $data['errors'] = $previous->errors;
    $json = json_encode($data, JSON_PRETTY_PRINT + JSON_UNESCAPED_UNICODE);
    $response->getBody()->write($json);

    return $response->withHeader('Content-Type', 'application/json');
  }

  public function logError(string $error): void
  {
    $this->error = true;
    parent::logError($error);
  }
}
