<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('SystemID','InvoiceID','Summ');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Invoices']['PaymentSystems']['EasyPay'];
#-------------------------------------------------------------------------------
$Send = $Settings['Send'];
#-------------------------------------------------------------------------------
$Send['EP_OrderNo'] = $InvoiceID;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Send['EP_Sum'] = Floor($Summ/$Settings['Course']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Invoice/Number',$InvoiceID);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
$Send['EP_OrderInfo'] .= SPrintF('%s, %s (%s)',$Comp,Translit($__USER['Name']),$__USER['Email']);
#-------------------------------------------------------------------------------
$Send['EP_Comment'] = SPrintF('#%s, %s',$Comp,$__USER['Email']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Hash = Array(
	#-----------------------------------------------------------------------------
	$Send['EP_MerNo'],
	$Settings['Hash'],
	$Send['EP_OrderNo'],
	$Send['EP_Sum']
);
#-------------------------------------------------------------------------------
$Send['EP_Hash'] = md5(Implode('',$Hash));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# URL для возврата
$Protocol = (@$_SERVER['SERVER_PORT'] != 80?'https':'http');
#-------------------------------------------------------------------------------
$Send['EP_Cancel_URL']	= SPrintF('%s://%s/Invoices?Error=yes',$Protocol,HOST_ID);
$Send['EP_Success_URL']	= SPrintF('%s://%s/Invoices',$Protocol,HOST_ID);
# кодировка
$Send['EP_Encoding']	= 'utf-8';
#-------------------------------------------------------------------------------
return $Send;
#-------------------------------------------------------------------------------

?>