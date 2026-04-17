<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/24
 * Time: 16:36
 */

namespace App\Application\Api\File;


use Exception;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\ResourceServerMiddleware;
use WanPHP\Core\Middleware\ScopeMiddleware;
use WanPHP\Core\Service\UploaderService;

class UploadFileApi extends Action
{

  /**
   * @param UploaderService $uploader
   */
  public function __construct(private readonly UploaderService $uploader)
  {
  }

  #[Route(
    path: '/api/upload/file',
    methods: ['POST'],
    name: 'api.upload.file',
    middleware: [[ScopeMiddleware::class, 'file.write'], ResourceServerMiddleware::class]
  )]
  #[OA\Post(
    path: "/api/upload/file",
    operationId: "uploadFile",
    summary: "上传文件（支持分片）",
    security: [["bearerAuth" => ["file.write"]]],
    requestBody: new OA\RequestBody(
      required: true,
      content: new OA\MediaType(
        mediaType: "multipart/form-data",
        schema: new OA\Schema(
          required: ["file"],
          properties: [
            new OA\Property(property: "file", description: "上传的文件二进制流", type: "string", format: "binary"),
            new OA\Property(property: "type", description: "文件类型", type: "string"),
            new OA\Property(property: "total", description: "总分片数", type: "integer", example: 10),
            new OA\Property(property: "index", description: "当前分片索引", type: "integer", example: 1),
            new OA\Property(property: "md5", description: "文件唯一标识 (MD5)", type: "string")
          ]
        )
      )
    ),
    tags: ["File"],
    responses: [
      new OA\Response(response: 200, description: "上传成功", content: new OA\JsonContent()),
      new OA\Response(response: 400, description: "上传失败")
    ]
  )]
  protected function action(): Response
  {
    $uploadedFiles = $this->request->getUploadedFiles();
    $uploadedFile = $uploadedFiles['file'];

    // 上传文件
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
      $formData['openid'] = $this->getUid();
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
