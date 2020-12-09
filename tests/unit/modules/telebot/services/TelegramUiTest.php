<?php

require_once APPLICATION_PATH . '/modules/telebot/services/TelegramUi.php';

/**
 * Class TelegramUiTest
 * @group gaz
 */
class TelegramUiTest extends \Codeception\Test\Unit
{
  private $telegramChatId;

  /**
   * @var Telebot\Service\TelegramUi
   */
  private $telegramUi;

  protected function _before()
  {
    $this->telegramChatId = mt_rand();
    $this->telegramUi = new Telebot\Service\TelegramUi();
  }

  /**
   * Проверяем, что при отправке меню без авторизации уходят корректные данные
   */
  public function testSendMenuWithoutAuth()
  {
    $menu = $this->telegramUi->sendMenuWithoutAuth($this->telegramChatId);
    $result = json_decode($menu, true);
    $this->assertArrayHasKey('buttons', $result);
    $this->assertArrayHasKey('inline', $result['buttons']);
    $this->assertNotEmpty($result['buttons']['inline']);
    $this->assertArrayHasKey('reply', $result['buttons']);
    $this->assertNotEmpty($result['buttons']['reply']);
  }

  /**
   * Проверяем, что при отправке меню с авторизацией уходят корректные данные
   */
  public function testSendMenuWithAuth()
  {
    $menu = $this->telegramUi->sendMenuWithAuth($this->telegramChatId);
    $result = json_decode($menu, true);
    $this->assertArrayHasKey('buttons', $result);
    $this->assertArrayHasKey('inline', $result['buttons']);
    $this->assertNotEmpty($result['buttons']['inline']);
    $this->assertArrayHasKey('reply', $result['buttons']);
    $this->assertNotEmpty($result['buttons']['reply']);
  }

}
