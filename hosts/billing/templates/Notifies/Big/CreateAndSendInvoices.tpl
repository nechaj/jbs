{*
 *  Joonte Billing System
 *  Copyright © 2020 Alex Keda, for www.host-food.ru
 *}
{assign var=Theme value="Выписаны счета на продление оканчивающихся услуг" scope=global}
Здравствуйте, {$User.Name|default:'$User.Name'}!

В связи со скорым окончанием ранее заказанных вами услуг, автоматически, для вас были выписаны счета на продление (смотрите вложения).

Если вас не устраивают способы оплаты выписанных счетов, то их можно изменить кликнув на иконку "Изменить счёт", в разделе счетов на оплату биллинговой панели:
http://{$smarty.const.HOST_ID|default:'HOST_ID'}/Invoices

Отключить автоматическую выписку счетов, или, сменить метод оплаты для автоматически создаваемых счетов вы можете в биллинговой панели, раздел:
"Мои настройки" -> "Мои настройки"

