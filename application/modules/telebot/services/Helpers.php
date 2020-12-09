<?php

namespace Telebot\Service;

final class Helpers
{
  public const TELEGRAM_MAX_MESSAGE_LENGTH = 4096;

  /**
   * Есть ли доступ к чат-боту
   * @return bool
   */
  public static function isAccess(): bool
  {
    $allowedInns = getConfigValue('telebot->allowed_inns', '');

    if (!haveRoles([\Model_User::USER_ROLE_SUPPLIER_ADMIN, \Model_User::USER_ROLE_SUPPLIER_APPLICATION_CREATION])) {
      return false;
    }

    if (empty($allowedInns)) {
      return true;
    }

    $contragent = \Model_Contragent::load(getActiveCompany());
    $allowedInns = explode(',', $allowedInns);

    foreach ($allowedInns as $inn) {
      $inn = trim($inn);
      if (isInnValid($inn) && $contragent->getInn() === $inn) {
        return true;
      }
    }

    return false;
  }

  /**
   * Получаем параметры для записи в лог
   * @param string $response
   * @param array $params
   * @return array
   */
  public static function getLogParams(string $response, array $params): array
  {
    return array_merge([
      'request' => json_encode($params, JSON_UNESCAPED_UNICODE),
      'response' => json_encode($response, JSON_UNESCAPED_UNICODE),
      'user_id' => getActiveUser() > 0 ? getActiveUser() : null,
      'date_added' => date('c')
    ], $params);
  }

  /**
   * Получаем контактные данные админов организации
   * @return string
   */
  public static function getAdminsContactInformation(): string
  {
    if (getActiveCompany() <= 0) {
      return '';
    }

    $adminsText = "У вас не хватает полномочий на получение запрашиваемых сведений.\n";
    $adminsText .= "При необходимости вы можете обратиться к вашему администратору организации для установки необходимых прав:\n";

    $company = \Model_Contragent::load(getActiveCompany());
    $usersIds = $company->getAdminUsers(array(\Model_User::USER_ROLE_ADMIN_FOR_USER));

    if (!empty($usersIds)) {
      $users = \Model_User::findObjectsBy(['id' => $usersIds]);

      foreach ($users as $user) {
        $adminsText .= sprintf(
          "%s %s %s, тел: %s, %s \n",
          htmlentities($user->getLastName()), htmlentities($user->getFirstName()), htmlentities($user->getMiddleName()), $user->getUserPhone(), $user->getUserEmail()
        );
      }
    }

    return $adminsText;
  }
}
