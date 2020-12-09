<?php

/**
 * Класс для отправки в очередь telegram сообщений
 * Class Core_Plugins_SendToTelegram
 */
class Core_Plugins_SendToTelegram
{
  private const MAIN_TRANSACTION_NUMBER = 1;

  private static $notices = [];

  public function __construct()
  {
    addListener('add_telebot_queue', [$this, 'onAddTelebotQueue']);
    addListener('transaction_commit', [$this, 'onCommitTransaction']);
    addListener('transaction_rollback', [$this, 'onRollbackTransaction']);
  }

  /**
   * @param $transactionNumber
   * @throws Exception
   */
  public function onCommitTransaction($transactionNumber): void
  {
    if (empty(self::$notices) || empty(self::$notices[$transactionNumber])) {
      return;
    }

    if ($transactionNumber > self::MAIN_TRANSACTION_NUMBER) {
      self::$notices[$transactionNumber - 1] = array_merge(getArrayValue(self::$notices, $transactionNumber - 1, []), self::$notices[$transactionNumber]);
      unset(self::$notices[$transactionNumber]);
      return;
    }

    $mqProvider = Core_Amqp_MqProvider::getInstance();
    $queue = getConfigValue('queue->telebot->name', 'telebot_gaz', true);

    foreach (self::$notices as $key => $value) {
      foreach ($value as $notice) {
        $this->sendToQueue($mqProvider, $notice, $queue);
      }
    }

    self::$notices = [];
  }

  /**
   * @param $transactionNumber
   */
  public function onRollbackTransaction($transactionNumber): void
  {
    if (empty(self::$notices)) {
      return;
    }

    if ($transactionNumber === self::MAIN_TRANSACTION_NUMBER) {
      self::$notices = [];
    } else {
      unset(self::$notices[$transactionNumber]);
    }
  }

  /**
   * @param $data
   * @throws Exception
   */
  public function onAddTelebotQueue($data): void
  {
    $transactionNumber = DbTransaction::inTransaction();
    $queue = getConfigValue('queue->telebot->name', 'telebot_gaz', true);

    if (!$transactionNumber) {
      $mqProvider = Core_Amqp_MqProvider::getInstance();
      $this->sendToQueue($mqProvider, $data, $queue);
    } else {
      self::$notices[$transactionNumber][] = $data;
    }
  }

  /**
   * @param Core_Amqp_MqProvider $mqProvider
   * @param array $data
   * @param string $queueName
   * @throws Exception
   */
  private function sendToQueue(Core_Amqp_MqProvider $mqProvider, array $data, string $queueName): void
  {
    $isSuccess = $mqProvider->sendToQueueWithConfirm($data, $queueName);

    if (!$isSuccess) {
      $error = sprintf("Произошла ошибка при отправке в очередь сообщения для пользователя: %s", $data['telegram_chat_id']);
      sysLogStr($error, LOG_ERR);
      throw new RuntimeException($error);
    }
  }
}