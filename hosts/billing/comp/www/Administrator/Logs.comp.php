<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('classes/DOM.class.php','modules/Authorisation.mod')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
#-------------------------------------------------------------------------------
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Base')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddAttribs('MenuLeft',Array('args'=>'Administrator/AddIns'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Дополнения → Обслуживание системы → Логи системы');
#-------------------------------------------------------------------------------
$Tmp = System_Element('tmp');
if(Is_Error($Tmp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Logs = SPrintF('%s/logs',$Tmp);
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY');
#-------------------------------------------------------------------------------
if(File_Exists($Logs)){
  #-----------------------------------------------------------------------------
  $Files = IO_Scan($Logs);
  if(Is_Error($Files))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Table = $Options = Array();
  #-----------------------------------------------------------------------------
  if(Count($Files)){
    #---------------------------------------------------------------------------
    Sort($Files);
    #---------------------------------------------------------------------------
    foreach($Files as $File)
      $Options[$File] = $File;
  }
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load('Form/Select',Array('name'=>'Log'),$Options);
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Table[] = Array('Текущий лог',$Comp);
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'type'  => 'text',
      'size'  => 5,
      'name'  => 'Lines',
      'value' => 15
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Table[] = Array('Кол-во строк вывода',$Comp);
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'type' => 'text',
      'name' => 'Search'
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Table[] = Array('Строка поиска',$Comp);
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'type'  => 'checkbox',
      'name'  => 'IsWrap',
      'value' => 'yes'
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsWrap\'); return false;'),'Построчный перенос'),$Comp);
  #-----------------------------------------------------------------------------
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'type'  => 'submit',
      'value' => 'Обновить'
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Table[] = $Comp;
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load('Tables/Standard',$Table);
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Form = new Tag('FORM',Array('name'=>'LogsForm','action'=>'LogScan','target'=>'LogScan','method'=>'POST'),$Comp);
  #-----------------------------------------------------------------------------
  $NoBody->AddChild($Form);
  #-----------------------------------------------------------------------------
  $IFrame = new Tag('IFRAME',Array('name'=>'LogScan','src'=>'/Administrator/LogScan?Log=http-send.log','width'=>'100%','height'=>'240px'),'Загрузка...');
  #-----------------------------------------------------------------------------
  $NoBody->AddChild($IFrame);
}else{
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load('Information','Логи системы пока еще не ведутся.','Notice');
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $NoBody->AddChild($Comp);
}
#-------------------------------------------------------------------------------
#$Comp = Comp_Load('Tab','Administrator/Billing',$NoBody);
#if(Is_Error($Comp))
#  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$NoBody);
#-------------------------------------------------------------------------------
$Out = $DOM->Build();
#-------------------------------------------------------------------------------
if(Is_Error($Out))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return $Out;
#-------------------------------------------------------------------------------

?>