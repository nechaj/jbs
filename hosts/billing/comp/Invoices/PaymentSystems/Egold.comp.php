<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('SystemID','InvoiceID','Summ');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Invoices']['PaymentSystems']['Egold'];
#-------------------------------------------------------------------------------
$Send = $Settings['Send'];
#-------------------------------------------------------------------------------
$Send['PAYMENT_ID'] = $InvoiceID;
#-------------------------------------------------------------------------------
$Send['PAYMENT_AMOUNT'] = Round($Summ/$Settings['Course'],2);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Invoice/Number',$InvoiceID);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
$Send['SUGGESTED_MEMO'] .= SPrintF('%s, %s (%s)',$Comp,Translit($__USER['Name']),$__USER['Email']);
#-------------------------------------------------------------------------------
return $Send;
#-------------------------------------------------------------------------------

?>