<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Name','UserID','UniqID','IsDisabled','Prompt');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Null($UserID))
  $UserID = 1;
#-------------------------------------------------------------------------------
$User = DB_Select('Users',Array('ID','GroupID','Email','Name'),Array('UNIQ','ID'=>$UserID));
#-------------------------------------------------------------------------------
switch(ValueOf($User)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return ERROR | @Trigger_Error(400);
  case 'array':
    #---------------------------------------------------------------------------
    $NoBody = new Tag('NOBODY');
    #---------------------------------------------------------------------------
    if(Is_Null($UniqID))
      $UniqID = UniqID('ID');
    #---------------------------------------------------------------------------
    $Comp = Comp_Load(
      'Form/Input',
      Array(
        'name'    => $UniqID,
        'onfocus' => "value='';",
        'onclick' => SPrintF("AutoComplite(this,GetPosition(this),'/Administrator/AutoComplite/UserID',function(Text,Value){form.%s.value = Text;form.%s.value = Value;});",$UniqID,$Name),
        'type'    => 'text',
        'value'   => SPrintF('%s (%s)',$User['Email'],$User['Name']),
	'prompt'  => $Prompt?$Prompt:'Для поиска пользователя введите первые буквы его имени или email адреса',
	'style'   => 'width:100%'
      )
    );
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    if($IsDisabled)
      $Comp->AddAttribs(Array('disabled'=>TRUE));
    #---------------------------------------------------------------------------
    if($User['GroupID'] != 4000000)
      $Comp->AddAttribs(Array('value'=>SPrintF('%s (%s)',$User['Email'],$User['Name'])));
    #---------------------------------------------------------------------------
    $NoBody->AddChild($Comp);
    #---------------------------------------------------------------------------
    $Comp = Comp_Load(
      'Form/Input',
      Array(
        'name'  => $Name,
        'type'  => 'hidden',
        'value' => $User['ID']
      )
    );
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $NoBody->AddChild($Comp);
    #---------------------------------------------------------------------------
    return $NoBody;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>