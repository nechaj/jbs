<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Args');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = IsSet($Args)?$Args:Args();
#-------------------------------------------------------------------------------
$RowsIDs	= (array)  @$Args['RowsIDs'];
$ServiceID	= (string) @$Args['ServiceID'];
$UseBalance	= (boolean)@$Args['UseBalance'];
$ItemsPay	= (integer)@$Args['ItemsPay'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$ServiceID)
	return ERROR | @Trigger_Error('[comp/www/OrdersPay]: Не задан сервис, невозможно определить что именно оплачивается');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Orders = Array();
#-------------------------------------------------------------------------------
foreach($RowsIDs as $RowsID)
	if(!In_Array(IntVal($RowsID),$Orders))
		$Orders[] = IntVal($RowsID);
#-------------------------------------------------------------------------------
if(SizeOf($Orders) < 1)
	return new gException('NO_SELECTED_ORDERS','Необходимо выбрать оплачиваемые заказы');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Service = DB_Select('Services',Array('ID','Code','Item','ConsiderTypeID'),Array('UNIQ','ID'=>$ServiceID));
switch(ValueOf($Service)){
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
$Orders = DB_Select(SPrintF('%sOrdersOwners',$Service['Code']),Array('*'),Array('Where'=>Array('`UserID` = @local.__USER_ID',SPrintF('`ID` IN (%s)',Implode(",",$Orders)))));
switch(ValueOf($Orders)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('NO_ORDERS_FOR_PAY','Отсутствуют заказы которые можно оплатить');
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Path = SPrintF('www/API/%sOrderPay',$Service['Code']);
#-------------------------------------------------------------------------------
if(Is_Error(System_Element(SPrintF('comp/%s.comp.php',$Path)))){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/API/OrdersPay]: API для заказа сервиса не найдено: %s',$Path));
	#-------------------------------------------------------------------------------
	return new gException('NO_API_SERVICE_ORDER','Не удалось определить API для заказа сервиса');
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Count = 0;
foreach($Orders as $Order){
	#-------------------------------------------------------------------------------
	$OrderPay = Comp_Load($Path,Array(SprintF('%sOrderID',$Service['Code'])=>$Order['ID'],'DaysPay'=>$ItemsPay,'IsUseBasket'=>(!$UseBalance),'IsNoBasket'=>$UseBalance));
	#-------------------------------------------------------------------------------
	switch(ValueOf($OrderPay)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		break;
	case 'array':
		#-------------------------------------------------------------------------------
		$Count++;
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Count = DB_Count('BasketOwners',Array('Where'=>'`UserID` = @local.__USER_ID'));
if(Is_Error($Count))
	return ERROR | Trigger_Error(500);
#-----------------------------------------------------------------------------
if($Count){
	#-------------------------------------------------------------------------------
	return Array('Status'=>'Url','Location'=>'/Basket');
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	return Array('Status'=>'Ok');
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------

?>
