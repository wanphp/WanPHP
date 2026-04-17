<?php

namespace App\Application\Actions\Common\Files;

use App\Application\Middleware\PermissionMiddleware;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\UploaderService;

class UploadFile extends Action
{
  public function __construct(private readonly UploaderService $uploader)
  {
  }

  #[Route(
    path: '/upload',
    methods: ['POST'],
    description: '上传文件',
    name: 'app.file.upload',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    $uploadedFiles = $this->request->getUploadedFiles();
    $uploadedFile = $uploadedFiles['file'];

    // 上传文件
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
      $formData = $this->request->getParsedBody();
      $formData['openid'] = $this->getLoginUserId();
      $formData['host'] = $this->httpHost();
      try {
        return $this->respondWithData($this->uploader->uploadFile($formData, $uploadedFile));
      } catch (Exception $exception) {
        return $this->respondWithError($exception->getMessage());
      }
    } else {
      return $this->respondWithError('文件上传失败');
    }
  }
}