<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Invoices']['PaymentSystems']['QIWI'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверяем, активна ли платёжная система. если нет - следующая проверка через час
if(!$Settings['IsActive'])
	return TRUE;
#-------------------------------------------------------------------------------
$NumInvoices = 0;
$NumPayed = 0;
#-------------------------------------------------------------------------------
# грузим либы
if(Is_Error(System_Load('libs/HTTP.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# исключаем отменённые счета, если число минут не кратно 5 (исполняться это будет редко - раз в час, примерно)
$Where = Array("`PaymentSystemID` = 'QIWI'","`StatusID` != 'Payed'");
#-------------------------------------------------------------------------------
# выбираем счета QIWI, статус отличается от "оплачен"
$Items = DB_Select('Invoices',Array('ID','Summ','StatusID'),Array('SortOn'=>'CreateDate','IsDesc'=>TRUE,'Where'=>$Where));
#-------------------------------------------------------------------------------
switch(ValueOf($Items)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	# No more...
	break;
case 'array':
	#-------------------------------------------------------------------------------
	$bill_list = "";
	#-------------------------------------------------------------------------------
	foreach($Items as $Item){
		#-------------------------------------------------------------------------
		#Debug("[comp/Tasks/GC/QIWI]: processing invoice #" . $Item['ID']);
		$bill_list .= "\t\t" . SPrintF('<bill txn-id="%s"/>', $Item['ID']) . "\n";
		#-------------------------------------------------------------------------
		$NumInvoices++;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	# create request
	$Result = TemplateReplace('Tasks.QIWI',Array('Settings'=>$Settings,'bill_list'=>$bill_list),FALSE);
	#Debug(SPrintF('[comp/Tasks/GC/QIWI]: Result = %s',print_r($Result,true)));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# calculate encrypt key
	$PasswordMD5 = md5($Settings['Hash'], true);
	#-------------------------------------------------------------------------------
	$salt = md5($Settings['Send']['from'] . bin2hex($PasswordMD5), true);
	#-------------------------------------------------------------------------------
	$key = Str_Pad($PasswordMD5, 24, '\0');
	#-------------------------------------------------------------------------------
	# XOR calculating
	for ($i = 8; $i < 24; $i++){
		#-------------------------------------------------------------------------------
		if($i >= 16){
			#-------------------------------------------------------------------------------
			$key[$i] = $salt[$i-8];
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			$key[$i] = $key[$i] ^ $salt[$i-8];
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	# create message
	$n = 8 - StrLen($Result) % 8;
	#-------------------------------------------------------------------------------
	$pad = Str_Pad($Result, StrLen($Result) + $n, ' ');
	#-------------------------------------------------------------------------------
	# crypt message
	$crypted = mcrypt_encrypt(MCRYPT_3DES, $key, $pad, MCRYPT_MODE_ECB, "\0\0\0\0\0\0\0\0");
	#-------------------------------------------------------------------------------
	$Result = "qiwi" . Str_Pad($Settings['Send']['from'], 10, "0", STR_PAD_LEFT) . "\n";
	#-------------------------------------------------------------------------------
	$Result .= base64_encode($crypted);
	#-------------------------------------------------------------------------------
	# send message to QIWI server
	$HTTP = Array('Protocol'=>($Settings['Send']['UseSSL'])?'ssl':'tcp','Port'=>($Settings['Send']['UseSSL'])?'443':'80','Address'=>'ishop.qiwi.ru','Host'=>'ishop.qiwi.ru','IsLogging'=>$Settings['IsLogging']);
	#-------------------------------------------------------------------------------
	$Send = HTTP_Send('/xml',$HTTP,Array(),$Result,Array('Content-type: text/xml; encoding=utf-8'));
	#-------------------------------------------------------------------------------
	if(Is_Error($Send))
		return TRUE;
	#-------------------------------------------------------------------------------
	# parse XML
	if(Mb_StrLen($Send['Body']) == 0){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/comp/Tasks/GC/QIWI]: Body zero size'));
		#-------------------------------------------------------------------------------
		return TRUE;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Send['Body']);
	if(Is_Exception($XML))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Response = Trim($Send['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
	$XML = $XML->ToArray('bill',Array('id','status','error','sum'));
	#-------------------------------------------------------------------------------
	#Debug(SprintF('[comp/Tasks/GC/QIWI]: XML = %s',print_r($XML,true)));
	#-------------------------------------------------------------------------------
	# проверяем код возврата, что вернул сервис... если он есть
	if(!IsSet($XML['response']['result-code']) || $XML['response']['result-code'] != 0)
		return TRUE;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$BillList = $XML['response']['bills-list'];
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($BillList) as $BillID){
		#-------------------------------------------------------------------------------
		#Debug(SprintF('[comp/Tasks/GC/QIWI]: bill = %s',print_r($BillList[$BillID],true)));
		#-------------------------------------------------------------------------------
		if(IsSet($BillList[$BillID]['id']) && $BillList[$BillID]['status'] == 60){
			#-------------------------------------------------------------------------------
			$Invoice = DB_Select('Invoices',Array('ID','Summ'),Array('UNIQ','ID'=>$BillList[$BillID]['id']));
			switch(ValueOf($Invoice)){
			case 'error':
				return ERROR | @Trigger_Error(500);
			case 'exception':
				return ERROR | @Trigger_Error(400);
			case 'array':
				#-------------------------------------------------------------------------------
				$Summ = $Invoice['Summ'];
				#-------------------------------------------------------------------------------
				$InvoiceID = $Invoice['ID'];
				#-------------------------------------------------------------------------------
				if($Summ == $BillList[$BillID]['sum']){
					#-------------------------------------------------------------------------------
					#Debug("[comp/Tasks/GC/QIWI]: summ compare success for #" . $Invoice['ID']);
					#-------------------------------------------------------------------------------
					$Comp = Comp_Load('Users/Init',100);
					if(Is_Error($Comp))
						return ERROR | @Trigger_Error(500);
					#-------------------------------------------------------------------------------
					$Comp = Comp_Load('www/API/StatusSet',
									Array(	'ModeID'	=> 'Invoices',
										'IsNotNotify'	=> TRUE,
										'IsNoTrigger'	=> FALSE,
										'StatusID'	=> 'Payed',
										'RowsIDs'	=> $InvoiceID,
										'Comment'	=> 'Автоматическое зачисление [cron]'
									)
								);
					#-------------------------------------------------------------------------------
					switch(ValueOf($Comp)){
					case 'error':
						return ERROR | @Trigger_Error(500);
					case 'exception':
						return ERROR | @Trigger_Error(400);
					case 'array':
						#Debug("[comp/Tasks/GC/QIWI]: Payment #$InvoiceID success");
						break;
					default:
						return ERROR | @Trigger_Error(101);
					}
					#-------------------------------------------------------------------------------
				}else{
					#-------------------------------------------------------------------------------
					Debug(SPrintF('[comp/Tasks/GC/QIWI]: Incorrect summ for #%s, billing have = %s; QIWI return = %s',$InvoiceID,$Summ,$BillList[$BillID]['sum']));
					#-------------------------------------------------------------------------------
				}
				#-------------------------------------------------------------------------------
				break;
				#-------------------------------------------------------------------------------
			default:
			      return ERROR | @Trigger_Error(101);
			}
			#-------------------------------------------------------------------------------
			$NumPayed++;
			#-------------------------------------------------------------------------------
		}elseif($BillList[$BillID]['status'] == 150){
			#-------------------------------------------------------------------------------
			# проверяем, не отменён ли он уже в биллинге.
			# иначе будет каждый запуск его отменять, и так 40 дней
			foreach($Items as $Item){
				#-------------------------------------------------------------------------------
				if($Item['ID'] == $BillList[$BillID]['id'] && $Item['StatusID'] != 'Rejected'){
					#-------------------------------------------------------------------------------
					$Comp = Comp_Load('Users/Init',100);
					if(Is_Error($Comp))
						return ERROR | @Trigger_Error(500);
					#-------------------------------------------------------------------------------
					$Comp = Comp_Load('www/API/StatusSet',
								Array(  'ModeID'        => 'Invoices',
									'IsNotNotify'   => TRUE,
									'IsNoTrigger'   => FALSE,
									'StatusID'      => 'Rejected',
									'RowsIDs'       => $BillList[$BillID]['id'],
									'Comment'       => 'Клиент отказался от оплаты счёта в терминале'
									)
								);
					#-------------------------------------------------------------------------------
					switch(ValueOf($Comp)){
					case 'error':
						return ERROR | @Trigger_Error(500);
					case 'exception':
						return ERROR | @Trigger_Error(400);
					case 'array':
						Debug(SPrintF('[comp/Tasks/GC/QIWI]: Payment #%s canceled, using terminal',$BillList[$BillID]['id']));
						break;
					default:
						return ERROR | @Trigger_Error(101);
					}
					#-------------------------------------------------------------------------------
				}
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($NumInvoices > 0){
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'] = Array('Invoices'=>Array($NumInvoices));

	#-------------------------------------------------------------------------------
	if($NumPayed > 0)
		$GLOBALS['TaskReturnInfo']['Payed'] = Array($NumPayed);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# functions, from https://ishop.qiwi.ru/docs/qiwi-php-xml/simple_crypt.php
# deleted, because from cron it redeclared on second cron run


?>
