# Установка Codecption

- Обновляем зависимости
```bash
$ composer install
```

## Запус всех тестов

- Запустить selenium:
```bash
$ codecept run --html
```
Результаты тестов будут в папке /tests/_output

- Запуск тестов в несколько потоков
```bash
$ vendor/bin/robo parallel:all
```

####Генерация документации:
```bash
php vendor/bin/phpdoc run -d ./tests -t ./tests/_output/docs
```


###### Полезные команды
- Создать новый тест
```bash
$ codecept generate:cest acceptance path/to/test/TEST_NAMECest
```
