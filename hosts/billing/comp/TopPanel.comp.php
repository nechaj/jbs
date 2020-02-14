<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Links = Links();
#-------------------------------------------------------------------------------
$Path = System_Element('templates/TopPanel/Base.xml');
if(Is_Error($Path))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Parse = IO_Read($Path);
if(Is_Error($Parse))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM = new DOM($Parse);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('libs/Mobile_Detect.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Detect = new Mobile_Detect();
#-------------------------------------------------------------------------------
$GLOBALS['IsMobile'] = $Detect->isMobile();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// низЭнькое устройство. убираем логотип вверху
if(IsSet($_COOKIE['hScreen']) && $_COOKIE['hScreen'] < 550){
	#-------------------------------------------------------------------------------
	Debug(SprintF('[comp/TopPanel]: детектировано устройство с низким вертикальным разрешением, $IsMobile = %s; wScreen = %s; hScreen = %s',($GLOBALS['IsMobile'])?'TRUE':'FALSE',@$_COOKIE['wScreen'],@$_COOKIE['hScreen']));
	#-------------------------------------------------------------------------------
	$Script = '$(document).ready ( function(){$(\'#TopLogo\').css(\'display\',\'none\');});';
	#-------------------------------------------------------------------------------
	$Links['DOM']->AddChild('Head',new Tag('SCRIPT',$Script));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
Debug(SprintF('[comp/TopPanel]: проверка мобильного устройства, IP = %s; $IsMobile = %s; wScreen = %s; hScreen = %s',@$_SERVER['REMOTE_ADDR'],($GLOBALS['IsMobile'])?'TRUE':'FALSE',@$_COOKIE['wScreen'],@$_COOKIE['hScreen']));
#-------------------------------------------------------------------------------
if($GLOBALS['IsMobile'] || (IsSet($_COOKIE['wScreen']) && $_COOKIE['wScreen'] < 950)){
/*
#-------------------------------------------------------------------------------
// есть мнение, что надо смотреть что $_COOKIE['wScreen'] пустая и выводить юзеру кнопку.
// нажал - установилась кука что это $IsForceMobile, например - и подключаются стили
	// загружаем специфический стиль под мобильники
	$Comp = Comp_Load('Css',Array('Mobile'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	foreach($Comp as $Css)
		$Links['DOM']->AddChild('Head',$Css);
	#-------------------------------------------------------------------------------
*/
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Tr = new Tag('TR');
#-------------------------------------------------------------------------------
if(!IsSet($GLOBALS['__USER'])){
	#-------------------------------------------------------------------------------
	$Links['DOM']->AddChild('Head',new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Logon.js}')));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Path = System_Element('templates/TopPanel/Logon.xml');
	if(Is_Error($Path))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	/* новый вариант, с адаптивным дизайном
	$Path = System_Element('templates/TopPanel/Logon.html');
	if(Is_Error($Path))
		return ERROR | @Trigger_Error(500);
	*/
	#-------------------------------------------------------------------------------
	$Parse = IO_Read($Path);
	if(Is_Error($Parse))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Tr->AddHTML($Parse);
	#-------------------------------------------------------------------------------
	$Script = new Tag('SCRIPT',Array('type'=>'text/javascript'),"function TopPanelLogon(){ Logon(document.getElementById('TopPanelEmail').value,document.getElementById('TopPanelPassword').value,document.getElementById('TopPanelIsRemember').checked); }");
	#-------------------------------------------------------------------------------
	$Links['DOM']->AddChild('Head',$Script);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$__USER = $GLOBALS['__USER'];
	#-------------------------------------------------------------------------------
	if(Is_Null($__USER))
		return ERROR | @Trigger_Error(400);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# передвинуто для всех юзеров, для реализации JBS-239
	$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Events.js}'));
	#-------------------------------------------------------------------------------
	$Links['DOM']->AddChild('Head',$Script);
	#-------------------------------------------------------------------------------
	$Links['DOM']->AddAttribs('Body',Array('onload'=>"CheckEvents();"));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(!$__USER['IsAdmin']){
		#-------------------------------------------------------------------------------
		$Contracts = DB_Select('Contracts',Array('ID','TypeID','Customer','Balance','(SELECT COUNT(*) FROM `Orders` WHERE `Orders`.`ContractID` = `Contracts`.`ID`) AS `Orders`'),Array('Where'=>SPrintF('`UserID` = %u',$__USER['ID']),'SortOn'=>Array('Orders'),'IsDesc'=>TRUE));
		#-------------------------------------------------------------------------------
		switch(ValueOf($Contracts)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			# No more...
			break;
		case 'array':
			break;
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		$Table = new Tag('TABLE',Array('class'=>'Standard','style'=>'border: 1px solid #707680;','width'=>'100%'));
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		if(SizeOf($Contracts) > 3){
			#-------------------------------------------------------------------------------
			$UniqID = UniqID('ID'); $UniqID2 = UniqID('ID2');
			#-------------------------------------------------------------------------------
			$Table->AddChild(
					new Tag('TR',
						new Tag('TD',
							Array(
								'colspan'	=> 5,
								'style'		=> 'cursor:pointer;',
								'onclick'	=> SPrintF("var Style = document.getElementById('%s').style; Style.display = (Style.display != 'none'?'none':''); document.getElementById('%s').innerHTML = (Style.display != 'none'?'Кликните чтобы свернуть список ваших договоров<hr size=1>':'Просмотр списка ваших договоров')",$UniqID,$UniqID2)
								),
								new Tag('DIV',Array('id'=>$UniqID2,'name'=>$UniqID2),'Просмотр списка ваших договоров')
							)
						)
					);
			#-------------------------------------------------------------------------------
			$Table->AddChild(new Tag('TBODY',Array('id'=>$UniqID,'style'=>'display:none;')));
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		foreach($Contracts as $Contract){
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/TopPanel]: Contract TypeID = %s',$Contract['TypeID']));
			#-------------------------------------------------------------------------------
			$ContractID = Comp_Load('Formats/Contract/Number',$Contract['ID']);
			#-------------------------------------------------------------------------------
			if(Is_Error($ContractID))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Formats/Currency',$Contract['Balance']);
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			if($Contract['TypeID'] == 'NaturalPartner' || $Contract['TypeID'] == 'Juridical'){
				#-------------------------------------------------------------------------------
				$A = new Tag('SPAN','-');
				#-------------------------------------------------------------------------------
			}else{
				#-------------------------------------------------------------------------------
				$A = new Tag('A',Array('href'=>SPrintF("javascript:ShowWindow('/InvoiceMake',{ContractID:%u,StepID:1});",$Contract['ID'])),'[пополнить]');
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			// ширина колонки с именем договора - примерно 65%
			$Width = Min(@$_COOKIE['wScreen']*0.65,650);
			#-------------------------------------------------------------------------------
			if($Width == 0)
				$Width = 200;
			#-------------------------------------------------------------------------------
			$Table->AddChild(
					new Tag('TR',
					new Tag('TD',Array('style'=>'text-align:left;'),SPrintF('#%s',$ContractID)),
	    				new Tag('TD',Array('style'=>'text-align:left;','width'=>$Width),new Tag('DIV',Array('style'=>SPrintF('width:%upx;overflow:hidden;white-space:nowrap;',$Width)),$Contract['Customer'])),
					new Tag('TD',Array('style'=>'text-align:left;white-space:nowrap;'),SPrintF('бал: %s,',$Comp)),
					new Tag('TD',Array('style'=>'text-align:left;white-space:nowrap;'),SPrintF('зак: %u',$Contract['Orders'])),
					new Tag('TD',Array('style'=>'text-align:left'),$A)
					));
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		if(Count($Table->Childs))
			$Links['DOM']->AddChild('Context',$Table,TRUE);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Notes',$__USER['InterfaceID']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Links['DOM']->AddChild('Context',$Comp,TRUE);
	#-------------------------------------------------------------------------------
	$MenuPath = SPrintF('%s/TopPanel',$__USER['InterfaceID']);
	#-------------------------------------------------------------------------------
	$Items = Styles_Menu($MenuPath);
	if(Is_Error($Items))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Items = &$Items['Items'];
	#-------------------------------------------------------------------------------
	$Tr->AddChild(new Tag('TD',Array('width'=>5)));
	#-------------------------------------------------------------------------------
	foreach($Items as $Item){
		#-------------------------------------------------------------------------------
		$Td = new Tag('TD',Array('valign'=>'bottom'));
		#-------------------------------------------------------------------------------
		$Prefix = ($Item['IsActive']?'Active':'UnActive');
		#-------------------------------------------------------------------------------
		$Section = new DOM(TemplateReplace('TopPanel'));
		#-------------------------------------------------------------------------------
		$Section->AddAttribs('TopPanelTabLeft',Array('src'=>SPrintF('SRC:{Images/TopPanelTabLeft%s.png}',$Prefix)));
		#-------------------------------------------------------------------------------
		$IsPick = (!$Item['IsActive'] && IsSet($Item['Pick']));
		#-------------------------------------------------------------------------------
		$Section->AddAttribs('TopPanelTabCenter',Array('style'=>SPrintF('background-image:url(%s);',SPrintF('SRC:{Images/TopPanelTabCenter%s.png}',($IsPick?SPrintF('%sPick',$Prefix):$Prefix)))));
		#-------------------------------------------------------------------------------
		$Adding = new Tag('A',Array('href'=>$Item['Href']),$Item['Text']);
		#-------------------------------------------------------------------------------
		$Adding->AddAttribs(Array('class'=>$IsPick?'TopPanelPick':'TopPanel'));
		#-------------------------------------------------------------------------------
		if(IsSet($Item['Comp'])){
			#-------------------------------------------------------------------------------
			$Adding = Comp_Load($Item['Comp'],$Adding);
			if(Is_Error($Adding))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		if(IsSet($Item['Prompt'])){
			#-------------------------------------------------------------------------------
			$LinkID = UniqID('ID');
			#-------------------------------------------------------------------------------
			$Links = &Links();
			#-------------------------------------------------------------------------------
			$Links[$LinkID] = &$Adding;
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Form/Prompt',$LinkID,$Item['Prompt']);
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			UnSet($Links[$LinkID]);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$Section->AddChild('TopPanelTabCenter',$Adding);
		#-------------------------------------------------------------------------------
		$Section->AddAttribs('TopPanelTabRight',Array('src'=>SPrintF('SRC:{Images/TopPanelTabRight%s.png}',$Prefix)));
		#-------------------------------------------------------------------------------
		$Td->AddChild($Section->Links['TopPanel']);
		#-------------------------------------------------------------------------------
		$Tr->AddChild($Td);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Adding = new Tag('TR');
	#-------------------------------------------------------------------------------
	$Adding->AddChild(new Tag('TD',new Tag('A',Array('class'=>'Button','title'=>'Мои настройки','href'=>"javascript:ShowWindow('/UserPersonalDataChange');"),'Мои настройки')));
	#-------------------------------------------------------------------------------
	$Adding->AddChild(new Tag('TD',Array('class'=>'TopPanelSeparator','align'=>'center','width'=>5,'style'=>'color:#848484;'),'|'));
	#-------------------------------------------------------------------------------
	$A = new Tag('A',Array('class'=>'Button','title'=>'Выход из системы','href'=>"javascript:ShowConfirm('Вы действительно хотите выйти из системы?','Logout();');"),'Выход');
	#-------------------------------------------------------------------------------
	$Adding->AddChild(new Tag('TD',$A));
	#-------------------------------------------------------------------------------
	$Tr->AddChild(new Tag('TD',Array('valign'=>'bottom'),new Tag('TABLE',Array('height'=>25),$Adding)));
	#-------------------------------------------------------------------------------
	$Session = new Session((string)@$_COOKIE['SessionID']);
	#-------------------------------------------------------------------------------
	$IsLoad = $Session->Load();
	if(Is_Error($IsLoad))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$UsersIDs = @$Session->Data['UsersIDs'];
	#-------------------------------------------------------------------------------
	if(Count($UsersIDs) < 1)
		return ERROR | @Trigger_Error(400);
	#-------------------------------------------------------------------------------
	$Array = Array();
	#-------------------------------------------------------------------------------
	foreach($UsersIDs as $UserID)
		$Array[] = (integer)$UserID;
	#-------------------------------------------------------------------------------
	$Users = DB_Select('Users',Array('ID','GroupID','Name','Email'),Array('Where'=>SPrintF('`ID` IN (%s)',Implode(',',$Array))));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Users)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		#----->
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	$Span = new Tag('SPAN',new Tag('SPAN',Array('style'=>SPrintF('font-weight:bold;text-decoration:underline;color:#%s;',$__USER['ID'] != @$Session->Data['RootID']?'990000':'6F9006')),$__USER['Name']));
	#-------------------------------------------------------------------------------
	if(Count($Users) > 1){
		#-------------------------------------------------------------------------------
		$Options[] = new Tag('OPTION',Array('style'=>'font-size:11px;font-weight:bold;'),'[очистить]');
		#-------------------------------------------------------------------------------
		$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/UserSwitch.js}'));
		#-------------------------------------------------------------------------------
		$Links['DOM']->AddChild('Head',$Script);
		#-------------------------------------------------------------------------------
		$Table = new Tag('TABLE',Array('id'=>'TopPanelUserSwitch','class'=>'Standard','cellspacing'=>5,'style'=>'position:absolute;display:none;'));
		#-------------------------------------------------------------------------------
		$Users[] = Array('ID'=>NULL,'Email'=>'[очистить список]');
		#-------------------------------------------------------------------------------
		foreach($Users as $User){
			#-------------------------------------------------------------------------------
			$UserID = $User['ID'];
			#-------------------------------------------------------------------------------
			if($UserID != $__USER['ID']){
				#-------------------------------------------------------------------------------
				$A = new Tag('A',Array('href'=>SPrintF('javascript:UserSwitch(%u);',$UserID)),$User['Email']);
				#-------------------------------------------------------------------------------
				$Table->AddChild($UserID?new Tag('TR',new Tag('TD',Array('class'=>'Standard','width'=>10)),new Tag('TD',$UserID != @$Session->Data['RootID']?$A:new Tag('B',$A))):new Tag('TD',Array('colspan'=>2,'align'=>'right','style'=>'border-top:1px solid #DCDCDC;'),$A));
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$Links['DOM']->AddChild('Floating',$Table);
		#-------------------------------------------------------------------------------
		$Span->AddAttribs(Array('onclick'=>"with(document.getElementById('TopPanelUserSwitch').style){ if(display != 'none'){ display = 'none'; } else { var Position = GetPosition(this); left = Position.clientX; top = Position.clientY + 10; display = 'block'; }}",'style'=>'cursor:pointer;'));
		#-------------------------------------------------------------------------------
		$Span->AddChild(new Tag('IMG',Array('width'=>5,'height'=>10,'src'=>'SRC:{Images/TopPanelUserSwitch.gif}')));
		#-------------------------------------------------------------------------------
		$Span->AddChild(new Tag('SPAN','[сменить]'));
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$DOM->AddChild('TopPanelMiddle',new Tag('DIV',Array('style'=>'font-size:12px;color:#505050;padding-left:5px;'),new Tag('SPAN','Пользователь:'),$Span));
	#-------------------------------------------------------------------------------
	$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Logout.js}'));
	#-------------------------------------------------------------------------------
	$Links['DOM']->AddChild('Head',$Script);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$DOM->AddChild('TopPanelMenu',$Tr);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// достаём все ссылки - нужны для построения главного мобильного меню (кстати, для незалогиненых - надо ещё и формы доставать)
$MenuLinks = $DOM->GetByTagName('A');
#-------------------------------------------------------------------------------
// готовим блок с меню
$NoBody = new Tag('NOBODY');
#-------------------------------------------------------------------------------
// главное меню
$Links['DOM']->AddChild('MenuHead',new Tag('SPAN','Основное меню'));
$Links['DOM']->AddAttribs('MenuHead',Array('OnClick'=>"$('#MobileMenuTR').slideToggle();"));
#-------------------------------------------------------------------------------
// дополнительное меню
$Links['DOM']->AddChild('MenuExtended',new Tag('SPAN','Расширенное меню'));
$Links['DOM']->AddAttribs('MenuExtended',Array('OnClick'=>"$('#MenuLeft').slideToggle();"));
// перебираем ссылки, составляем список пунктов меню
foreach($MenuLinks as $A){
	#-------------------------------------------------------------------------------
	// достаём ссылку
	$Href = $A->{'Attribs'}['href'];
	#-------------------------------------------------------------------------------
	// если ссылка начинается со слэша, то это именно ссылка. её надо в JS переделать, иначе на div не навесишь
	if($Href[0] == '/')
		$Href = SPrintF("javascript:location.replace('%s');",$Href);
	#-------------------------------------------------------------------------------
	// создаём пункт меню
	$Div = new Tag('DIV',Array('class'=>'MainMenu','OnClick'=>$Href),$A);
	#-------------------------------------------------------------------------------
	if(IsSet($A->{'Attribs'}['onmouseover']))
		$Div->AddAttribs(Array('onmouseover'=>$A->{'Attribs'}['onmouseover']));
	#-------------------------------------------------------------------------------
	$NoBody->AddChild($Div);
	#Debug(SprintF('[comp/TopPanel]: Text = %s; href = %s',print_r($A->{'Text'},true),print_r($A->{'Attribs'}['href'],true)));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Links['DOM']->AddChild('MobileMenu',$NoBody);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------



//echo $ddd;
#-------------------------------------------------------------------------------
if(IsSet($_COOKIE['Email']))
	$DOM->AddAttribs('TopPanelEmail',Array('value'=>$_COOKIE['Email']));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $DOM->Object;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
