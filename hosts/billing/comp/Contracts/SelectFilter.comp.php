<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('LinkID');
/******************************************************************************/
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Links = &Links();
# Коллекция ссылок
$Template = &$Links[$LinkID];
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$ContractID = (integer)@$Args['ContractID'];
#-------------------------------------------------------------------------------
$Contracts = DB_Select('Contracts',Array('ID','TypeID','Customer'),Array('Where'=>SPrintF('`UserID` = %u AND `TypeID` != "NaturalPartner"',$GLOBALS['__USER']['ID']),'GroupBy'=>'ID'));
#-------------------------------------------------------------------------------
switch(ValueOf($Contracts)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return FALSE;
  case 'array':
    #---------------------------------------------------------------------------
    if(Count($Contracts) < 2)
      return FALSE;
    #---------------------------------------------------------------------------
    $Options = Array('Default'=>'Все договора');
    #---------------------------------------------------------------------------
    foreach($Contracts as $Contract){
      #-------------------------------------------------------------------------
      $Comp = Comp_Load('Formats/Contract/Number',$Contract['ID']);
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $Customer = SPrintF('%s %s',$Comp,$Contract['Customer']);
      #-------------------------------------------------------------------------
      if(Mb_StrLen($Customer) > 40)
        $Customer = SPrintF('%s...',Mb_SubStr($Customer,0,30));
      #-------------------------------------------------------------------------
      $Options[$Contract['ID']] = $Customer;
    }
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
if(!IsSet($Options[$ContractID]))
  $ContractID = 'Default';
#-------------------------------------------------------------------------------
if($ContractID != 'Default'){
  #-----------------------------------------------------------------------------
  $Where = &$Template['Source']['Adding']['Where'];
  #-----------------------------------------------------------------------------
  $Where[] = SPrintF('`ContractID` = %u',$ContractID);
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'ContractID','onchange'=>'TableSuperReload();'),$Options,$ContractID);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table = Array();
#-------------------------------------------------------------------------------
$Table[] = Array('По договору',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return $Comp;
#-------------------------------------------------------------------------------

?>
