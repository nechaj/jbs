<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
if(!Count($Args))
	return 'No args...';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# yandex protocol version = commonHTTP-3.0
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#[22:57:17.44][44122] [Security module]: (notification_type) = (p2p-incoming)
#[22:57:17.44][44122] [Security module]: (amount) = (899.44)
#[22:57:17.44][44122] [Security module]: (datetime) = (2018-09-15T19:57:17Z)
#[22:57:17.44][44122] [Security module]: (codepro) = (false)
#[22:57:17.44][44122] [Security module]: (sender) = (41001000040)
#[22:57:17.44][44122] [Security module]: (sha1_hash) = (7a73cb4f07419a6d79291ac883bea507debe565c)
#[22:57:17.44][44122] [Security module]: (test_notification) = (true)
#[22:57:17.44][44122] [Security module]: (operation_label) = (EMPTY)
#[22:57:17.44][44122] [Security module]: (operation_id) = (test-notification)
#[22:57:17.44][44122] [Security module]: (currency) = (643)
#[22:57:17.44][44122] [Security module]: (label) = (EMPTY)

$ArgsIDs = Array(
		'notification_type',
		'operation_id',
		'amount',
		'withdraw_amount',
		'currency',
		'datetime',
		'sender',
		'codepro',
		'label',
		'sha1_hash',
		'requestDatetime',
		'action',
		'md5',
		'shopId',
		'orderNumber',
		'customerNumber',
		'orderCreatedDatetime',
		'orderSumAmount',
		'orderSumCurrencyPaycash',
		'orderSumBankPaycash',
		'shopSumAmount',
		'shopSumCurrencyPaycash',
		'shopSumBankPaycash'
		);
#-------------------------------------------------------------------------------
foreach($ArgsIDs as $ArgID)
	$Args[$ArgID] = @$Args[$ArgID];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(StrLen($Args['sha1_hash']) > 1){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/Merchant/Yandex]: физики: лопатник'));
	#-------------------------------------------------------------------------------
	$Settings = $Config['Invoices']['PaymentSystems']['Yandex.p2p'];
	#-------------------------------------------------------------------------------
	$Sha1 = Array(
			$Args['notification_type'],
			$Args['operation_id'],
			$Args['amount'],
			$Args['currency'],
			$Args['datetime'],
			$Args['sender'],
			$Args['codepro'],
			$Settings['Hash'],
			$Args['label'],
			);
	#-------------------------------------------------------------------------------
	if(Sha1(Implode('&',$Sha1)) != $Args['sha1_hash'])
		return ERROR | @Trigger_Error('[comp/www/Merchant/Yandex]: проверка подлинности завершилась не удачей');
	#-------------------------------------------------------------------------------
	$OrderID = $Args['label'];
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Invoice = DB_Select('Invoices',Array('ID','Summ'),Array('UNIQ','ID'=>$OrderID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Invoice)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	if(Round($Invoice['Summ']/$Settings['Course'],2) != $Args['withdraw_amount'])
		return ERROR | @Trigger_Error('[comp/Merchant/Yandex]: проверка суммы платежа завершилась неудачей');
	#-------------------------------------------------------------------------------
	$InvoiceID = $Invoice['ID'];
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Users/Init',100);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>'Invoices','StatusID'=>'Payed','RowsIDs'=>$InvoiceID,'Comment'=>'Автоматическое зачисление'));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Comp)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		#-------------------------------------------------------------------------------
		return TemplateReplace('www.Merchant.Yandex',Array('Args'=>$Args,'Date'=>$Date),FALSE);
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/Merchant/Yandex]:  юрики: яндекс-касса'));
	#-------------------------------------------------------------------------------
	$OrderID = $Args['orderNumber'];
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Settings = $Config['Invoices']['PaymentSystems']['Yandex'];
	#-------------------------------------------------------------------------------
	$Md5 = Array(
		$Args['action'],
		$Args['orderSumAmount'],
		$Args['orderSumCurrencyPaycash'],
		$Args['orderSumBankPaycash'],
		$Args['shopId'],
		$Args['invoiceId'],
		$Args['customerNumber'],
		$Settings['Hash']
	);
	#-------------------------------------------------------------------------------
	if(StrToUpper(Md5(Implode(';',$Md5))) != $Args['md5'])
		return ERROR | @Trigger_Error('[comp/www/Merchant/Yandex]: проверка подлинности завершилась не удачей');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Invoice = DB_Select('Invoices',Array('ID','Summ'),Array('UNIQ','ID'=>$OrderID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Invoice)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	if(Round($Invoice['Summ']/$Settings['Course'],2) != $Args['orderSumAmount'])
		return ERROR | @Trigger_Error('[comp/Merchant/Yandex]: проверка суммы платежа завершилась неудачей');
	#-------------------------------------------------------------------------------
	$InvoiceID = $Invoice['ID'];
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	switch($Args['action']){
	case 'checkOrder':
		#-------------------------------------------------------------------------------
		$Date = Date('c', Time());
		#-------------------------------------------------------------------------------
		return TemplateReplace('www.Merchant.Yandex',Array('Args'=>$Args,'Date'=>$Date),FALSE);
		#-------------------------------------------------------------------------------
	case 'paymentAviso':
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Users/Init',100);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>'Invoices','StatusID'=>'Payed','RowsIDs'=>$InvoiceID,'Comment'=>'Автоматическое зачисление'));
		#-------------------------------------------------------------------------------
		switch(ValueOf($Comp)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			return ERROR | @Trigger_Error(400);
		case 'array':
			#-------------------------------------------------------------------------------
			return TemplateReplace('www.Merchant.Yandex',Array('Args'=>$Args,'Date'=>$Date),FALSE);
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
