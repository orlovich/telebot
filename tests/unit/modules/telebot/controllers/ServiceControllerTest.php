<?php

require_once APPLICATION_PATH . '/modules/telebot/controllers/ServiceController.php';

/**
 * Class ServiceControllerTest
 * @group gaz
 */
class ServiceControllerTest extends \Codeception\Test\Unit
{
  /**
   * @var \UnitTester
   */
  protected $tester;

  /**
   * @var Zend_Controller_Request_Http
   */
  private $request;

  /**
   * @var Zend_Controller_Response_Http
   */
  private $response;
  /**
   * @var integer
   */
  private $telegramChatId;

  /**
   * @throws \Zend_Controller_Request_Exception
   */
  protected function _before()
  {
    $this->request = new \Zend_Controller_Request_Http();
    $this->response = new \Zend_Controller_Response_Http();
    $supplier = $this->tester->createUserWithRoles(
      [\Model_User::USER_ROLE_SUPPLIER_ADMIN],
      true,
      \Model_User::TYPE_USER,
      true
    );
    $this->tester->loginAs(\Model_User::load($supplier));
    $this->telegramChatId = mt_rand();
    $params = [
      'user_id' => getActiveUser(),
      'telegram_chat_id' => $this->telegramChatId
    ];
    $this->tester->createTelegramUser($params);
  }

  /**
   * Тестируем отработку команды start от сервиса
   */
  public function testStartAction()
  {
    $controller = new Telebot_ServiceController($this->request, $this->response);
    $params = [
      'telegram_chat_id' => $this->telegramChatId
    ];
    $controller->startAction($params);
    $log = Telebot\Model_Log::loadBy('telegram_chat_id', $params['telegram_chat_id']);

    // Проверяем что запрос и ответ корректно записываются в лог
    $this->assertNotEmpty($log->getRequest());
    $this->assertNotEmpty($log->getResponse());
  }

  /**
   * Тестируем отработку команды mainmenu от сервиса
   */
  public function testMainMenuAction()
  {
    $controller = new Telebot_ServiceController($this->request, $this->response);
    $params = [
      'telegram_chat_id' => $this->telegramChatId
    ];
    $controller->mainMenuAction($params);
    $log = Telebot\Model_Log::loadBy('telegram_chat_id', $params['telegram_chat_id']);

    // Проверяем что запрос и ответ корректно записываются в лог
    $this->assertNotEmpty($log->getRequest());
    $this->assertNotEmpty($log->getResponse());
  }

  /**
   * Тестируем отработку команды loadapps от сервиса
   */
  public function testLoadAppsAction()
  {
    $_GET['telegram_chat_id'] = $this->telegramChatId;
    $controller = new Telebot_ServiceController($this->request, $this->response);
    $params = [
      'telegram_chat_id' => $this->telegramChatId
    ];
    $controller->loadAppsAction($params);
    $log = Telebot\Model_Log::loadBy('telegram_chat_id', $params['telegram_chat_id']);

    // Проверяем что запрос и ответ корректно записываются в лог
    $this->assertNotEmpty($log->getRequest());
    $this->assertNotEmpty($log->getResponse());
  }

}
