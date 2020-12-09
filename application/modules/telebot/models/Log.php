<?php

namespace Telebot;

use Telebot\Model\DbTable_TelebotLog;

final class Model_Log extends \Core_Mapper
{
  /**
   * Table class for model data storage
   *
   * @access public
   * @var String
   */
  public $_dbClass = DbTable_TelebotLog::class;

  public function __construct()
  {
    parent::__construct();
  }

  public $parameters = [
    'id' => [
      'pseudo' => 'Идентификатор записи'
    ],
    'telegram_chat_id' => [
      'pseudo' => 'Идентификатор пользователя в telegram',
      'validators' => ['Required'],
      'type' => 'integer'
    ],
    'request' => [
      'pseudo' => 'Запрос пользователя из telegram',
      'type' => 'jsonb'
    ],
    'response' => [
      'pseudo' => 'Ответ пользователю в telegram',
      'type' => 'jsonb'
    ],
    'user_id' => [
      'pseudo' => 'Идентификатор пользователя на площадке',
      'type' => 'integer'
    ],
    'date_added' => [
      'pseudo' => 'Дата добавления записи в лог',
      'type' => 'timestamp with time zone'
    ]
  ];

  /**
   * @param array $params
   * @throws \Exception
   */
  public function createRecord(array $params): void
  {
    $this->_setData($params);
    $this->save();
  }

  /**
   * Получаем количество запросов к боту за последний час
   * @return int
   * @throws \Zend_Db_Adapter_Exception
   * @throws \Zend_Db_Statement_Exception
   */
  public function getCountRecordsInLastHour(): int
  {
    $db = getDbInstance();
    $db->setFetchMode(\Zend_Db::FETCH_ASSOC);
    $select = $db->select()
      ->from(DbTable_TelebotLog::NAME)
      ->where('date_added > ?', new \Zend_Db_Expr("NOW()::timestamp without time zone - interval '1 hour'"));

    return $select->query()->rowCount();
  }
}
