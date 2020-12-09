<?php

namespace Telebot\Service;

use Telebot\Model_Users;

/**
 * Класс для отправки элементов UI в сервис для последующего отображения в telegram
 * @package Telebot
 */
final class TelegramUi
{
  /**
   * Отправляем меню без кнопки авторизации
   * @param string $telegramChatId
   * @return string - Необходимо для логирования
   */
  public function sendMenuWithoutAuth(string $telegramChatId): string
  {
    $data = [
      'telegram_chat_id' => $telegramChatId,
      'buttons' => [
        'inline' => [
          ['InlineKeyboardButton("Мои заявки", callback_data="loadapps")']
        ],
        'reply' => [
          ['KeyboardButton("Главное меню")']
        ]
      ]
    ];

    fireEvent('add_telebot_queue', $data);

    return json_encode($data, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Отправляем меню с кнопкой авторизации
   * @param string $telegramChatId
   * @return string - Необходимо для логирования
   */
  public function sendMenuWithAuth(string $telegramChatId): string
  {
    $data = [
      'telegram_chat_id' => $telegramChatId,
      'buttons' => [
        'inline' => [
          ['InlineKeyboardButton("Войти на etpgaz.gazprombank.ru", login_url=LoginUrl(url="' . getConfigValue('general->site_url') . '/telebot/ui/auth"))']
        ],
        'reply' => [
          ['KeyboardButton("Главное меню")']
        ]
      ]
    ];

    fireEvent('add_telebot_queue', $data);

    return json_encode($data, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Инициализируем меню в соответствии с типом полученных данных
   * @param array $params
   * @return string
   * @throws \Exception
   */
  public function initMenu(array $params): string
  {
    // Пользователь обратился напрямую через telegram
    if (!isset($params['token'])) {
      return $this->sendMenuWithAuth($params['telegram_chat_id']);
    }

    // Пользователь обратился с площадки
    $telebotUser = Model_Users::loadBy('token', $params['token']);

    if (!$telebotUser->getTelegramChatId()) {
      $telebotUser->setTelegramChatId($params['telegram_chat_id']);
      $telebotUser->save();
    }

    return $this->sendMenuWithoutAuth($params['telegram_chat_id']);
  }
}
