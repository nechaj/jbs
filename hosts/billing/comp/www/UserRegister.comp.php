<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = IsSet($Args)?$Args:Args();
#-------------------------------------------------------------------------------
$Eval =  (string) @$Args['Eval'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('classes/DOM.class.php','libs/Server.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(IsSet($GLOBALS['__USER'])){
	#-------------------------------------------------------------------------------
	$__USER = $GLOBALS['__USER'];
	#-------------------------------------------------------------------------------
	if(!SetCookie('OwnerID',$__USER['ID']))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$_COOKIE['OwnerID'] = $__USER['ID'];
	#-------------------------------------------------------------------------------
	if(!SetCookie('IsManaged','yes'))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$_COOKIE['IsManaged'] = 'yes';
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load(XML_HTTP_REQUEST?'Window':'Main')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Регистрация в биллинговой системе');
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/UserRegister.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/PasswordCheck.js}')));
#-------------------------------------------------------------------------------
$DOM->AddAttribs('Body',Array('onload'=>'PasswordMode();'));
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'UserRegisterForm','onsubmit'=>'return false;'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($Eval){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('name'=>'Eval','type'=>'hidden','value'=>$Eval));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table = Array();
#-------------------------------------------------------------------------------
if(IsSet($_COOKIE['OwnerID'])){
	#-------------------------------------------------------------------------------
	$OwnerID = $_COOKIE['OwnerID'];
	#-------------------------------------------------------------------------------
	if(IsSet($_COOKIE['IsManaged'])){
		#-------------------------------------------------------------------------------
		$Owner = DB_Select('Users',Array('Email','Name'),Array('UNIQ','ID'=>$OwnerID));
		#-------------------------------------------------------------------------------
		switch(ValueOf($Owner)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			# No more...
			break;
		case 'array':
			#-------------------------------------------------------------------------------
			$Table[] = Array('Партнер',SPrintF('%s (%s)',$Owner['Name'],$Owner['Email']));
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Messages = Messages();
#-------------------------------------------------------------------------------
$Table[] = 'Параметры входа в систему';
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('name'=>'Email','size'=>25,'class'=>'Duty','prompt'=>$Messages['Prompts']['Email'],'type'=>'text'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = new Tag('TD',Array('width'=>430,'colspan'=>2,'class'=>'Standard','style'=>'background-color:#FDF6D3;'),'Пожалуйста, проверьте правильность электронного адреса, т.к. он будет являться основным каналом связи и будет использоваться для входа в личный кабинет.');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('NOBODY',new Tag('SPAN','Электронный адрес (логин)'),new Tag('BR'),new Tag('SPAN',Array('class'=>'Comment'),'Например: ivanov@ivanovich.ru')),$Comp);
#-------------------------------------------------------------------------------
$Password = Comp_Load('Passwords/Generator');
if(Is_Error($Password))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('name'=>'IsPasswordCreate','id'=>'IsPasswordCreate','value'=>$Password,'type'=>'checkbox','onclick'=>'PasswordMode();'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY',new Tag('DIV',Array('style'=>'margin-bottom:5px;'),$Comp,new Tag('LABEL',Array('style'=>'font-size:10px;','for'=>'IsPasswordCreate'),'Вставить из примера')));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('name'=>'Password','size'=>16,'class'=>'Duty','prompt'=>$Messages['Prompts']['User']['Password'],'type'=>'password'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody->AddChild($Comp);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('NOBODY',new Tag('SPAN','Будущий пароль'),new Tag('BR'),new Tag('SPAN',Array('class'=>'Comment'),new Tag('SPAN',SPrintF('Например: %s',$Password)))),$NoBody);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('name'=>'_Password','size'=>16,'class'=>'Duty','prompt'=>$Messages['Prompts']['User']['Password'],'type'=>'password'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('NOBODY',new Tag('SPAN','Подтверждение пароля'),new Tag('BR'),new Tag('SPAN',Array('class'=>'Comment'),'Аналогично полю [Будущий пароль]')),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Персональные данные';
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('name'=>'Name','size'=>25,'class'=>'Duty','prompt'=>$Messages['Prompts']['User']['Name'],'type'=>'text'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('NOBODY',new Tag('SPAN','Имя'),new Tag('BR'),new Tag('SPAN',Array('class'=>'Comment'),'Например: Иван Иванович')),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Img = new Tag('IMG',Array('id'=>'Protect','align'=>'left','width'=>80,'height'=>30,'alt'=>'Включите отображение картинок','src'=>SPrintF('/Protect?Rand=%u',Rand(1000,9999))));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('name'=>'Protect','class'=>'Duty','type'=>'text'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!IsSet($GLOBALS['__USER']) && $_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']){
	#-------------------------------------------------------------------------------
	$Table[] = 'Защита от автоматических регистраций';
	#-------------------------------------------------------------------------------
	$Table[] = Array('Защитный код',$Img);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Цифры на изображении',$Comp);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!IsSet($GLOBALS['__USER'])){
	#-------------------------------------------------------------------------------
	$Div = new Tag('DIV',Array('class'=>'Standard','align'=>'right'));
	#-------------------------------------------------------------------------------
	$Div->AddHTML(TemplateReplace('www.UserRegister'));
	#-------------------------------------------------------------------------------
	$Table[] = $Div;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>"if(PasswordCheck(this.form,'Password')) UserRegister();",'value'=>'Регистрация',));
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
$Out = $DOM->Build(!XML_HTTP_REQUEST);
#-------------------------------------------------------------------------------
if(Is_Error($Out))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return (XML_HTTP_REQUEST?Array('Status'=>'Ok','DOM'=>$DOM->Object):$Out);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
