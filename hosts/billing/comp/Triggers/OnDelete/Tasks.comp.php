<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if($Task['UserID'] == 1){
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load('Formats/Task/Number',$Task['ID']);
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  return new gException('TASK_CAN_NOT_DELETED',SPrintF('Задание №%s является системным и не может быть удалено',$Comp));
}
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------

?>