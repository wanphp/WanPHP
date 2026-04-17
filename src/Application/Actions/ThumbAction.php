<?php

namespace App\Application\Actions;

use App\Service\Common\SettingService;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class ThumbAction extends Action
{
  private string $filepath;

  public function __construct(private readonly SettingService $config)
  {
    $this->filepath = ROOT_PATH . getenv('APP_UPLOAD_FILE_PATH');
  }

  #[Route(path: '/image/thumb/{ym:\d{6}}/{hash:[a-z0-9]+}/{name:[a-z0-9_-]+}.{ext:jpg|gif|png|webp}', methods: ['GET'], description: '生成缩略图', name: 'app.thumb')]
  protected function action(): Response
  {
    $path = $this->resolveArg('ym');
    $name = $this->resolveArg('hash');
    $thumbName = $this->resolveArg('name');
    $extension = $this->resolveArg('ext');

    $file = $this->filepath . "/image/{$path}/{$name}.{$extension}";
    if (!file_exists($file)) {
      return $this->respondWithError('Image Not Found');
    }
    $manager = new ImageManager(new Driver());
    $image = $manager->read($file);
    $thumb_path = $this->filepath . "/image/thumb/{$path}/{$name}";
    $thumb = "{$thumb_path}/{$thumbName}.{$extension}";

    //创建缩略图路径
    if (!is_dir($thumb_path)) mkdir($thumb_path, 0755, true);

    $pattern = '/^(?:(?<size>\d+x\d+|w\d+|h\d+))?(?:_(?<wm>watermark-(?<pos>[a-z]{2})-(?<scale>\d+)))?$/x';
    if (!preg_match($pattern, $thumbName, $m)) {
      return $this->response->withStatus(404);
    }

    $width = $height = 0;
    $watermark = false;
    $position = 'br';
    $watermarkImage = null;

    // 解析尺寸
    if (!empty($m['size'])) {
      if (str_contains($m['size'], 'x')) {
        [$width, $height] = array_map('intval', explode('x', $m['size']));
      } elseif ($m['size'][0] === 'w') {
        $width = (int)substr($m['size'], 1);
      } elseif ($m['size'][0] === 'h') {
        $height = (int)substr($m['size'], 1);
      }
    }
    $allow = [64, 120, 240, 320, 640, 800];
    if (
      ($width > 0 && !in_array($width, $allow, true)) ||
      ($height > 0 && !in_array($height, $allow, true))
    ) {
      return $this->response->withStatus(403);
    }

    // 解析水印
    if (!empty($m['wm'])) {
      // 取配置水印图片
      $watermarkPath = $this->config->getConfig('watermark');
      if ($watermarkPath && is_file($this->filepath . $watermarkPath)) {
        $wmScale = max((int)($m['scale'] ?? 4), 1);
        // 读取水印（独立实例）
        $wm = $manager->read($this->filepath . $watermarkPath);
        // 目标：水印宽度 = 原图宽度 / $wmScale
        $targetWidth = (int)($image->width() / $wmScale);
        // 只缩小，不放大
        if ($wm->width() > $targetWidth) {
          $wm->scale(width: $targetWidth);
        }

        $watermark = true;
        $watermarkImage = $wm;

        $position = match ($m['pos']) {
          'tl' => 'top-left',
          'tr' => 'top-right',
          'bl' => 'bottom-left',
          'br' => 'bottom-right',
          default => 'center',
        };
      }
    }

    if ($width === 0 && $height === 0 && !$watermark) {
      return $this->response->withStatus(404);
    }

    // 尺寸策略
    if ($width && $height) {
      $image->cover($width, $height);
    } elseif ($width) {
      $image->scaleDown(width: $width);
    } elseif ($height) {
      $image->scaleDown(height: $height);
    }

    // 水印
    if ($watermark) {
      $image->place($watermarkImage, $position);
    }

    // 保存
    $outputExt = $extension;
    $accept = $this->request->getHeaderLine('Accept') ?? '';
    $supportWebp = str_contains($accept, 'image/webp');
    if ($supportWebp && in_array($extension, ['jpg', 'jpeg'])) {
      $outputExt = 'webp';
    }
    if ($outputExt === 'webp') {
      $image->toWebp(85)->save($thumb);
    } else {
      $image->save($thumb);
    }

    $mime = match ($outputExt) {
      'webp' => 'image/webp',
      'png' => 'image/png',
      'gif' => 'image/gif',
      default => 'image/jpeg'
    };

    return $this->response
      ->withHeader('Content-Type', $mime)
      ->withHeader('Cache-Control', 'public, max-age=31536000')
      ->withHeader('Vary', 'Accept')
      ->withHeader('X-Accel-Redirect', '/protected-files/' . "/image/thumb/{$path}/{$name}/{$thumbName}.{$extension}");
  }
}
