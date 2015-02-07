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
$DSServersGroupID = (integer) @$Args['DSServersGroupID'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($DSServersGroupID){
	#-------------------------------------------------------------------------------
	$DSServersGroup = DB_Select('DSServersGroups','*',Array('UNIQ','ID'=>$DSServersGroupID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($DSServersGroup)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		# No more...
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$DSServersGroup = Array('Name'=>'По умолчанию','FunctionID'=>'NotDefined','Comment'=>'Москва, M9','SystemID'=>'NullSystem','SortID'=>10);
	#-------------------------------------------------------------------------------
}
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
$Title = ($DSServersGroupID?SPrintF('Редактирование группы "%s"',$DSServersGroup['Name']):'Добавление новой группы серверов');
#-------------------------------------------------------------------------------
$DOM->AddText('Title',$Title);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table = Array();
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'text','name'=>'Name','value'=>$DSServersGroup['Name']));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Название группы',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/TextArea',Array('rows'=>3,'name'=>'Comment'),$DSServersGroup['Comment']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Комментарий',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Systems = $Config['DS']['Systems'];
#-------------------------------------------------------------------------------
$Script = Array('var Settings = {};');
#-------------------------------------------------------------------------------
foreach(Array_Keys($Systems) as $SystemID){
	#-----------------------------------------------------------------------------
	$System = $Systems[$SystemID];
	#-----------------------------------------------------------------------------
	$Options[$SystemID] = $System['Name'];
	#-----------------------------------------------------------------------------
	$Script[] = SPrintF("Settings['%s'] = %s;",$SystemID,JSON_Encode($System['Settings']));
}
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',new Tag('SCRIPT',Implode("\n",$Script)));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'SystemID','style'=>'width: 100%;'),$Options,$DSServersGroup['SystemID']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Система управления',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'text','name'=>'SortID','size'=>5,'value'=>$DSServersGroup['SortID']));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Порядок сортировки',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>SPrintF("FormEdit('/Administrator/API/DSServersGroupEdit','DSServersGroupEditForm','%s');",$Title),'value'=>($DSServersGroupID?'Сохранить':'Добавить')));
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
$Form = new Tag('FORM',Array('name'=>'DSServersGroupEditForm','onsubmit'=>'return false;'),$Comp);
#-------------------------------------------------------------------------------
if($DSServersGroupID){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('name'=>'DSServersGroupID','type'=>'hidden','value'=>$DSServersGroupID));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
