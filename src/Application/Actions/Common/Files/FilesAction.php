<?php

namespace App\Application\Actions\Common\Files;

use App\Application\Middleware\PermissionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\UploaderService;
use WanPHP\Core\Service\UserService;

class FilesAction extends Action
{
  public function __construct(private readonly UploaderService $uploader, private readonly UserService $user)
  {
  }

  #[Route(
    path: '/files',
    methods: ['GET'],
    description: '上传文件管理',
    name: 'app.files',
    isNav: true,
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    if ($this->isAjax()) {
      $params = $this->request->getQueryParams();
      $where = [];
      if (!empty($params['type'])) $where['type'] = intval($params['type']);

      $recordsTotal = $this->uploader->count($where);
      if (!empty($params['search']['value'])) {
        $keyword = trim($params['search']['value']);
        $keyword = addcslashes($keyword, '*%_');
        $where['name[~]'] = $keyword;
      }

      $where['ORDER'] = ['id' => 'DESC'];
      $recordsFiltered = $this->uploader->count($where);
      $limit = $this->getLimit();
      if ($limit) $where['LIMIT'] = $limit;

      $files = $this->uploader->select('id,openid,url,name,type,size,uptime', $where);
      $openid = array_unique(array_column($files, 'openid'));
      // 绑定微信
      if (!empty($openid)) $users = $this->user->getUserInfo($openid);

      foreach ($files as &$file) {
        if (!empty($users[$file['openid']])) $file['user'] = $users[$file['openid']];
        $file['download'] = $this->urlFor('app.file.download', ['id' => $file['id']]);
        $file['actions'] = [
          'edit' => ['name' => '修改', 'path' => $this->urlFor('app.file.edit', ['id' => $file['id']])],
          'delete' => ['name' => '删除', 'path' => $this->urlFor('app.file.delete', ['id' => $file['id']])]
        ];
      }
      unset($file);

      $data = [
        "draw" => $params['draw'],
        "recordsTotal" => $recordsTotal,
        "recordsFiltered" => $recordsFiltered,
        'data' => $files
      ];

      return $this->respondWithData($data);
    } else {
      $data = [
        'title' => '上传文件管理'
      ];

      return $this->respondView('pages/files/index.twig', $data);
    }
  }

}