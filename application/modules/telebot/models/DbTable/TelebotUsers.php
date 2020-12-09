<?php

namespace Telebot\Model;

class DbTable_TelebotUsers extends \Zend_Db_Table_Abstract {

  CONST NAME = 'telebot_users';

  /**
   * The default table name
   * @access protected
   */
  protected $_name = self::NAME;

  /**
   * Связанные таблицы.
   * @access protected
   */
  protected $_referenceMap = array(
    'User' => array(
      'columns' => 'user_id',
      'refTableClass' => 'DbTable_Users',
      'refColumns' => 'id',
      'onDelete' => parent::RESTRICT
    ),
  );
}
