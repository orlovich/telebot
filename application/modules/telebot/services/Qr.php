<?php

namespace Telebot\Service;

use Endroid\QrCode\QrCode;

/**
 * Класс для работы с Qr-кодом
 *
 * @package Telebot\Service
 */
final class Qr
{
  /**
   * @param string $href
   * @return string
   */
  public static function generate(string $href): string
  {
    $qrCode = new QrCode($href);
    $qrCode->setSize(150);
    $qrCode->setMargin(0);
    $dataUri = $qrCode->writeDataUri();
    return $dataUri;
  }
}
