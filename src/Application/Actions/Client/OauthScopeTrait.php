<?php

namespace App\Application\Actions\Client;

use WanPHP\Core\Middleware\ScopeMiddleware;

trait OauthScopeTrait
{
  private function oauthScopes(): array
  {
    $cacheFile = ROOT_PATH . '/var/cache/routes.cache.php';
    if (file_exists($cacheFile)) {
      $cachedRoutes = require $cacheFile;
      if (is_array($cachedRoutes)) {
        $scopes = [];

        foreach ($cachedRoutes as $item) {
          if (!empty($item['middleware'])) {
            foreach ($item['middleware'] as $mw) {
              if ($mw[0] === ScopeMiddleware::class) {
                if (is_string($mw[1])) $scopes[] = $mw[1];
                elseif (is_array($mw[1])) $scopes = array_merge($scopes, $mw[1]);
              }
            }
          }
        }
        $finalScopes = array_unique($scopes); // 去重
        sort($finalScopes);
        return $finalScopes;
      }
    }
    return [];
  }
}