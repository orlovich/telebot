<?php

use Telebot\Service\TelegramUi;
use Telebot\Service\TelegramNotification;
use Telebot\Model_Users;
use Telebot\Model_Log;
use Telebot\Service\Helpers;

/**
 * Контроллер для обработки данных с python-сервиса
 *
 * Class Telebot_ServiceController
 */
class Telebot_ServiceController extends Core_Controller_Action
{
  /**
   * @var Telebot\Service\TelegramNotification
   */
  private $telegramNotification;

  /**
   * @var Telebot\Model_Users
   */
  private $telebotUser;

  /**
   * @var Telebot\Service\TelegramUi
   */
  private $telegramUi;

  /**
   * @var Telebot\Model_Log
   */
  private $telebotLog;

  public function init()
  {
    if (APPLICATION_ENV !== 'testing' && !empty(getConfigValue('telebot->service->ip_addr'))
      && $_SERVER['REMOTE_ADDR'] !== getConfigValue('telebot->service->ip_addr')
    ) {
      throw new RuntimeException('Access denied');
    }

    try {
      // Отключаем вывод данных в шаблоны
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender(TRUE);

      $auth = new Telebot\Service\Authentication();

      $this->telebotUser = (!empty($_GET['telegram_chat_id'])
        ? Model_Users::loadBy('telegram_chat_id', htmlspecialchars($_GET['telegram_chat_id']))
        : new Model_Users()
      );

      if ($this->telebotUser->getUserId()) {
        $auth->setAuthData($this->telebotUser->getUserId());
      }

      $this->telegramUi = new TelegramUi();
      $this->telebotLog = new Model_Log();
      $this->telegramNotification = new TelegramNotification();
    } catch (Exception $e) {
      sysLogStr(
        sprintf(
          "При обработке запроса пользователя чат-бота, telegram_chat_id которого - %s, произошла ошибка: %s",
          htmlspecialchars($_GET['telegram_chat_id']),
          $e->getMessage()),
        LOG_ERR
      );
      throw new RuntimeException($e->getMessage());
    }
  }

  /**
   * @remotable
   * @param $params
   */
  public function startAction(array $params): void
  {
    $logParams = [
      'request' => json_encode($params, JSON_UNESCAPED_UNICODE),
      'date_added' => date('c')
    ];

    try {
      DbTransaction::start();

      $activeUser = getActiveUser();

      if ($activeUser > 0) {
        $logParams['user_id'] = $activeUser;
        $response = $this->telegramUi->sendMenuWithoutAuth($params['telegram_chat_id']);
      } else {
        $response = $this->telegramUi->initMenu($params);
      }

      $logParams['response'] = $response;
      $this->telebotLog->createRecord(array_merge($logParams, $params));

      DbTransaction::commit();
    } catch (Exception $e) {
      DbTransaction::rollback();
      sysLogStr(
        sprintf("При начале общения с пользователем, telegram_chat_id которого - %s, произошла ошибка: %s", $params['telegram_chat_id'], $e->getMessage()),
        LOG_ERR
      );
      throw new RuntimeException($e->getMessage());
    }
  }

  /**
   * @remotable
   * @param $params
   */
  public function mainMenuAction(array $params): void
  {
    $logParams = [
      'request' => json_encode($params, JSON_UNESCAPED_UNICODE),
      'date_added' => date('c')
    ];

    try {
      DbTransaction::start();

      $activeUser = getActiveUser();

      if ($activeUser > 0) {
        $logParams['user_id'] = $activeUser;
        $response = $this->telegramUi->sendMenuWithoutAuth($params['telegram_chat_id']);
      } else {
        $response = $this->telegramUi->sendMenuWithAuth($params['telegram_chat_id']);
        $response .= $this->telegramNotification->sendMessage(
          $params['telegram_chat_id'],
          'Для отображения главного меню вам нужно авторизоваться!'
        );
      }

      $logParams['response'] = json_encode($response, JSON_UNESCAPED_UNICODE);
      $this->telebotLog->createRecord(array_merge($logParams, $params));
      DbTransaction::commit();
    } catch (Exception $e) {
      DbTransaction::rollback();
      sysLogStr(
        sprintf("При получении меню бота пользователем, telegram_chat_id которого - %s, произошла ошибка: %s", $params['telegram_chat_id'], $e->getMessage()),
        LOG_ERR
      );
      throw new RuntimeException($e->getMessage());
    }
  }

  /**
   * @remotable
   * @param $params
   */
  public function loadAppsAction(array $params): void
  {
    try {
      DbTransaction::start();

      $logs = [];

      if (!empty($this->telebotUser->getUserId())) {
        $user = \Model_User::load($this->telebotUser->getUserId());
      }

      if (!$this->telebotUser->getTelegramChatId()) {
        $response = $this->telegramUi->sendMenuWithAuth($params['telegram_chat_id']);
        $response .= $this->telegramNotification->sendMessage(
          $params['telegram_chat_id'],
          'Для отображения списка заявок вам нужно авторизоваться!'
        );
        $logs[] = Helpers::getLogParams($response, $params);
      } elseif (!haveRoles([\Model_User::USER_ROLE_SUPPLIER_ADMIN, \Model_User::USER_ROLE_SUPPLIER_APPLICATION_CREATION])
        || (!empty($user) && $user->getStatus() === \Model_User::STATUS_BLOCKED)
      ) {
        $message = Helpers::getAdminsContactInformation();
        $response = $this->telegramNotification->sendMessage(
          $params['telegram_chat_id'],
          $message
        );
        $logs[] = Helpers::getLogParams($response, $params);
      }

      if ($this->telebotUser->getTelegramChatId()
        && haveRoles([\Model_User::USER_ROLE_SUPPLIER_ADMIN, \Model_User::USER_ROLE_SUPPLIER_APPLICATION_CREATION])
        && $user->getStatus() !== \Model_User::STATUS_BLOCKED
      ) {
        $appsArrayStrings = $this->telebotUser->getSupplierApplications();

        foreach ($appsArrayStrings as $appsString) {
          $response = $this->telegramNotification->sendMessage(
            $params['telegram_chat_id'],
            $appsString
          );

          $logs[] = Helpers::getLogParams($response, $params);
        }
      }

      foreach ($logs as $log) {
        $this->telebotLog->createRecord($log);
      }

      DbTransaction::commit();
    } catch (Exception $e) {
      DbTransaction::rollback();
      sysLogStr(
        sprintf("При запросе заявок пользователем, telegram_chat_id которого - %s, произошла ошибка: %s", $params['telegram_chat_id'], $e->getMessage()),
        LOG_ERR
      );
      throw new RuntimeException($e->getMessage());
    }
  }

  /**
   * @remotable
   */
  public function loadLogAction()
  {
    try {
      echo $this->telebotLog->getCountRecordsInLastHour();
    } catch (Exception $e) {
      throw new RuntimeException($e->getMessage());
    }
  }
}