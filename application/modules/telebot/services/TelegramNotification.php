<?php

namespace Telebot\Service;

/**
 * Класс для отправки уведомлений в telegram
 * Class TelegramNotification
 * @package Telebot\Service
 */
final class TelegramNotification
{
  /**
   * Уведомляем пользователя в telegram сообщением
   * @param string $telegramChatId
   * @param string $message
   * @return string - Необходимо для логирования
   */
  public function sendMessage(string $telegramChatId, string $message): string
  {
    $data = [
      'telegram_chat_id' => $telegramChatId,
      'message' => $message
    ];

    fireEvent('add_telebot_queue', $data);

    return json_encode($data, JSON_UNESCAPED_UNICODE);
  }
}