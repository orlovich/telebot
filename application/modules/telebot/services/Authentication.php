<?php

namespace Telebot\Service;

use Exception;
use Telebot\Model_Users;
use Zend_Auth_Storage_Exception;

final class Authentication
{
  /**
   * @param array $params
   * @return array
   *    [
   *      $isSuccess - (bool) успешная ли авторизация
   *      $response - (string) ответ, отправленный в Telegram. Необходим для логов.
   *    ]
   * @throws Zend_Auth_Storage_Exception
   * @throws \ResponseException
   * @throws \Zend_Exception
   * @throws Exception
   */
  public function login(array $params): array
  {
    $isSuccess = \Model_User::login($params['user_name'], $params['pass'], false);

    if ($isSuccess && !\Model_Accreditation::isSupplier(getActiveCompany())
    ) {
      throw new \RuntimeException('Вы не аккредитованый поставщик');
    }

    $response = 'Не успешная авторизация';

    if ($isSuccess) {
      $telegramUi = new TelegramUi();
      $user = \Model_User::load(getActiveUser());

      if ($user->getStatus() === \Model_User::STATUS_BLOCKED) {
        throw new \RuntimeException(Helpers::getAdminsContactInformation());
      }

      $response = $telegramUi->sendMenuWithoutAuth($params['id']);
      $telebotUser = Model_Users::loadBy('user_id', getActiveUser());

      if ($telebotUser->getUserId()) {
        $telebotUser->setTelegramChatId($params['id']);
        $telebotUser->save();
      } else {
        $telebotUser->update(['user_id' => getActiveUser(), 'telegram_chat_id' => $params['id']]);
      }
    }

    return [$isSuccess, $response];
  }

  /**
   * Проверка данных пришедших из telegram
   * @param array $data
   * @throws Exception
   */
  public function checkTelegramAuth(array $data): void
  {
    if (APPLICATION_ENV === 'testing') {
      return;
    }

    $hash = $data['hash'];
    unset($data['hash']);

    $new_data = [];

    foreach($data as $key => $value) {
      if (in_array($key, ['module', 'controller', 'action', 'pass', 'login', 'user_name'])) {
        continue;
      }
      $new_data[] = $key . '=' . $value;
    }

    sort($new_data);

    $data_string = implode("\n", $new_data);
    $secret_hash = hash('sha256', getConfigValue('telebot->bot_token'), true);
    $new_hash = hash_hmac('sha256', $data_string, $secret_hash);

    if (strcmp($hash, $new_hash) !== 0) {
      throw new \RuntimeException('Ошибка авторизации. Подмена данных');
    }

    if ((time() - $data['auth_date']) > 86400) {
      throw new \RuntimeException('Ошибка авторизации. Время авторизации истекло');
    }
  }

  /**
   * Сохранение во временном хранилище основных данных пользователя
   * Данные необходимы для корректной работы функций getActiveCompany() и getActiveUser()
   * @param $userId int - идентификатор пользователя
   * @throws Zend_Auth_Storage_Exception
   */
  public function setAuthData(int $userId): void
  {
    $oUser = \Model_User::load($userId);
    $identity = new \stdClass();
    $identity->id = $oUser->getId();
    $identity->contragent_id = $oUser->getContragentId();
    $identity->status = $oUser->getStatus();
    $identity->user_type = $oUser->getUserType();
    $identity->username = $oUser->getUsername();
    $storage = new \Zend_Auth_Storage_NonPersistent();
    $storage->write($identity);
    \Zend_Auth::getInstance()->setStorage($storage);
  }
}
