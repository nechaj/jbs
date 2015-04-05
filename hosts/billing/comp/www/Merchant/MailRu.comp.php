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
$ArgsIDs = Array('type','status','item_number','issuer_id','serial','auth_method','signature');
#-------------------------------------------------------------------------------
foreach($ArgsIDs as $ArgID)
  $Args[$ArgID] = @$Args[$ArgID];
#-------------------------------------------------------------------------------
$OrderID = base64_decode($Args['issuer_id']);
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Invoices']['PaymentSystems']['MailRu'];
#-------------------------------------------------------------------------------
$sha = Array(
  #-----------------------------------------------------------------------------
  $Args['auth_method'],
  $Args['issuer_id'],
  $Args['item_number'],
  $Args['serial'],
  $Args['status'],
  $Args['type'],
  $Settings['Hash']
);
#-------------------------------------------------------------------------------
if(sha1(Implode('',$sha)) != $Args['signature'])
  return ERROR | @Trigger_Error('[comp/www/Merchant/MailRu]: проверка подлинности завершилась не удачей');
#-------------------------------------------------------------------------------
$Invoice = DB_Select('Invoices',Array('ID','Summ'),Array('UNIQ','ID'=>$OrderID));
#-------------------------------------------------------------------------------
switch(ValueOf($Invoice)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return ERROR | @Trigger_Error(400);
  case 'array':
    #---------------------------------------------------------------------------
    $InvoiceID = $Invoice['ID'];
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Users/Init',100);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
      #-----------------------------------------------------------------------
      $Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>'Invoices','StatusID'=>'Payed','RowsIDs'=>$InvoiceID,'Comment'=>'Автоматическое зачисление'));
      #-----------------------------------------------------------------------
      switch(ValueOf($Comp)){
        case 'error':
          return ERROR | @Trigger_Error(500);
        case 'exception':
          return ERROR | @Trigger_Error(400);
        case 'array':
          #-------------------------------------------------------------------------------
          $Result = "item_number=%s\nstatus=ACCEPTED\n";
          #-------------------------------------------------------------------------------
	  return SPrintF(trim($Result),$Args['item_number']);
          default:
            return ERROR | @Trigger_Error(101);
        }
      default:
        return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>