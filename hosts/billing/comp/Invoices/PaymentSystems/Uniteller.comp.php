<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda (for www.host-food.ru) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('PaymentSystemID','InvoiceID','Summ');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Invoices']['PaymentSystems']['Uniteller'];
#-------------------------------------------------------------------------------
$Send = $Settings['Send'];
#-------------------------------------------------------------------------------
$Send['Subtotal_P'] = Round($Summ/$Settings['Course'],2);
#-------------------------------------------------------------------------------
$Send['Order_IDP'] = $InvoiceID;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Invoice/Number',$InvoiceID);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
$Send['Comment'] .= SPrintF('%s, %s (%s)',$Comp,Translit($__USER['Name']),$__USER['Email']);
#-------------------------------------------------------------------------------
$Protocol = (@$_SERVER['SERVER_PORT'] != 80?'https':'http');
#-------------------------------------------------------------------------------
$Send['URL_RETURN']	= SPrintF('%s://%s/Invoices',$Protocol,HOST_ID);
$Send['URL_RETURN_OK']	= SPrintF('%s://%s/Invoices',$Protocol,HOST_ID);
$Send['URL_RETURN_NO']	= SPrintF('%s://%s/Invoices?Error=yes',$Protocol,HOST_ID);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# а ещё в люобй форме есть CSRF
#$Send['CSRF'] = $GLOBALS['CSRF'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Array = Array(
		$Send['Shop_IDP'],
		$Send['Order_IDP'],
		$Send['Subtotal_P'],
		'',			/* MeanType — платёжная система банковской карты. */
		'',			/* EMoneyType — тип электронной валюты. */
		'',			/* Lifetime — время жизни формы оплаты в секундах. */
		'',			/* Customer_IDP — идентификатор покупателя, используемый некоторыми интернет-магазинами. */
		'',			/* Card_IDP — идентификатор зарегистрированной карты. */
		'',			/* IData — «длинная запись». */
		'',			/* PT_Code — тип платежа. */
		$Settings['Hash']
		);
#-------------------------------------------------------------------------------
$Hash = Array();
#-------------------------------------------------------------------------------
foreach($Array as $Value)
	$Hash[] = Md5($Value);
#-------------------------------------------------------------------------------
$Send['Signature'] = StrToUpper(Md5(Implode("&",$Hash)));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Send;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
