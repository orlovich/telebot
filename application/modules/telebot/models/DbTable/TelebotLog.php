<?php

namespace Telebot\Model;

class DbTable_TelebotLog extends \Zend_Db_Table_Abstract {

  CONST NAME = 'telebot_log';

  /**
   * The default table name
   * @access protected
   */
  protected $_name = self::NAME;
}
