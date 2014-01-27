<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Params');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Tasks']['Types']['GC']['CleanTablesSettings'];
#-------------------------------------------------------------------------------
if(!$Settings['IsActive'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# зачищаем таблицу задач
$Where = Array(
		SPrintF('`ExecuteDate` < UNIX_TIMESTAMP() - %u',$Settings['TableTasksStoryPeriod'] * 24 * 3600),'`UserID` != 1',
		"`TypeID` != 'Dispatch'"
		);
$IsDelete = DB_Delete('Tasks',Array('Where'=>$Where));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# костыль к рассыльщику SMS
$Where = Array(
		'`CreateDate` < UNIX_TIMESTAMP() - 24 * 3600',
		"`TypeID` = 'SMS'"
		);
$IsDelete = DB_Delete('Tasks',Array('Where'=>$Where));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);
#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# зачищаем таблицу ServersUpTime
$Where = SPrintF('`TestDate` < UNIX_TIMESTAMP() - %u',$Settings['TableServersUpTimeStoryPeriod'] * 24 * 3600);
$IsDelete = DB_Delete('ServersUpTime',Array('Where'=>$Where));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);
#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# зачищаем таблицу RequestLog
$Where = SPrintF('`CreateDate` < UNIX_TIMESTAMP() - %u',$Settings['TableRequestLogStoryPeriod'] * 24 * 3600);
$IsDelete = DB_Delete('RequestLog',Array('Where'=>$Where));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);
#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# added by lissyara, 2011-12-27 in 14:09 MSK, for JBS-232
# проставляем тикеты как оповещённые, если больше недели прошло
$IsUpdate = DB_Update('EdesksMessages',Array('IsNotify'=>'yes'),Array('Where'=>SPrintF('`CreateDate` < %u',(Time() - 7*24*3600))));
if(Is_Error($IsUpdate))
	return ERROR | @Trigger_Error(500);
#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# added by lissyara 2012-09-28 in 13:54 MSK, for JBS-377
$Where = '(SELECT `ID` FROM `Users` WHERE `Events`.`UserID`=`Users`.`ID`) IS NULL';
$IsDelete = DB_Delete('Events',Array('Where'=>$Where));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);
#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
return TRUE;
#--------------------------------------------------------------------------------

?>