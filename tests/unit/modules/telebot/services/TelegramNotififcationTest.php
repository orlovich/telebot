<?php

require_once APPLICATION_PATH . '/modules/telebot/services/TelegramNotification.php';

/**
 * Class TelegramNotificationTest
 * @group gaz
 */
class TelegramNotificationTest extends \Codeception\Test\Unit
{
  /**
   * Проверяем, что при отправке уведомления уходят корректные данные
   */
  public function testSendMessage()
  {
    $telegramNotify = new Telebot\Service\TelegramNotification();
    $result = $telegramNotify->sendMessage(mt_rand(), 'test');
    $data = json_decode($result, true);

    $this->assertArrayHasKey('message', $data);
    $this->assertNotEmpty($data['message']);
  }
}
