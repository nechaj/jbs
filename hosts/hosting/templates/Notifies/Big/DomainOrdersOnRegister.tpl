{*
 *  Joonte Billing System
 *  Copyright © 2020 Alex Keda, for www.host-food.ru
 *}
{assign var=Theme value="Заказ домена ({$DomainName|default:'$DomainName'}.{$Name|default:'$Name'}) поступил на регистрацию" scope=global}

Уведомляем Вас о том, что {$StatusDate|date_format:"%d.%m.%Y"} Ваш заказ #{$OrderID|string_format:"%05u"} на регистрацию домена {$DomainName|default:'$DomainName'}.{$Name|default:'$Name'} был отправлен на регистрацию.
{if isset($RegistrationMessage)}

{$RegistrationMessage|default:'$RegistrationMessage'}

{/if}
{if isset($UploadID)}

Для регистрации домена Вам необходимо загрузить документ подтверждающий личность. Для этого пройдите по ссылке:

http://www.reg.ru/user/docs/add?userdoc_secretkey={$UploadID|default:'$UploadID'}

{/if}
Данные в службе WhoIs станут доступны в течение нескольких часов, а полная регистрация Вашего доменного имени будет завершена в течение 24 часов.

---
Обращаем Ваше внимание, что последнее время участились факты фишинговых рассылок с предложением продлить домен, иначе он будет удалён/продан/заблокирован - на что хватает фантазии у создателей рассыки.
Также могут предлагать "регистрацию в поисковых системах", проверку, подтверждение владением и т.п.
В письме содерджится ссылка на оплату, но домен они, в реальности, не продлевают - просто обманывают. Будьте внимательны, проверяйте сайт, на который ведёт ссылка на оплату.

Единственный вариант, когда может быть письмо не от нас - это международные домены, в некоторых зонах требуют подтвердить контактный адрес владельца домена. Бесплатно.

