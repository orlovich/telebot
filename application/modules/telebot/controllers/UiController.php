<?php

use Telebot\Service\TelegramUi;
use Telebot\Model_Users;
use Telebot\Model_Log;

/**
 * Контроллер для обработки данных с площадки
 *
 * Class Telebot_UiController
 */
class Telebot_UiController extends Core_Controller_Action
{

  /**
   * Получение данных для отображения на форме добавления чат-бота
   * @remotable
   */
  public function addBotAction()
  {
    try {
      DbTransaction::start();

      $userId = getActiveUser();
      $telebotUsers = Model_Users::loadBy('user_id', $userId);
      $token = generateUUID();
      $params = [
        'user_id' => $userId,
        'token' => $token
      ];
      $telebotUsers->update($params);
      $href = 'https://t.me/' . getConfigValue('telebot->bot_username', 'etpgpBot') . '?start=' . $token;

      $this->view->hRef = $href;
      $this->view->qrCode = Telebot\Service\Qr::generate($href);
      $this->view->success = true;

      DbTransaction::commit();
    } catch (Exception $e) {
      DbTransaction::rollback();
      $this->view->success = false;
      $this->view->message = $e->getMessage();
    }
  }

  /**
   * Авторизация пользователя на площадке через telegram
   * @remotable
   * @param array $params
   */
  public function authAction(array $params)
  {
    try {
      DbTransaction::start();

      $auth = new Telebot\Service\Authentication();
      $auth->checkTelegramAuth($params);

      if (empty($params['login'])) {
        $telebotUser = Model_Users::loadBy('telegram_chat_id', $params['id']);

        // Сквозная авторизация пользователя по telegram_chat_id и отправка меню в telegram
        if ($telebotUser->getUserId()) {
          $auth->setAuthData($telebotUser->getUserId());
          $telegramUi = new TelegramUi();
          $response = $telegramUi->sendMenuWithoutAuth($params['id']);
          $this->view->success = true;
        }
      } else {
        [$isSuccess, $response] = $auth->login($params);
        $this->view->success = $isSuccess;
      }

      if (!empty($response) && getActiveUser() > 0) {
        $telebotLog = new Model_Log();
        $logParams = [
          'request' => json_encode($params, JSON_UNESCAPED_UNICODE),
          'user_id' => getActiveUser(),
          'telegram_chat_id' => $params['id']
        ];
        $logParams['response'] = $response;
        $telebotLog->createRecord($logParams);
      }

      DbTransaction::commit();
    } catch (\RuntimeException $e) {
      sysLogStr(
        sprintf("При авторизации пользователя на площадке, telegram_chat_id которого - %s, произошла ошибка: %s", $params['telegram_chat_id'], $e->getMessage()),
        LOG_ERR
      );
      $this->view->success = false;
      $this->view->message = $e->getMessage();
      DbTransaction::rollback();
    } catch (Exception $e) {
      sysLogStr(
        sprintf("При авторизации пользователя на площадке, telegram_chat_id которого - %s, произошла ошибка: %s", $params['telegram_chat_id'], $e->getMessage()),
        LOG_ERR
      );
      $this->view->success = false;
      $this->view->message = 'При авторизации произошла ошибка. Обратитесь к администратору.';
      DbTransaction::rollback();
    }
  }
}