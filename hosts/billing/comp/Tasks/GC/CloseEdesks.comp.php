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
$Settings = $Config['Tasks']['Types']['GC']['CloseEdesksSettings'];
#-------------------------------------------------------------------------------
if(!$Settings['IsActive'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Where = Array(
		'`StatusID` != "Closed"', '`Flags` != "DenyClose"',
		SPrintF('`StatusDate` < %u',(Time() - $Settings['CloseEdesksDays'] * 24 * 60 * 60)),
		SPrintF('`SeenByUser` < %u',(Time() - $Settings['CloseEdesksDays'] * 24 * 60 * 60))
		);
#-------------------------------------------------------------------------------
$Edesks = DB_Select('Edesks',Array('ID','UserID'),Array('SortOn'=>'CreateDate', 'IsDesc'=>TRUE, 'Where'=>$Where));
#-------------------------------------------------------------------------------
switch(ValueOf($Edesks)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return TRUE;
case 'array':
	#-------------------------------------------------------------------------------
	foreach($Edesks as $Edesk){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Tasks/GC/CloseEdesks]: Закрытие тикета #%d.',$Edesk['ID']));
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>'Edesks','StatusID'=>'Closed','RowsIDs'=>$Edesk['ID'],'Comment'=>SPrintF('Автоматическое закрытие, >%d дней неактивности',$Settings['CloseEdesksDays'])));
		#-------------------------------------------------------------------------------
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------


?>
