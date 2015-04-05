<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$Search = (string) @$Args['Search'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Search = DB_Escape($Search);
#-------------------------------------------------------------------------------
$Where = SPrintF("`Email` LIKE '%%%s%%' OR `Name` LIKE '%%%s%%'",$Search,$Search);
#-------------------------------------------------------------------------------
$Users = DB_Select('Users',Array('ID','Email','Name'),Array('Limit'=>Array('Start'=>0,'Length'=>15),'Where'=>$Where));
#-------------------------------------------------------------------------------
switch(ValueOf($Users)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return new gException('NO_RESULT','Пользователи не найдены');
  case 'array':
    #---------------------------------------------------------------------------
    $Result = Array();
    #---------------------------------------------------------------------------
    foreach($Users as $User)
      $Result[UniqID('ID')] = Array('Value'=>$User['ID'],'Label'=>SPrintF('%s (%s)',$User['Email'],$User['Name']));
    #---------------------------------------------------------------------------
    return Array('Options'=>$Result,'Status'=>'Ok');
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>