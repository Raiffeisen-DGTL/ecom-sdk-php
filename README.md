# Ecommerce payment API SDK

[![Build Status](https://github.com/Raiffeisen-DGTL/ecom-sdk-php/actions/workflows/ci.yaml/badge.svg)](https://github.com/Raiffeisen-DGTL/ecom-sdk-php/actions/workflows/ci.yaml)
[![Latest Stable Version](https://poser.pugx.org/raiffeisen-ecom/payment-sdk/version)](https://packagist.org/packages/raiffeisen-ecom/payment-sdk)
[![Total Downloads](https://poser.pugx.org/raiffeisen-ecom/payment-sdk/downloads)](https://packagist.org/packages/raiffeisen-ecom/payment-sdk)

SDK модуль для внедрения эквайринга Райффайзенбанка.

## Установка и подключение

Установка с помощью [composer](https://getcomposer.org/download/):

```bash
$ composer require raiffeisen-ecom/payment-sdk
```

## Документация

**Raiffeisenbank e-commerce API: https://pay.raif.ru/doc/ecom.html

Генерация авто-документации `composer run docs`.

## Авторизация

Для использования SDK требуется секретный ключ `$secretKey` и идентификатор мерчанта `$publicId`, подробности [в документации](https://pay.raif.ru/doc/ecom.html#section/API/Avtorizaciya) и на [сайте банка](https://www.raiffeisen.ru/corporate/management/commerce/).

```php
<?php

$secretKey = '***';
$publicId = '***';
$ecomClient = new \Raiffeisen\Ecom\Client($secretKey, $publicId);

?>
```

## Примеры

Пользователь совершает следующие действия в процессе платежа:

* Выбирает товары/услуги в корзину магазина и нажимает кнопку “Оплатить”;
* Партнер открывает платежную форму;
* Клиент вводит реквизиты на платежной форме и подтверждает платеж.

### Настройка URL для приема событий

Метод `postCallbackUrl` устанавливает адресс приема событий.
В параметрах нужно указать:

* `$callbackUrl` - невый URL.

```php
<?php

$callbackUrl = 'http://test.ru/';

/** @var \Raiffeisen\Ecom\Client $client */
$client->postCallbackUrl($callbackUrl);

?>
```

### Платежная форма

Метод `getPayUrl` возвращает ссылку на платежную форму.
В параметрах нужно указать:

* `$amount` - сумма заказа;
* `$orderId` - идентификатор заказа;
* `$query` - дополнительные параметры запроса.

```php
<?php

$amount = 10;
$orderId = 'testOrder';
$query = [
  'successUrl' => 'http://test.ru/',
];

/** @var \Raiffeisen\Ecom\Client $client */
$link = $client->getPayUrl(200, '***', $params);

echo $link;

?>
```

Вывод:

```
https://e-commerce.raiffeisen.ru/pay/?publicId=***&amount=10&orderId=testOrder&successUrl=http%3A%2F%2Ftest.ru%2F
```

### Получение информации о статусе транзакции

Метод `getOrderTransaction` возвращает информацию о статусе транзакции.
В параметрах нужно указать:

* `$orderId` - идентификатор заказа.

```php
<?php

$orderId = 'testOrder';

/** @var \Raiffeisen\Ecom\Client $client */
$response = $client->getOrderTransaction($orderId);

print_r($response);

?>
```

Вывод:

```
Array
(
    [code] => SUCCESS
    [transaction] => Array
    (
        [id] => 120059
        [orderId] => testOrder
        [status] => Array
        (
            [value] => SUCCESS
            [date] => 2019-07-11T17:45:13+03:00
        )
        [paymentMethod] => acquiring
        [paymentParams] => Array
        (
            [rrn] => 935014591810
            [authCode] => 25984
        )
        [amount] => 12500.5
        [comment] => Покупка шоколадного торта
        [extra] => Array
        (
            [additionalInfo] => Sweet Cake
        )
    )
)
```

### Оформление возврата по платежу

Метод `postOrderRefund` создает возврат по заказу.
В параметрах нужно указать:

* `$orderId` - идентификатор заказа;
* `$refundId` - идентификатор заказа;
* `$amount` - сумма возврата.

```php
<?php

$orderId = 'testOrder';
$refundId = 'testRefund';
$amount = 150;

/** @var \Raiffeisen\Ecom\Client $client */
$response = $client->postOrderRefund($orderId, $refundId, $amount);

print_r($response);

?>
```

Вывод:

```
Array
(
    [code] => SUCCESS
    [amount] => 150
    [refundStatus] => IN_PROGRESS
)
```

### Статус возврата

Метод `getOrderRefund` возвращает статус возврата.
В параметрах нужно указать:

* `$orderId` - идентификатор заказа;
* `$refundId` - идентификатор заказа.

```php
<?php

$orderId = 'testOrder';
$refundId = 'testRefund';

/** @var \Raiffeisen\Ecom\Client $client */
$response = $client->getOrderRefund($orderId, $refundId);

print_r($response);

?>
```

Вывод:

```
Array
(
    [code] => SUCCESS
    [amount] => 150
    [refundStatus] => COMPLETED
)
```

### Получение информации о заказе

Метод `getOrder` возвращает данные о заказе.
В параметрах нужно указать:

* `$orderId` - идентификатор заказа.

```php
<?php

$orderId = 'testOrder';

/** @var \Raiffeisen\Ecom\Client $client */
$response = $client->getOrder($orderId);

print_r($response);

?>
```

Вывод:

```
Array
(
    [amount] => 12500.5
    [comment] => Покупка шоколадного торт
    [extra] => Array
    (
        [additionalInfo] => sweet cake
    )
    [status] => Array
    (
        [value] => NEW
        [date] => 2019-08-24T14:15:22+03:00
    )
    [expirationDate] => 2019-08-24T14:15:22+03:00
)
```

### Отмена выставленного заказа

Метод `deleteOrder` удаляет заказ, если он не был оплачен.
В параметрах нужно указать:

* `$orderId` - идентификатор заказа.

```php
<?php

$orderId = 'testOrder';

/** @var \Raiffeisen\Ecom\Client $client */
$client->deleteOrder($orderId);

?>
```

### Получение списка чеков

Метод `getOrderReceipts` возвращает список чеков.
В параметрах нужно указать:

* `$orderId` - идентификатор заказа.
* `$receiptType` - необязательное, тип чека:
  * sell – чек прихода;
  * refund – чек возврата.

```php
<?php

$orderId = 'testOrder';

/** @var \Raiffeisen\Ecom\Client $client */
$response = $client->getOrderReceipts($orderId);

print_r($response);

?>
```

Вывод:

```
Array
(
    [0] => Array
    (
        [receiptNumber] => 3000827351831
        [receiptType] => REFUND
        [status] => DONE
        [orderNumber] => testOrder
        [total] => 1200
        [customer] => Array
        (
            [email] => customer@test.ru
            [name] => Иванов Иван Иванович
        )
        [items] => Array
        (
            [0] => Array
            (
                [name] => Шоколадный торт
                [price] => 1200
                [quantity] => 1
                [amount] => 1200
                [paymentObject] => COMMODITY
                [paymentMode] => FULL_PREPAYMENT
                [measurementUnit] => шт
                [nomenclatureCode] => 00 00 00 01 00 21 FA 41 00 23 05 41 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 12 00 AB 00
                [vatType] => VAT20
                [agentType] => ANOTHER
                [supplierInfo] => Array
                (
                    [phone] => +79991234567
                    [name] => ООО «Ромашка»
                    [inn] => 1234567890
                )
            )
        )
    )
)
```

### Получение чека возврата

Метод `getOrderRefundReceipt` возвращает чек возврата.
В параметрах нужно указать:

* `$orderId` - идентификатор заказа;
* `$refundId` - идентификатор возврата.

```php
<?php

$orderId = 'testOrder';
$refundId = 'testRefund';

/** @var \Raiffeisen\Ecom\Client $client */
$response = $client->getOrderRefundReceipt($orderId, $refundId);

print_r($response);

?>
```

Вывод:

```
Array
(
    [receiptNumber] => 3000827351831
    [receiptType] => REFUND
    [status] => DONE
    [orderNumber] => testOrder
    [total] => 1200
    [customer] => Array
    (
        [email] => customer@test.ru
        [name] => Иванов Иван Иванович
    )
    [items] => Array
    (
        [0] => Array
        (
            [name] => Шоколадный торт
            [price] => 1200
            [quantity] => 1
            [amount] => 1200
            [paymentObject] => COMMODITY
            [paymentMode] => FULL_PREPAYMENT
            [measurementUnit] => шт
            [nomenclatureCode] => 00 00 00 01 00 21 FA 41 00 23 05 41 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 12 00 AB 00
            [vatType] => VAT20
            [agentType] => ANOTHER
            [supplierInfo] => Array
            (
                [phone] => +79991234567
                [name] => ООО «Ромашка»
                [inn] => 1234567890
            )
        )
    )
)
```

### Уведомление о платеже

Метод `checkEventSignature` проверяет подпись уведомления о платеже.
В параметрах нужно указать:

* `$signature` - содержимое заголовка `x-api-signature-sha256`;
* `$eventBody` - разобранный JSON из тела запроса.

```php
<?php

$signature = '***';
$eventBody = [
    'event' => 'payment',
    'transaction' => [
        'id' => 120059,
        'orderId' => 'testOrder',
        'status' => [
            "value" => 'SUCCESS',
            "date" => '2019-07-11T17:45:13+03:00',
        ],
        'paymentMethod' => 'acquiring',
        'paymentParams' => [
            'rrn' => 935014591810,
            'authCode' => 25984,
        ],
        'amount' => 12500.5,
        'comment' => 'Покупка шоколадного торта',
        'extra' => [
            'additionalInfo': 'Sweet Cake',
        ],
    ],
];

/** @var \Raiffeisen\Ecom\Client $client */
$client->checkEventSignature($signature, $eventBody); // true or false

?>
```

## Требования

* **PHP v5.6.0** или выше
* расширение PHP **json**
* расширение PHP **curl**

## Лицензия

[MIT](LICENSE)

