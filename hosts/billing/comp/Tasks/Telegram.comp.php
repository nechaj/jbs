<?php
#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','Address','Message','Attribs');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
// возможно, параметры не заданы/требуется немедленная отправка - время не опредлеяем
if(!IsSet($Attribs['IsImmediately']) || !$Attribs['IsImmediately']){
	#-------------------------------------------------------------------------------
	// проверяем, можно ли отправлять в заданное время
	$TransferTime = Comp_Load('Formats/Task/TransferTime',$Attribs['UserID'],$Address,'Telegram',$Attribs['TimeBegin'],$Attribs['TimeEnd']);
	#-------------------------------------------------------------------------------
	switch(ValueOf($TransferTime)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'integer':
		return $TransferTime;
	case 'false':
		break;
	default:
		return ERROR | @Trigger_Error(100);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Tasks/Telegram]: отправка Telegram сообщения для (%s)', $Address));
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = $Address;
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('libs/HTTP.php','libs/Telegram.php','libs/Server.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Settings = SelectServerSettingsByTemplate('Telegram');
#-------------------------------------------------------------------------------
switch(ValueOf($Settings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'] = 'server with template: Telegram, params: IsActive, IsDefault not found';
	#-------------------------------------------------------------------------------
	if(IsSet($GLOBALS['IsCron']))
		return 3600;
	#-------------------------------------------------------------------------------
	return $Settings;
	#-------------------------------------------------------------------------------
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!TgSendMessage($Settings,$Attribs['ExternalID'],$Message))
	return 3600;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# достаём данные юзера которому идёт письмо
$User = DB_Select('Users',Array('ID','Params'),Array('UNIQ','ID'=>$Attribs['UserID']));
if(!Is_Array($User))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($User['Params']['Settings']['SendEdeskFilesToTelegram'] == "Yes")
	if(!TgSendFile($Settings,$Attribs['ExternalID'],$Attribs['Attachments']))
		Debug(SPrintF('[comp/Tasks/Telegram]: не удалось отправить файл в Telegram '));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
if(!$Config['Notifies']['Methods']['Telegram']['IsEvent'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Event = Comp_Load('Events/EventInsert', Array('UserID'=>$Attribs['UserID'],'Text'=>SPrintF('Сообщение для (%s) через службу Telegram отправлено', $Address)));
if(!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>