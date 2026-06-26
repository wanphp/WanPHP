<?php
declare(strict_types=1);

use App\Application\Middleware\DocAuthMiddleware;
use App\Application\Middleware\PermissionMiddleware;
use Defuse\Crypto\Key;
use DI\ContainerBuilder;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\ResourceServer;
use Symfony\Component\Cache\Psr16Cache;
use WanPHP\Core\Database\EntityManager;
use WanPHP\Core\Entities\ClientEntity;
use WanPHP\Core\Entities\ScopeEntity;
use WanPHP\Core\Factory\RedisCacheFactory;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Repositories\OAuth2\AccessTokenRepository;
use WanPHP\Core\Repositories\OAuth2\AuthCodeRepository;
use WanPHP\Core\Repositories\OAuth2\RefreshTokenRepository;
use WanPHP\Core\Repositories\OAuth2\UserRepository;
use function DI\autowire;

return function (ContainerBuilder $containerBuilder) {
  $definitions = [
    // 注册后台中间件，给plugin使用
    AdminPermissionMiddlewareInterface::class => autowire(PermissionMiddleware::class),
    // 注册文档中间件
    DocAuthMiddleware::class => autowire()->constructorParameter('secretPassword', getenv('API_DOCS_PASSWORD') ?: '123456'),
    // 注册 OAuth2 接口映射
    AccessTokenRepositoryInterface::class => function () {
      $redisCacheFactory = new RedisCacheFactory(
        getenv('OAUTH2_SERVER_REDIS_HOST'),
        (int)getenv('OAUTH2_SERVER_REDIS_PORT'),
        getenv('OAUTH2_SERVER_REDIS_PASSWORD')
      );
      $storage = new Psr16Cache($redisCacheFactory->create(
        (int)getenv('OAUTH2_SERVER_REDIS_STORAGE'),
        getenv('OAUTH2_SERVER_REDIS_PREFIX')
      ));
      return new AccessTokenRepository($storage);
    },
    ClientRepositoryInterface::class => function (EntityManager $entityManager) {
      return $entityManager->getRepository(ClientEntity::class);
    },
    ScopeRepositoryInterface::class => function (EntityManager $entityManager) {
      return $entityManager->getRepository(ScopeEntity::class);
    },
  ];
  // 注册 OAuth2 服务端
  if (getenv('OAUTH2_PRIVATE_KEY')) {
    $definitions[AuthorizationServer::class] = function (
      ClientRepositoryInterface      $clientRepository,
      AccessTokenRepositoryInterface $accessTokenRepository,
      ScopeRepositoryInterface       $scopeRepository,
      AuthCodeRepository             $authCodeRepository,
      RefreshTokenRepository         $refreshTokenRepository,
      UserRepository                 $userRepository,
    ) {
      $keyPath = ROOT_PATH . getenv('OAUTH2_PRIVATE_KEY');
      $privateKey = new CryptKey($keyPath, getenv('OAUTH2_PRIVATE_KEY_PASSWORD') ?: null);
      $encryptionKey = Key::loadFromAsciiSafeString(getenv('APP_ENCRYPTION_KEY'));

      $server = new AuthorizationServer(
        $clientRepository,
        $accessTokenRepository,
        $scopeRepository,
        $privateKey,
        $encryptionKey
      );

      // 设置访问令牌过期时间为2小时
      $accessTokenTTL = new DateInterval('PT2H');

      // 启用授权码模式
      $server->enableGrantType(new AuthCodeGrant($authCodeRepository, $refreshTokenRepository, new DateInterval('PT10M')), $accessTokenTTL);

      // 启用客户端凭证模式
      $server->enableGrantType(new ClientCredentialsGrant(), $accessTokenTTL);

      // 启用密码模式
      $server->enableGrantType(new PasswordGrant($userRepository, $refreshTokenRepository), $accessTokenTTL);

      // 启用刷新令牌
      $grant = new RefreshTokenGrant($refreshTokenRepository);
      //设置刷新令牌过期时间为12小时，默认是一个月
      $grant->setRefreshTokenTTL(new DateInterval('PT12H'));
      $server->enableGrantType($grant, $accessTokenTTL);

      return $server;
    };
  }
  // 注册 OAuth2 客户端
  if (getenv('OAUTH2_PUBLIC_KEY')) {
    $definitions[ResourceServer::class] = function (AccessTokenRepositoryInterface $accessTokenRepository) {
      $publicKeyPath = ROOT_PATH . getenv('OAUTH2_PUBLIC_KEY');
      if (!file_exists($publicKeyPath)) {
        throw new LogicException("Key file not found: $publicKeyPath");
      }
      return new ResourceServer($accessTokenRepository, $publicKeyPath);
    };
  }
  $containerBuilder->addDefinitions($definitions);
};
