
INSERT INTO `Clauses` (`AuthorID`, `EditorID`, `Partition`, `Title`, `IsProtected`, `IsXML`, `IsDOM`, `IsPublish`, `Text`) VALUES
(100, 100, 'CreateTicket/LOCK_OVERLIMITS', 'Аккаунт заблокирован за превышение использования CPU', 'no', 'yes', 'yes', 'yes', '<NOBODY>\n <P>\n Уведомляем Вас о том, что ваш аккаунт %Login%, паркованный домен %Domain%, превысил использование процессорного времени, определённое вашим тарифом &quot;%Scheme%&quot;. Превышения были систематические, на предыдущие уведомления по данному поводу вы не реагировали, поэтому аккаунт заблокирован.<BR /><BR />\n Среднее использование за %PeriodToLock% дней составило: %BUsage%%, при лимите тарифного плана: %QuotaCPU%%.<BR /><BR />\n Подробную статистику использования ресурсов, вы можете узнать в панели управления хостингом:<BR />\n %HostingOrder.Url%\n</P>\n</NOBODY>\n');


