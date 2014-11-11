<?php

define('PAYEER_MERCHANT_URL', '//payeer.com/merchant/');// путь для оплаты, не менять без уведомления
define('PAYEER_MERCHANT_ID', '');						// идентификатор магазина
define('PAYEER_SECRET_KEY', '');						// секретный ключ
define('PAYEER_ORDER_DESC', '');						// комментарий от сайта
define('PAYEER_CURRENCY_CODE', '');						// валюта магазина (RUB,EUR, USD)
define('PAYEER_IPFILTER', '');							// ip фильтр, доверенные ip обработчика через запятую (можно указать маску)
define('PAYEER_EMAILERR', '');							// email для ошибок оплаты
define('PAYEER_LOGFILE', '');							// путь до файла-журнала, где идет запись оплат заказов

?>