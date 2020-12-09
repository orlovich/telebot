<?php

namespace Telebot;

use Telebot\Model\DbTable_TelebotUsers;
use Telebot\Service\Helpers;

final class Model_Users extends \Core_Mapper
{
  /**
   * Table class for model data storage
   *
   * @access public
   * @var String
   */
  public $_dbClass = DbTable_TelebotUsers::class;

  public function __construct()
  {
    parent::__construct();
  }

  public $parameters = [
    'id' => [
      'pseudo' => 'Идентификатор записи'
    ],
    'user_id' => [
      'pseudo' => 'Идентификатор пользователя на площадке',
      'validators' => ['Required'],
      'type' => 'integer'
    ],
    'telegram_chat_id' => [
      'pseudo' => 'Идентификатор пользователя в telegram',
      'type' => 'integer'
    ],
    'token' => [
      'pseudo' => 'Уникальный токен, генерируемый автоматически при открытии формы, с добавлением чат-бота',
      'type' => 'uuid'
    ]
  ];

  /**
   * @param string $field
   * @param $value
   * @return static
   */
  public static function loadBy(string $field, $value): self
  {
    $self = parent::loadBy($field, $value);

    if ($self) {
      return $self;
    }

    return new self();
  }

  /**
   * @param array $params
   * @throws \Exception
   */
  public function update(array $params): void
  {
    $this->_setData($params);
    $this->save();
  }

  /**
   * Загружаем заявки организации к которой принадлежит текущий telegram пользователь
   * @return array
   */
  public function getSupplierApplications(): array
  {
    $contragentId = getActiveCompany();
    $appsString = '';
    $appsArrayStrings = [];

    if ($contragentId) {
      $params = [
        'supplier_id' => $contragentId,
        'request_from_telegram' => true
      ];

      $result = \Model_Application::getSupplierApplications($params);
      $appNumber = 0;

      foreach ($result['rows'] as $app) {
        $appNumber++;
        $procedureTitle = htmlspecialchars($app['procedure_title'], ENT_NOQUOTES);
        $appsStringLen = strlen($appsString) + strlen("{$appNumber}. <b>{$procedureTitle}</b>\n\n");

        if ($appsStringLen > Helpers::TELEGRAM_MAX_MESSAGE_LENGTH) {
          $appsArrayStrings[] = $appsString;
          $appsString = '';
        }

        $appsString .= "{$appNumber}. <b>{$procedureTitle}</b>\n\n";
      }

      if (!empty($appsString)) {
        $appsArrayStrings[] = $appsString;
      }
    }

    if (empty($result['rows'])) {
      $appsArrayStrings[] = 'У вас нет актуальных заявок.';
    }

    return $appsArrayStrings;
  }
}
