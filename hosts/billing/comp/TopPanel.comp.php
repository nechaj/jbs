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
if(!IsSet($_COOKIE['IsMobile'])){
	#-------------------------------------------------------------------------------
	if(Is_Error(System_Load('libs/Mobile_Detect.php')))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Detect = new Mobile_Detect();
	#-------------------------------------------------------------------------------
	$IsMobile = $Detect->isMobile();
	#-------------------------------------------------------------------------------
	if($IsMobile){
		#-------------------------------------------------------------------------------
		if(!SetCookie('IsMobile',1,Time() + 3600,'/'))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		if(!SetCookie('IsMobile',0,Time() + 3600,'/'))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$IsMobile = $_COOKIE['IsMobile'];
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
if($IsMobile || (IsSet($_COOKIE['hScreen']) && $_COOKIE['hScreen'] < 600)){
	#-------------------------------------------------------------------------------
	$Script = '$(document).ready ( function(){$(\'#TopLogo\').css(\'display\',\'none\');});';
	#-------------------------------------------------------------------------------
	$Links['DOM']->AddChild('Head',new Tag('SCRIPT',$Script));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Tr = new Tag('TR');
#-------------------------------------------------------------------------------
if(!IsSet($GLOBALS['__USER'])){
  #-----------------------------------------------------------------------------
  $Links['DOM']->AddChild('Head',new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Logon.js}')));
  #-----------------------------------------------------------------------------
  #-----------------------------------------------------------------------------
  $Path = System_Element('templates/TopPanel/Logon.xml');
  if(Is_Error($Path))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Parse = IO_Read($Path);
  if(Is_Error($Parse))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Tr->AddHTML($Parse);
  #-----------------------------------------------------------------------------
  $Script = new Tag('SCRIPT',Array('type'=>'text/javascript'),"function TopPanelLogon(){ Logon(document.getElementById('TopPanelEmail').value,document.getElementById('TopPanelPassword').value,document.getElementById('TopPanelIsRemember').checked); }");
  #-----------------------------------------------------------------------------
  $Links['DOM']->AddChild('Head',$Script);
}else{
  #-----------------------------------------------------------------------------
  $__USER = $GLOBALS['__USER'];
  #-----------------------------------------------------------------------------
  if(Is_Null($__USER))
    return ERROR | @Trigger_Error(400);
  #-----------------------------------------------------------------------------
  #-----------------------------------------------------------------------------
  # передвинуто для всех юзеров, для реализации JBS-239
  #-------------------------------------------------------------------------
  $Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Events.js}'));
  #-------------------------------------------------------------------------
  $Links['DOM']->AddChild('Head',$Script);
  #-------------------------------------------------------------------------
  $Links['DOM']->AddAttribs('Body',Array('onload'=>"CheckEvents();"));
  #-----------------------------------------------------------------------------
  #-----------------------------------------------------------------------------
  if(!$__USER['IsAdmin']){
      #-------------------------------------------------------------------------
      $Contracts = DB_Select('Contracts',Array('ID','TypeID','Customer','Balance'),Array('Where'=>SPrintF('`UserID` = %u',$__USER['ID'])));
      #-------------------------------------------------------------------------
      switch(ValueOf($Contracts)){
        case 'error':
          return ERROR | @Trigger_Error(500);
        case 'exception':
          # No more...
        break;
        case 'array':
          #---------------------------------------------------------------------
          $Table = new Tag('TABLE',Array('class'=>'Standard','style'=>'border: 1px solid #707680;','width'=>'100%'));
	  #---------------------------------------------------------------------
	  #---------------------------------------------------------------------
	  if(SizeOf($Contracts) > 2){
		  $UniqID = UniqID('ID');
		$Table->AddChild(new Tag('TR',
		new Tag('TD',
			Array(
				'colspan'=>4,
				'style'=>'cursor:pointer;',
				'onclick'=>SPrintF("var Style = document.getElementById('%s').style;Style.display = (Style.display != 'none'?'none':'');",$UniqID)),
				'Просмотр списка ваших договоров'
			)));
		$Table->AddChild(new Tag('TBODY',Array('id'=>$UniqID,'style'=>'display:none;')));
	  }
	  #---------------------------------------------------------------------
	  #---------------------------------------------------------------------
          foreach($Contracts as $Contract){
	    #-------------------------------------------------------------------
            $ContractID = Comp_Load('Formats/Contract/Number',$Contract['ID']);
            if(Is_Error($ContractID))
              return ERROR | @Trigger_Error(500);
            #-------------------------------------------------------------------
            $Comp = Comp_Load('Formats/Currency',$Contract['Balance']);
            if(Is_Error($Comp))
              return ERROR | @Trigger_Error(500);
            #-------------------------------------------------------------------
            $A = new Tag('A',Array('href'=>SPrintF("javascript:ShowWindow('/InvoiceMake',{ContractID:%u,StepID:1});",$Contract['ID'])),'[пополнить]');
            #-------------------------------------------------------------------
            $Table->AddChild(new Tag('TR',
	    				new Tag('TD',Array('style'=>'text-align:left;'),SPrintF('#%s',$ContractID)),
	    				new Tag('TD',Array('style'=>'text-align:left;overflow-x:hidden','width'=>'70%'),$Contract['Customer']),
					new Tag('TD',Array('style'=>'text-align:left;'),SPrintF('баланс: %s',$Comp)),
					new Tag('TD',Array('style'=>'text-align:left'),$A))
				);
          }
          #---------------------------------------------------------------------
          if(Count($Table->Childs))
            $Links['DOM']->AddChild('Context',$Table,TRUE);
          #---------------------------------------------------------------------
        break;
        default:
          return ERROR | @Trigger_Error(101);
      }
  }else{	# неадмин -> админ
      # commented for JBS-239
      ##-------------------------------------------------------------------------
      #$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Events.js}'));
      ##-------------------------------------------------------------------------
      #$Links['DOM']->AddChild('Head',$Script);
      ##-------------------------------------------------------------------------
      #$Links['DOM']->AddAttribs('Body',Array('onload'=>"CheckEvents();"));
      ##-------------------------------------------------------------------------
  }
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load('Notes',$__USER['InterfaceID']);
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Links['DOM']->AddChild('Context',$Comp,TRUE);
  #-----------------------------------------------------------------------------
  $MenuPath = SPrintF('%s/TopPanel',$__USER['InterfaceID']);
  #-----------------------------------------------------------------------------
  $Items = Styles_Menu($MenuPath);
  if(Is_Error($Items))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Items = &$Items['Items'];
  #-----------------------------------------------------------------------------
  $Tr->AddChild(new Tag('TD',Array('width'=>5)));
  #-----------------------------------------------------------------------------
  foreach($Items as $Item){
    #---------------------------------------------------------------------------
    $Td = new Tag('TD',Array('valign'=>'bottom'));
    #---------------------------------------------------------------------------
    $Prefix = ($Item['IsActive']?'Active':'UnActive');
    #---------------------------------------------------------------------------
    $Section = new DOM(TemplateReplace('TopPanel'));
    #---------------------------------------------------------------------------
    $Section->AddAttribs('TopPanelTabLeft',Array('src'=>SPrintF('SRC:{Images/TopPanelTabLeft%s.png}',$Prefix)));
    #---------------------------------------------------------------------------
    $IsPick = (!$Item['IsActive'] && IsSet($Item['Pick']));
    #---------------------------------------------------------------------------
    $Section->AddAttribs('TopPanelTabCenter',Array('style'=>SPrintF('background-image:url(%s);',SPrintF('SRC:{Images/TopPanelTabCenter%s.png}',($IsPick?SPrintF('%sPick',$Prefix):$Prefix)))));
    #---------------------------------------------------------------------------
    $Adding = new Tag('A',Array('href'=>$Item['Href']),$Item['Text']);
    #---------------------------------------------------------------------------
    $Adding->AddAttribs(Array('class'=>$IsPick?'TopPanelPick':'TopPanel'));
    #---------------------------------------------------------------------------
    if(IsSet($Item['Comp'])){
      #-------------------------------------------------------------------------
      $Adding = Comp_Load($Item['Comp'],$Adding);
      if(Is_Error($Adding))
        return ERROR | @Trigger_Error(500);
    }
    #---------------------------------------------------------------------------
    if(IsSet($Item['Prompt'])){
      #-------------------------------------------------------------------------
      $LinkID = UniqID('ID');
      #-------------------------------------------------------------------------
      $Links = &Links();
      #-------------------------------------------------------------------------
      $Links[$LinkID] = &$Adding;
      #-------------------------------------------------------------------------
      $Comp = Comp_Load('Form/Prompt',$LinkID,$Item['Prompt']);
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      UnSet($Links[$LinkID]);
    }
    #---------------------------------------------------------------------------
    $Section->AddChild('TopPanelTabCenter',$Adding);
    #---------------------------------------------------------------------------
    $Section->AddAttribs('TopPanelTabRight',Array('src'=>SPrintF('SRC:{Images/TopPanelTabRight%s.png}',$Prefix)));
    #---------------------------------------------------------------------------
    $Td->AddChild($Section->Links['TopPanel']);
    #---------------------------------------------------------------------------
    $Tr->AddChild($Td);
  }
  #-----------------------------------------------------------------------------
  $Adding = new Tag('TR');
  #-----------------------------------------------------------------------------
  $Adding->AddChild(new Tag('TD',new Tag('A',Array('class'=>'Button','title'=>'Мои настройки','href'=>"javascript:ShowWindow('/UserPersonalDataChange');"),'Мои настройки')));
  #-----------------------------------------------------------------------------
  $Adding->AddChild(new Tag('TD',Array('class'=>'TopPanelSeparator','align'=>'center','width'=>5,'style'=>'color:#848484;'),'|'));
  #-----------------------------------------------------------------------------
  $A = new Tag('A',Array('class'=>'Button','title'=>'Выход из системы','href'=>"javascript:ShowConfirm('Вы действительно хотите выйти из системы?','Logout();');"),'Выход');
  #-----------------------------------------------------------------------------
  $Adding->AddChild(new Tag('TD',$A));
  #-----------------------------------------------------------------------------
  $Tr->AddChild(new Tag('TD',Array('valign'=>'bottom'),new Tag('TABLE',Array('height'=>25),$Adding)));
  #-----------------------------------------------------------------------------
  $Session = new Session((string)@$_COOKIE['SessionID']);
  #-----------------------------------------------------------------------------
  $IsLoad = $Session->Load();
  if(Is_Error($IsLoad))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $UsersIDs = $Session->Data['UsersIDs'];
  if(Count($UsersIDs) < 1)
    return ERROR | @Trigger_Error(400);
  #-----------------------------------------------------------------------------
  $Array = Array();
  #-----------------------------------------------------------------------------
  foreach($UsersIDs as $UserID)
    $Array[] = (integer)$UserID;
  #-----------------------------------------------------------------------------
  $Users = DB_Select('Users',Array('ID','GroupID','Name','Email'),Array('Where'=>SPrintF('`ID` IN (%s)',Implode(',',$Array))));
  #-----------------------------------------------------------------------------
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
  #-----------------------------------------------------------------------------
  $Span = new Tag('SPAN',new Tag('SPAN',Array('style'=>SPrintF('font-weight:bold;text-decoration:underline;color:#%s;',$__USER['ID'] != @$Session->Data['RootID']?'990000':'6F9006')),$__USER['Name']));
  #-----------------------------------------------------------------------------
  if(Count($Users) > 1){
    #---------------------------------------------------------------------------
    $Options[] = new Tag('OPTION',Array('style'=>'font-size:11px;font-weight:bold;'),'[очистить]');
    #---------------------------------------------------------------------------
    $Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/UserSwitch.js}'));
    #---------------------------------------------------------------------------
    $Links['DOM']->AddChild('Head',$Script);
    #---------------------------------------------------------------------------
    $Table = new Tag('TABLE',Array('id'=>'TopPanelUserSwitch','class'=>'Standard','cellspacing'=>5,'style'=>'position:absolute;display:none;'));
    #---------------------------------------------------------------------------
    $Users[] = Array('ID'=>NULL,'Email'=>'[очистить список]');
    #---------------------------------------------------------------------------
    foreach($Users as $User){
      #-------------------------------------------------------------------------
      $UserID = $User['ID'];
      #-------------------------------------------------------------------------
      if($UserID != $__USER['ID']){
        #-----------------------------------------------------------------------
	#Debug(print_r($User,true));
        $A = new Tag('A',Array('href'=>SPrintF('javascript:UserSwitch(%u);',$UserID)),$User['Email']);
        #-----------------------------------------------------------------------
        $Table->AddChild($UserID?new Tag('TR',new Tag('TD',Array('class'=>'Standard','width'=>10)),new Tag('TD',$UserID != @$Session->Data['RootID']?$A:new Tag('B',$A))):new Tag('TD',Array('colspan'=>2,'align'=>'right','style'=>'border-top:1px solid #DCDCDC;'),$A));
      }
    }
    #---------------------------------------------------------------------------
    $Links['DOM']->AddChild('Floating',$Table);
    #---------------------------------------------------------------------------
    $Span->AddAttribs(Array('onclick'=>"with(document.getElementById('TopPanelUserSwitch').style){ if(display != 'none'){ display = 'none'; } else { var Position = GetPosition(this); left = Position.clientX; top = Position.clientY + 10; display = 'block'; }}",'style'=>'cursor:pointer;'));
    #---------------------------------------------------------------------------
    $Span->AddChild(new Tag('IMG',Array('width'=>5,'height'=>10,'src'=>'SRC:{Images/TopPanelUserSwitch.gif}')));
    #---------------------------------------------------------------------------
    $Span->AddChild(new Tag('SPAN','[сменить]'));
  }
  #-----------------------------------------------------------------------------
  $DOM->AddChild('TopPanelMiddle',new Tag('DIV',Array('style'=>'font-size:12px;color:#505050;padding-left:5px;'),new Tag('SPAN','Пользователь:'),$Span));
  #-----------------------------------------------------------------------------
  $Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Logout.js}'));
  #-----------------------------------------------------------------------------
  $Links['DOM']->AddChild('Head',$Script);
}
#-------------------------------------------------------------------------------
$DOM->AddChild('TopPanelMenu',$Tr);
#-------------------------------------------------------------------------------
if(IsSet($_COOKIE['Email']))
  $DOM->AddAttribs('TopPanelEmail',Array('value'=>$_COOKIE['Email']));
#-------------------------------------------------------------------------------
return $DOM->Object;
#-------------------------------------------------------------------------------

?>
