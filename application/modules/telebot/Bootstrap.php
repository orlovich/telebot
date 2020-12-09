<?php

class Telebot_Bootstrap extends Zend_Application_Module_Bootstrap
{
  protected function _initAutoload()
  {
    $loader = function ($className) {
      if (interface_exists($className, false) || class_exists($className, false)) {
        return;
      }
      $className = str_replace('\\', '_', $className);
      Zend_Loader_Autoloader::autoload($className);
    };
    $autoloader = Zend_Loader_Autoloader::getInstance();
    $autoloader->pushAutoloader($loader, 'Telebot\\');
  }
}