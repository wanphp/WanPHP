<?php

namespace App\Application\Common\Message;

use Exception;
use WanPHP\Core\Service\UserService;

class Message
{
  private array $msgData;

  public function __construct(private readonly UserService $user)
  {
  }

  /**
   * @throws Exception
   */
  public function send(array $sendUser): array
  {
    return $this->user->sendMessage($sendUser, $this->msgData);
  }

  public function login(array $data): Message
  {
    // TODO 构建通知数据包，这里使用测试公众号
    $this->msgData = [
      'template_id' => 'mu6wvson-tzMBTABC36elzNIcBe8rVEQAZKBfNiOU70',
      'url' => $data['url'],
      'data' => [
        'character_string10' => ['value' => $data['account']],
        'time3' => ['value' => date('Y-m-d H:i')],
        'thing7' => ['value' => $data['device']],
        'character_string4' => ['value' => $data['ip']],
      ]
    ];
    return $this;
  }

  public function editPassword(array $data): Message
  {
    // TODO 构建通知数据包，这里使用测试公众号
    $this->msgData = [
      'template_id' => 'WQkgxvYGNj_rEjaouWI-69iftoZ7x_EU3hn02BQXh0o',
      'url' => $data['url'],
      'data' => [
        'character_string1' => ['value' => $data['account']],
        'character_string2' => ['value' => $data['password']],
      ]
    ];
    return $this;
  }
}