<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Args = Args();
#-------------------------------------------------------------------------------
$DSOrderID = (integer) @$Args['DSOrderID'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Window')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/Administrator/OrderCompensation.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Компенсация времени');
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'OrderCompensationForm','onsubmit'=>'return false;'));
#-------------------------------------------------------------------------------
$Table = Array();
#-------------------------------------------------------------------------------
if($DSOrderID){
	#-------------------------------------------------------------------------------
	$DSOrder = DB_Select('DSOrdersOwners',Array('ID','OrderID','IP','ExtraIP','StatusID'),Array('UNIQ','ID'=>$DSOrderID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($DSOrder)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return new gException('DS_ORDER_NOT_FOUND','Заказ на выделенный сервер не найден');
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	if($DSOrder['StatusID'] != 'Active')
		return new gException('DS_ORDER_NOT_ACTIVE','Заказ выделенного сервера не активен');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Formats/Order/Number',$DSOrder['OrderID']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Номер заказа',SPrintF('#%s',$DSOrder['OrderID']));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table[] = Array('IP',SPrintF('%s',$DSOrder['IP']));
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('type'=>'hidden','name'=>'DSOrderID','value'=>$DSOrder['ID']));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Servers = DB_Select('Servers',Array('ID','Address'),Array('Where'=>'(SELECT `ServiceID` FROM `ServersGroups` WHERE `ServersGroups`.`ID` = `Servers`.`ServersGroupID`) = 40000','SortOn'=>'Address'));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Servers)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return new gException('SERVERS_NOT_FOUND','Управляющие сервера не настроены');
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	$Options = Array();
	#-------------------------------------------------------------------------------
	foreach($Servers as $Server)
		$Options[$Server['ID']] = $Server['Address'];
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Select',Array('name'=>'ServerID','style'=>'width: 100%;'),$Options);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Управляющий сервер',$Comp);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'text','name'=> 'DaysReserved','value'=>10));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Дней компенсации',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>"ShowConfirm('Подверждаете выполнение операции?','OrderCompensation(\'/Administrator/API/DSCompensation\');')",'value'=>'Компенсировать'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form->AddChild($Comp);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>