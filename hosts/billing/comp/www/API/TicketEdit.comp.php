<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
$__args_list = Array('Args');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
#-------------------------------------------------------------------------------
if(!IsSet($Args)){
	if(Is_Error(System_Load('modules/Authorisation.mod')))
		return ERROR | @Trigger_Error(500);
	$Args = Args();
}
#-------------------------------------------------------------------------------
$Theme         =  (string) @$Args['Theme'];
$TargetGroupID = (integer) @$Args['TargetGroupID'];
$TargetUserID  = (integer) @$Args['TargetUserID'];
$PriorityID    =  (string) @$Args['PriorityID'];
$Message       =  (string) @$Args['Message'];
$UserID        = (integer) @$Args['UserID'];
$Flags         =  (string) @$Args['Flags'];
$NotifyEmail   =  (string) @$Args['NotifyEmail'];
#-------------------------------------------------------------------------------
# truncate $Theme & $Message
$Theme		= substr($Theme, 0, 127);
$Message	= substr($Message, 0, 62000);
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('libs/Upload.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!$Theme)
  return new gException('THEME_IS_EMPTY','Введите тему запроса');
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Priorities = $Config['Edesks']['Priorities'];
#-------------------------------------------------------------------------------
if(!In_Array($PriorityID,Array_Keys($Priorities)))
  return new gException('WRONG_PRIORITY','Неверный приоритет запроса');
#-------------------------------------------------------------------------------
if(!$Message)
  return new gException('MESSAGE_IS_EMPTY','Введите сообщение запроса');
#-------------------------------------------------------------------------------
$Count = DB_Count('Groups',Array('ID'=>$TargetGroupID));
if(Is_Error($Count))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!$Count)
  return new gException('DEPARTAMENT_NOT_FOUND','Отдел запроса не найден');
#-------------------------------------------------------------------------------
$ITicket = Array(
  #-----------------------------------------------------------------------------
  'TargetGroupID' => $TargetGroupID,
  'PriorityID'    => $PriorityID,
  'Theme'         => $Theme,
  'UpdateDate'    => Time(),
  'Flags'	  => $Flags
);
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
if($UserID){
  #-----------------------------------------------------------------------------
  $User = DB_Select('Users','ID',Array('UNIQ','ID'=>$UserID));
  #-----------------------------------------------------------------------------
  switch(ValueOf($User)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      return new gException('USER_NOT_FOUND','Пользователь не найден');
    case 'array':
      #-------------------------------------------------------------------------
      $Permission = Permission_Check('UserRead',(integer)$__USER['ID'],(integer)$User['ID']);
      #-------------------------------------------------------------------------
      switch(ValueOf($Permission)){
        case 'error':
          return ERROR | @Trigger_Error(500);
        case 'exception':
          return ERROR | @Trigger_Error(400);
        case 'false':
          return ERROR | @Trigger_Error(700);
        case 'true':
          $ITicket['UserID'] = $User['ID'];
        break 2;
        default:
          return ERROR | @Trigger_Error(101);
      }
    default:
      return ERROR | @Trigger_Error(101);
  }
}else
  $ITicket['UserID'] = $__USER['ID'];
#-------------------------------------------------------------------------------
if(Is_Error(DB_Transaction($TransactionID = UniqID('TicketEdit'))))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($TargetUserID){
  #-----------------------------------------------------------------------------
  $User = DB_Select('Users','ID',Array('UNIQ','ID'=>$TargetUserID));
  #-----------------------------------------------------------------------------
  switch(ValueOf($User)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      return new gException('WORKER_NOT_FOUND','Сотрудник не найден');
    case 'array':
      #-------------------------------------------------------------------------
      $Permission = Permission_Check('UserRead',(integer)$__USER['ID'],(integer)$User['ID']);
      #-------------------------------------------------------------------------
      switch(ValueOf($Permission)){
        case 'error':
          return ERROR | @Trigger_Error(500);
        case 'exception':
          return ERROR | @Trigger_Error(400);
        case 'false':
          return ERROR | @Trigger_Error(700);
        case 'true':
          $ITicket['TargetUserID'] = $User['ID'];
        break 2;
        default:
          return ERROR | @Trigger_Error(101);
      }
    default:
      return ERROR | @Trigger_Error(101);
  }
}else
  $ITicket['TargetUserID'] = ($UserID?$__USER['ID']:100);
#-------------------------------------------------------------------------------
if(IsSet($NotifyEmail))
  $ITicket['NotifyEmail'] = $NotifyEmail;
#-------------------------------------------------------------------------------
$TicketID = DB_Insert('Edesks',$ITicket);
if(Is_Error($TicketID))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>'Edesks','IsNotNotify'=>TRUE,'IsNoTrigger'=>TRUE,'StatusID'=>($UserID?'Opened':'Newest'),'RowsIDs'=>$TicketID));
#-------------------------------------------------------------------------------
switch(ValueOf($Comp)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return ERROR | @Trigger_Error(400);
  case 'array':
    #---------------------------------------------------------------------------
    $ITicketMessage = Array(
      #-------------------------------------------------------------------------
      'UserID'  => $__USER['ID'],
      'EdeskID' => $TicketID,
      'Content' => $Message
    );
    #---------------------------------------------------------------------------
    $Upload = Upload_Get('TicketMessageFile');
    #---------------------------------------------------------------------------
    switch(ValueOf($Upload)){
      case 'error':
        return ERROR | @Trigger_Error(500);
      case 'exception':
        # No more...
      break;
      case 'array':
        #-----------------------------------------------------------------------
        $ITicketMessage['FileName'] = $Upload['Name'];
      break;
      default:
        return ERROR | @Trigger_Error(101);
    }
    #---------------------------------------------------------------------------
    $MessageID = DB_Insert('EdesksMessages',$ITicketMessage);
    if(Is_Error($MessageID))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    if(IsSet($ITicketMessage['FileName']))
      if(!SaveUploadedFile('EdesksMessages', $MessageID, $Upload['Data']))
        return new gException('CANNOT_SAVE_UPLOADED_FILE','Не удалось сохранить загруженный файл');
    #---------------------------------------------------------------------------
    if(!$UserID){
      #-------------------------------------------------------------------------
      $Event = Array(
			'UserID'	=> $__USER['ID'],
			'PriorityID'	=> 'Billing',
			'Text'		=> SPrintF('Создан новый запрос в службу поддержки с темой (%s)',$Theme)
      		    );
      $Event = Comp_Load('Events/EventInsert',$Event);
      if(!$Event)
        return ERROR | @Trigger_Error(500);
    }
    #---------------------------------------------------------------------------
    if(Is_Error(DB_Commit($TransactionID)))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    return Array('Status'=>'Ok');
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
