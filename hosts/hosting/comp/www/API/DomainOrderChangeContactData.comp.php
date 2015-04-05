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
$Email         =  (string) @$Args['Email'];
$Phone         =  (string) @$Args['Phone'];
$CellPhone     =  (string) @$Args['CellPhone'];
$PostalAddress =  (string) @$Args['PostalAddress'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DomainServer.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DomainOrder = DB_Select('DomainOrdersOwners',Array('*','(SELECT `Name` FROM `DomainSchemes` WHERE `DomainSchemes`.`ID` = `DomainOrdersOwners`.`SchemeID`) as `DomainZone`'),Array('UNIQ','ID'=>$DomainOrderID));
#-------------------------------------------------------------------------------
switch(ValueOf($DomainOrder)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return new gException('DOMAIN_ORDER_NOT_FOUND','Выбранный заказ не найден');
  case 'array':
    #---------------------------------------------------------------------------
    $__USER = $GLOBALS['__USER'];
    #---------------------------------------------------------------------------
    $IsPermission = Permission_Check('DomainOrdersChangeContactData',(integer)$__USER['ID'],(integer)$DomainOrder['UserID']);
    #---------------------------------------------------------------------------
    switch(ValueOf($IsPermission)){
      case 'error':
        return ERROR | @Trigger_Error(500);
      case 'exception':
        return ERROR | @Trigger_Error(400);
      case 'false':
        return ERROR | @Trigger_Error(700);
      case 'true':
        #-----------------------------------------------------------------------
        $DomainOrderID = (integer)$DomainOrder['ID'];
        #-----------------------------------------------------------------------
        if($DomainOrder['StatusID'] != 'Active')
          return new gException('ORDER_IS_NOT_ACTIVE','Невозможно изменить данные для неактивного домена');
        #-----------------------------------------------------------------------
        $DomainScheme = DB_Select('DomainSchemes','*',Array('UNIQ','ID'=>$DomainOrder['SchemeID']));
        #-----------------------------------------------------------------------
        switch(ValueOf($DomainScheme)){
          case 'error':
            return ERROR | @Trigger_Error(500);
          case 'exception':
            return ERROR | @Trigger_Error(400);
          case 'array':
            #-------------------------------------------------------------------
            $Regulars = Regulars();
            #-------------------------------------------------------------------
            $Domain = SPrintF('%s.%s',$DomainOrder['DomainName'],$DomainScheme['Name']);
            #-------------------------------------------------------------------
            #-------------------------------------------------------------------
            $Person = Array();
            #-------------------------------------------------------------------
            if($Email){
              if(!Preg_Match($Regulars['Email'],$Email)){
                return new gException('WRONG_EMAIL','Введён некорректный почтовый адрес');
              }
              $Person['Email'] = $Email;
            }
            #-------------------------------------------------------------------
            if($Phone){
              if(!Preg_Match($Regulars['Phone'],$Phone)){
                return new gException('WRONG_PHONE','Введён некорректный телефон');
              }
              $Person['Phone'] = $Phone;
            }
            #-------------------------------------------------------------------
            if($CellPhone){
              if(!Preg_Match($Regulars['Phone'],$CellPhone)){
                return new gException('WRONG_CELLPHONE','Введён некорректный мобильный телефон');
              }
              $Person['CellPhone'] = $CellPhone;
            }

            #-------------------------------------------------------------------
            if($PostalAddress){
              if(StrLen($PostalAddress) < 10){
                return new gException('WRONG_POSTAL_ADDRESS','Введён некорректный почтовый адрес');
              }
              $Person['PostalAddress'] = $PostalAddress;
            }

            #-------------------------------------------------------------------
            if(!Count($Person))
	      return new gException('NO_INPUT_DATA','Необходимо ввести хоть какие-то данные для изменения');
            #-------------------------------------------------------------------
            #-------------------------------------------------------------------
            $Server = new DomainServer();
            #---------------------------------------------------------------------------
            $IsSelected = $Server->Select((integer)$DomainOrder['ServerID']);
            #---------------------------------------------------------------------------
            switch(ValueOf($IsSelected)){
            case 'error':
              return ERROR | @Trigger_Error(500);
            case 'exception':
              return ERROR | @Trigger_Error(400);
            case 'true':
              break;
            default:
              return ERROR | @Trigger_Error(101);
            }
            #---------------------------------------------------------------------------
            $ChangeContactDetail = $Server->ChangeContactDetail($Domain,$DomainOrder['DomainZone'],$Person);
            switch(ValueOf($ChangeContactDetail)){
            case 'error':
              return ERROR | @Trigger_Error(500);
            case 'exception':
              return new gException('CANNOT_UPDATE_CONTACT_DATA','Не удалось обновить контактные данные у регистратора');
            case 'array':
              break;
            default:
              return ERROR | @Trigger_Error(101);
            }
            #---------------------------------------------------------------------------
            #---------------------------------------------------------------------------
            return Array('Status'=>'Ok','DomainOrderID'=>$DomainOrderID);
            #---------------------------------------------------------------------------
          default:
             return ERROR | @Trigger_Error(101);
        }
      default:
        return ERROR | @Trigger_Error(101);
    }
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>