<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','DSOrderID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('classes/DSServer.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DSOrder = DB_Select('DSOrdersOwners',Array('ID','UserID','IP','SchemeID','ServerID'),Array('UNIQ','ID'=>$DSOrderID));
#-------------------------------------------------------------------------------
switch(ValueOf($DSOrder)){
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
$DSServer = new DSServer();
#-------------------------------------------------------------------------------
$IsSelected = $DSServer->Select((integer)$DSOrder['ServerID']);
#-------------------------------------------------------------------------------
switch(ValueOf($IsSelected)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'true':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IsSuspend = $DSServer->Suspend($DSOrder['IP']);
#-------------------------------------------------------------------------------
switch(ValueOf($IsSuspend)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return $IsSuspend;
case 'true':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Event = Array(
		'UserID'	=> $DSOrder['UserID'],
		'PriorityID'	=> 'Billing',
		'Text'		=> SPrintF('Выключен арендованный сервер, IP адрес %s',$DSOrder['IP'])
		);
#-------------------------------------------------------------------------------
$Event = Comp_Load('Events/EventInsert',$Event);
if(!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
