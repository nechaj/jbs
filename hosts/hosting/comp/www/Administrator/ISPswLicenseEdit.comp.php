<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$ISPswLicenseID = (integer) @$Args['ISPswLicenseID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$ISPswLicense = DB_Select('ISPswLicensesOwners',Array('*'),Array('UNIQ','ID'=>$ISPswLicenseID));
#-------------------------------------------------------------------------------
switch(ValueOf($ISPswLicense)){
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
$DOM->AddText('Title','Редактирование информации о лицензии');
#-------------------------------------------------------------------------------
$Table = $Options = Array();
#-------------------------------------------------------------------------------
$Table[] = 'Общая информация';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Внутренний идентификатор лицензии',$ISPswLicense['ID']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Идентификатор в ISPsystem (elid)',$ISPswLicense['elid']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('IP адрес лицензии',$ISPswLicense['IP']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# CreateDate
$Comp = Comp_Load('Formats/Date/Standard',$ISPswLicense['CreateDate']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Дата создания лицензии',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# время_изменения + 31_день - время_сейчас
$m_time = $ISPswLicense['ip_change_date'] + 31 * 24 * 3600 - Time();
#-------------------------------------------------------------------------------
if($m_time > 0){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Formats/Date/Remainder', $m_time);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Formats/Date/Remainder', 0);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('До разрешения менять IP',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# время_изменения + 31_день - время_сейчас
$m_time = $ISPswLicense['lickey_change_date'] + 31 * 24 * 3600 - Time();
#-------------------------------------------------------------------------------
if($m_time > 0){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Formats/Date/Remainder', $m_time);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Formats/Date/Remainder', 0);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('До разрешения менять ключ',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Date/Standard',$ISPswLicense['update_expiredate']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Обновления закончатся',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# StatusDate
$Comp = Comp_Load('Formats/Date/Standard',$ISPswLicense['StatusDate']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Дата установки статуса',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# ExpireDate
$Comp = Comp_Load('Formats/Date/Standard',$ISPswLicense['ExpireDate']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Лицензия истекает',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$ISPswLicense['ISPname'])
	$ISPswLicense['ISPname'] = '-';
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/String',$ISPswLicense['ISPname'],50);
if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Имя лицензии',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/String',$ISPswLicense['LicKey'],50);
if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Ключ лицензии',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Число узлов кластера',$ISPswLicense['addon']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Options = Comp_Load('Formats/ISPswOrder/SoftWareList');
if(Is_Error($Options))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#Debug(SPrintF('[comp/www/Administrator/ISPswSchemeEdit]: Options = %s',print_r($Options,true)));
#-------------------------------------------------------------------------------
$Table[] = Array('Тип лицензии',$Options['pricelist_id'][$ISPswLicense['pricelist_id']]);
#-------------------------------------------------------------------------------
$Table[] = Array('Период заказа (месяцев/пробная/вечная)',$Options['period'][$ISPswLicense['period']]);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Данные лицензии';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'type'	=> 'text',
			'style'	=> 'width:100%;',
			'name'	=> 'Flag',
			'value' => $ISPswLicense['Flag'],
			'prompt'=> 'Любая строка записанная в это поле, приведёт к тому что лицензия не будет использоваться при поиске свободных лицензий. Можно, например, записать имя сервера на котором она установлена - если эта лицензия используется для собственных нужд организации'
		)
	);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-----------------------------------------------------------------------------
$Table[] = Array('Флаг',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'IsInternal','id'=>'IsInternal','value'=>'yes'),$ISPswLicense['IsInternal']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
if($ISPswLicense['IsInternal'])
	$Comp->AddAttribs(Array('checked'=>'yes'));
#-----------------------------------------------------------------------------
$Table[] = Array(new Tag('LABEL',Array('for'=>'IsInternal'),'Внутренняя лицензия'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'IsUsed','id'=>'IsUsed','value'=>'yes'),$ISPswLicense['IsUsed']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
if($ISPswLicense['IsUsed'])
	$Comp->AddAttribs(Array('checked'=>'yes'));
#-----------------------------------------------------------------------------
$Table[] = Array(new Tag('LABEL',Array('for'=>'IsUsed'),'Используется'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# ISPname 

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# StatusID

# Flag

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'button',
    'onclick' => "FormEdit('/Administrator/API/ISPswLicenseEdit','ISPswLicenseEditForm','Редактирование информации о лицензии');",
    'value'   => 'Сохранить'
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
#Debug("[comp/www/Administrator/ISPswLicenseEdit]: Table = " . print_r($Table, true));
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'ISPswLicenseEditForm','onsubmit'=>'return false;'),$Comp);
#-------------------------------------------------------------------------------
if($ISPswLicenseID){
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'name'  => 'ISPswLicenseID',
      'type'  => 'hidden',
      'value' => $ISPswLicenseID
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Form->AddChild($Comp);
}
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------

?>
