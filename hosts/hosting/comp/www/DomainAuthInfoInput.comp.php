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
$DomainOrderID = (integer) @$Args['DomainOrderID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php','libs/WhoIs.php')))
  return ERROR | @Trigger_Error(500);
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
$DomainOrder = DB_Select('DomainsOrdersOwners',Array('ID','UserID','SchemeID','DomainName','Name','AuthInfo','StatusID'),Array('UNIQ','ID'=>$DomainOrderID));
#-------------------------------------------------------------------------------
switch(ValueOf($DomainOrder)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	#---------------------------------------------------------------------------
	$__USER = $GLOBALS['__USER'];
	#---------------------------------------------------------------------------
	$IsPermission = Permission_Check('DomainsOrdersRead',(integer)$__USER['ID'],(integer)$DomainOrder['UserID']);
	#---------------------------------------------------------------------------
	switch(ValueOf($IsPermission)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'false':
		return ERROR | @Trigger_Error(700);
	case 'true':
		# OK
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#---------------------------------------------------------------------------
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(In_Array($DomainOrder['Name'],Array('ru','su','рф')))
	return new gException('AUTHCODE_NOT_USED','Для доменов в зонах ru/su/рф пароль (AuthInfo) не используется');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM->AddText('Title',SPrintF('Перенос домена %s.%s',$DomainOrder['DomainName'],$DomainOrder['Name']));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'DomainAuthInfoInputForm','onsubmit'=>'return false;'));
#-------------------------------------------------------------------------------
          #---------------------------------------------------------------------
          $Comp = Comp_Load(
            'Form/Input',
            Array(
              'name'  => 'DomainOrderID',
              'type'  => 'hidden',
              'value' => $DomainOrderID
            )
          );
          if(Is_Error($Comp))
            return ERROR | @Trigger_Error(500);
          #---------------------------------------------------------------------
          $Form->AddChild($Comp);
          #---------------------------------------------------------------------
	  #---------------------------------------------------------------------
            $Comp = Comp_Load(
              'Form/Input',
              Array(
                'type'   => 'text',
                'name'   => 'AuthInfo',
                'size'   => 20,
		'value'  => $DomainOrder['AuthInfo'],
		'prompt' => 'Пароль (authinfo) домена. Для переноса домена, его необходимо получить у прежнего регистратора.'
              )
            );
            if(Is_Error($Comp))
              return ERROR | @Trigger_Error(500);
            #---------------------------------------------------------------------
            $Table[] = Array('Код переноса домена (AuthInfo)',$Comp);
          #---------------------------------------------------------------------
          #---------------------------------------------------------------------
          #---------------------------------------------------------------------
          $Comp = Comp_Load(
            'Form/Input',
            Array(
              'type'    => 'button',
              'onclick' => "FormEdit('/API/DomainAuthInfoInput','DomainAuthInfoInputForm','Смена пароля (AuthInfo) домена');",
              'value'   => 'Изменить'
            )
          );
          if(Is_Error($Comp))
            return ERROR | @Trigger_Error(500);
          #---------------------------------------------------------------------
          $Table[] = $Comp;
          #---------------------------------------------------------------------
          $Comp = Comp_Load('Tables/Standard',$Table);
          if(Is_Error($Comp))
            return ERROR | @Trigger_Error(500);
          #---------------------------------------------------------------------
          $Form->AddChild($Comp);

#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#-------------------------------------------------------------------------------
$Out = $DOM->Build(FALSE);
#-------------------------------------------------------------------------------
if(Is_Error($Out))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------

?>
