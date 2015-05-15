<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda (for www.host-food.ru) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
if(!Count($Args))
	return "No args...\n";
#-------------------------------------------------------------------------------
$ArgsIDs = Array('Order_ID','Status','Signature');
#-------------------------------------------------------------------------------
foreach($ArgsIDs as $ArgID)
	$Args[$ArgID] = @$Args[$ArgID];
#-------------------------------------------------------------------------------
$OrderID = $Args['Order_ID'];
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Invoices']['PaymentSystems']['Uniteller'];
#-------------------------------------------------------------------------------
if($Args['Signature'] != StrToUpper(Md5($Args['Order_ID'] . $Args['Status'] . $Settings['Hash'])))
	return ERROR | @Trigger_Error('[comp/www/Merchant/Uniteller]: проверка подлинности завершилась не удачей');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Invoice = DB_Select('Invoices',Array('ID','Summ','ContractID'),Array('UNIQ','ID'=>$OrderID));
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
#-------------------------------------------------------------------------------
$InvoiceID = $Invoice['ID'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Users/Init',100);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
switch($Args['Status']){
case 'authorized':
	#-------------------------------------------------------------------------------
	$StatusID = 'Payed';
	#-------------------------------------------------------------------------------
	$Comment = 'Средства успешно заблокированы (выполнена авторизационная транзакция)';
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'paid':
	#-------------------------------------------------------------------------------
	$StatusID = 'Payed';
	#-------------------------------------------------------------------------------
	$Comment = 'Оплачен (выполнена финансовая транзакция или заказ оплачен в электронной платёжной системе)';
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'canceled':
	#-------------------------------------------------------------------------------
	$StatusID = 'Rejected';
	#-------------------------------------------------------------------------------
	$Comment = 'Отменён (выполнена транзакция разблокировки	средств или выполнена операция по возврату платежа после списания средств)';
	#-------------------------------------------------------------------------------
	#----------------------------------TRANSACTION----------------------------------
	if(Is_Error(DB_Transaction($TransactionID = UniqID('Merchant/Uniteller'))))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# ставим счёт как неоплаченный
	$IsUpdate = DB_Update('Invoices',Array('IsPosted'=>FALSE),Array('ID'=>$Invoice['ID']));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	# вычитаем сумму счёта из договора, на который счёт.
	$Contract = DB_Select('ContractsOwners','Balance',Array('UNIQ','ID'=>$Invoice['ContractID']));
	switch(ValueOf($Contract)){
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
	$After = $Contract['Balance'] - $Invoice['Summ'];
	#-------------------------------------------------------------------------------
	$IsUpdate = DB_Update('Contracts',Array('Balance'=>$After),Array('ID'=>$Invoice['ContractID']));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	# заносим запись в историю операций с контрактами
	$Comp = Comp_Load('Formats/Invoice/Number',$Invoice['ID']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$IPosting = Array(
			'ContractID' => $Invoice['ContractID'],
			'ServiceID'  => 2000,
			'Comment'    => SPrintF('Возврат средств условно зачисленных по счёту #%u',$Comp),
			'Before'     => $Contract['Balance'],
			'After'      => $After
			);
	#-------------------------------------------------------------------------------
	$PostingID = DB_Insert('Postings',$IPosting);
	if(Is_Error($PostingID))
		return ERROR | @Trigger_Error(500);

	break;
	#-------------------------------------------------------------------------------
default:
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Invoices/PaymentSystems/Uniteller]: статус "%s", счёт #%u проигнорирован',$Args['Status'],$Args['Order_ID']));
	return "OK\n";
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>'Invoices','StatusID'=>$StatusID,'RowsIDs'=>$InvoiceID,'Comment'=>$Comment));
#-------------------------------------------------------------------------------
switch(ValueOf($Comp)){
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
#-------------------------------------------------------------------------------
# если была транзакция - коммитим
if(IsSet($TransactionID))
	if(Is_Error(DB_Commit($TransactionID)))
		return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return "OK\n";
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
