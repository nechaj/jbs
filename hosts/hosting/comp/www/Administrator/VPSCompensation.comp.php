<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
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
$VPSOrderID = (integer) @$Args['VPSOrderID'];
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
if($VPSOrderID){
	#-------------------------------------------------------------------------------
	$VPSOrder = DB_Select('VPSOrdersOwners',Array('ID','Login','Domain','StatusID'),Array('UNIQ','ID'=>$VPSOrderID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($VPSOrder)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return new gException('VPS_ORDER_NOT_FOUND','Заказ на виртуальный сервер не найден');
	case 'array':
		#-------------------------------------------------------------------------------
		if($VPSOrder['StatusID'] != 'Active')
			return new gException('VPS_ORDER_NOT_ACTIVE','Заказ виртуального сервера не активен');
		#-------------------------------------------------------------------------------
		$Table[] = Array('Заказ виртуального сервера',SPrintF('%s (%s)',$VPSOrder['Login'],$VPSOrder['Domain']));
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load(
				'Form/Input',
				Array(
					'type'  => 'hidden',
					'name'  => 'VPSOrderID',
					'value' => $VPSOrder['ID']
					)
				);
		#-------------------------------------------------------------------------------
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Form->AddChild($Comp);
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Servers = DB_Select('Servers',Array('ID','Address'),Array('Where'=>'(SELECT `ServiceID` FROM `ServersGroups` WHERE `ServersGroups`.`ID` = `Servers`.`ServersGroupID`) = 30000','SortOn'=>'Address'));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Servers)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return new gException('SERVERS_NOT_FOUND','Серверы VPS не настроены');
	case 'array':
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
		$Table[] = Array('Сервер VPS',$Comp);
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'type'  => 'text',
			'name'  => 'DaysReserved',
			'size'  => 5,
			'value' => 10
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Дней компенсации',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'type'    => 'button',
			'onclick' => "ShowConfirm('Подверждаете выполнение операции?','OrderCompensation(\'/Administrator/API/VPSCompensation\');')",
			'value'   => 'Компенсировать'
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
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

?>
