{*
 *  Joonte Billing System
 *  Copyright © 2020 Alex Keda, for www.host-food.ru
 *}
{assign var=Theme value="Отчет за услуги" scope=global}
Здравствуйте, {$User.Name|default:'$User.Name'}!

Разрешите произвести перед Вами отчет за оказанные услуги.

Вся информация по текущим оказанным услугам может быть получена Вами загрузив акт выполненных работ с ипользованием прямой ссылки:

http://{$smarty.const.HOST_ID|default:'HOST_ID'}/WorksCompliteReportDownload?Email={$User.Email|default:'$User.Email'}&Password={$User.UniqID|default:'$User.UniqID'}&ContractID={$ContractID|default:'$ContractID'}&Month={$Month|default:'$Month'}

Оригиналы документов будут высланы Вам по почте или через электронный документооборот в самое ближайшее время.

