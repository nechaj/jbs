<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('DNSmanagerOrder');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
switch($DNSmanagerOrder['StatusID']){
case 'Waiting':
	# No more...
	break;
case 'Deleted':
	#-------------------------------------------------------------------------------
	$Count = DB_Count('Tasks',Array('Where'=>Array(SPrintF('`UserID` = %u', $DNSmanagerOrder['UserID']),"`IsExecuted` = 'no'")));
	if(Is_Error($Count))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	if($Count)
		if(Time() - $DNSmanagerOrder['StatusDate'] < 600)
			return new gException('SYNCHRONIZATION_WAITING','Синхронизация по удалению заказа с сервера еще не произведена. Пожалуйста, повторите запрос через 10 минут.');
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return new gException('DELETE_DENIED','Удаление заказа не возможно');
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Services/Orders/OrdersHistory',Array('OrderID'=>$DNSmanagerOrder['OrderID'],'Parked'=>Explode(',',$DNSmanagerOrder['Parked'])));
switch(ValueOf($Comp)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return $Comp;
case 'array':
	return TRUE;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------

?>
