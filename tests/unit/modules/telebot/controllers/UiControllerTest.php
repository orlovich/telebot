<?php

require_once APPLICATION_PATH . '/modules/telebot/controllers/UiController.php';

/**
 * Class UiControllerTest
 * @group gaz
 */
class UiControllerTest extends \Codeception\Test\Unit
{
  /**
   * @var \UnitTester
   */
  protected $tester;

  /**
   * @var Telebot_UiController
   */
  private $controller;

  /**
   * @var integer
   */
  private $telegramChatId;

  /**
   * @throws \Zend_Controller_Request_Exception
   */
  protected function _before()
  {
    $request = new \Zend_Controller_Request_Http();
    $response = new \Zend_Controller_Response_Http();
    $this->controller = new Telebot_UiController($request, $response);
    $supplier = $this->tester->createUserWithRoles(
      [\Model_User::USER_ROLE_SUPPLIER_ADMIN],
      true,
      \Model_User::TYPE_USER,
      true
    );
    $this->telegramChatId = mt_rand();
    $this->tester->loginAs(\Model_User::load($supplier));
  }

  /**
   * Проверяем, что на форму данные передаются не пустыми
   */
  public function testAddBot()
  {
    $this->controller->addBotAction();
    $this->assertNotEmpty($this->controller->view->hRef);
    $this->assertNotEmpty($this->controller->view->qrCode);
    $this->assertNotEmpty($this->controller->view->success);
  }

  /**
   * Проверяем механизм авторизации
   * @dataProvider testAuthProvider
   * @param array $params
   */
  public function testAuth(array $params)
  {
    $params['telegram_chat_id'] = $this->telegramChatId;

    // Если передаются логин и пароль
    if (!empty($params['with_data'])) {
      $user = \Model_User::load(getActiveUser());
      $params['user_name'] = $user->getUsername();
      $params['pass'] = \Helper\User::DEFAULT_USER_PASSWORD;
    }

    $params['user_id'] = getActiveUser();
    $this->tester->createTelegramUser($params);
    $params['id'] = $params['telegram_chat_id'];
    $this->controller->authAction($params);

    // Прошла ли авторизация
    $this->assertEquals($params['expected'], $this->controller->view->success);
  }

  public function testAuthProvider(): array
  {
    return [
      // Авторизация без заполнения формы
      'auth_without_login_param' => [
        [
          'expected' => true
        ]
      ],
      // Авторизация с заполнением формы пустыми данными
      'auth_with_login_param_without_login_and_pass' => [
        [
          'login' => true,
          'expected' => false
        ]
      ],
      // Авторизация с заполнением формы корректными данными
      'auth_with_login_param_with_correct_login_and_pass' => [
        [
          'login' => true,
          'with_data' => true,
          'expected' => true
        ]
      ],
    ];
  }
}
