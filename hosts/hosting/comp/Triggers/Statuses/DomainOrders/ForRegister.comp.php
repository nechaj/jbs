<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('DomainOrder');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$IsAdd = Comp_Load('www/Administrator/API/TaskEdit',Array('UserID'=>$DomainOrder['UserID'],'TypeID'=>'DomainRegister','Params'=>Array($DomainOrder['ID'])));
#-------------------------------------------------------------------------------
switch(ValueOf($IsAdd)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return ERROR | @Trigger_Error(400);
  case 'array':
    return TRUE;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
