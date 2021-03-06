<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','ISPswOrderID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('libs/BillManager.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$ISPswOrder = DB_Select('ISPswOrdersOwners',Array('*','(SELECT `ProfileID` FROM `Contracts` WHERE `Contracts`.`ID` = `ISPswOrdersOwners`.`ContractID`) as `ProfileID`','(SELECT `ServerID` FROM `OrdersOwners` WHERE `OrdersOwners`.`ID` = `ISPswOrdersOwners`.`OrderID`) AS `ServerID`'),Array('UNIQ','ID'=>$ISPswOrderID));
#-------------------------------------------------------------------------------
switch(ValueOf($ISPswOrder)){
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
$Server = DB_Select('Servers','*',Array('UNIQ','ID'=>$ISPswOrder['ServerID']));
#-------------------------------------------------------------------------------
switch(ValueOf($Server)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	Debug(SPrintF('[comp/Tasks/ISPswCreate]: found server: Address = %s; ID = %s',$Server['Address'],$Server['ID']));
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$ISPswScheme = DB_Select('ISPswSchemes','*',Array('UNIQ','ID'=>$ISPswOrder['SchemeID']));
#-------------------------------------------------------------------------------
switch(ValueOf($ISPswScheme)){
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
$User = DB_Select('Users','*',Array('UNIQ','ID'=>$ISPswOrder['UserID']));
#-------------------------------------------------------------------------------
switch(ValueOf($User)) {
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
# create license comment
$ISPswScheme['LicComment'] = SPrintF('%s, order #%u%s',(($ISPswScheme['IsInternal'])?'INTERNAL':'EXTERNAL'),$ISPswOrder['OrderID'],(($ISPswScheme['IsInternal'])?'':SPrintF(', for %s',$User['Email'])));
#-------------------------------------------------------------------------------
# add IP
$ISPswScheme['IP'] = $ISPswOrder['IP'];
#-------------------------------------------------------------------------------
$License = BillManager_Find_Free_License($ISPswScheme);
if(Is_Error($License))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($License){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/ISPswCreate]: found free license, elid = %s',$License['elid']));
	#-------------------------------------------------------------------------------
	$ISPswScheme['elid'] = $License['elid'];
	#-------------------------------------------------------------------------------
	$ISPswScheme['LicenseID'] = $License['LicenseID'];
	#-------------------------------------------------------------------------------
	# меняем IP лицензии
	$Change_IP = BillManager_Change_IP($Server,$ISPswScheme);
	#-------------------------------------------------------------------------------
	if(Is_Error($Change_IP))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$IUpdate = Array('StatusDate'=>Time(),'ip_change_date'=>Time(),'IsUsed'=>'yes','ip'=>$ISPswScheme['IP']);
	#-------------------------------------------------------------------------------
	$IUpdate['IsInternal'] = (($ISPswScheme['IsInternal'])?TRUE:FALSE);
	#-------------------------------------------------------------------------------
	$IsUpdate = DB_Update('ISPswLicenses',$IUpdate,Array('ID'=>$License['LicenseID']));
	#-------------------------------------------------------------------------------
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	# разблокируем
	if(!BillManager_UnLock($Server,$ISPswScheme))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	# всё путём, лицензия создана
	$IsCreate = $License;
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	# свободная лицензия не найдена, надо заказывать
	$IsCreate = BillManager_Create($Server,$ISPswScheme);
	if(Is_Error($IsCreate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
switch(ValueOf($IsCreate)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return $IsCreate;
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# ставим статус
$Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>'ISPswOrders','StatusID'=>'Active','RowsIDs'=>$ISPswOrder['ID'],'Comment'=>'ПО заказано'));
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
$IsUpdate = DB_Update('ISPswOrders',Array('LicenseID'=>$IsCreate['LicenseID']),Array('ID'=>$ISPswOrder['ID']));
if(Is_Error($IsUpdate))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Event = Array('UserID'=>$ISPswOrder['UserID'],'PriorityID'=>'Hosting','Text'=>SPrintF('Заказ ПО ISPsystem осуществлён, тарифный план (%s), идентификатор пакета (%s)',$ISPswScheme['Name'],$ISPswScheme['PackageID']));
#-------------------------------------------------------------------------------
$Event = Comp_Load('Events/EventInsert',$Event);
if(!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = Array($ISPswOrder['IP'],$ISPswScheme['Name']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
