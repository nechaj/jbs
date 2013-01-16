<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('LinkID','ColumnID');
/******************************************************************************/
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Links = &Links();
# Коллекция ссылок
$Template = &$Links[$LinkID];
/******************************************************************************/
/******************************************************************************/
$Args = &Args();
#-------------------------------------------------------------------------------
$Date1 = (integer)@$Args[SPrintF('%s1',$ColumnID)];
#-------------------------------------------------------------------------------
if($Date1){
  #-----------------------------------------------------------------------------
  $Where = SPrintF('%u < `%s`',MkTime(0,0,0,Date('n',$Date1),Date('j',$Date1),Date('Y',$Date1)),$ColumnID);
  #-----------------------------------------------------------------------------
  $Template['Source']['Adding']['Where'][] = $Where;
}
#-------------------------------------------------------------------------------
$Date2 = (integer)@$Args[SPrintF('%s2',$ColumnID)];
#-------------------------------------------------------------------------------
if($Date2){
  #-----------------------------------------------------------------------------
  $Where = SPrintF('`%s` < %u',$ColumnID,MkTime(23,59,59,Date('n',$Date2),Date('j',$Date2),Date('Y',$Date2)));
  #-----------------------------------------------------------------------------
  $Template['Source']['Adding']['Where'][] = $Where;
}
#-------------------------------------------------------------------------------
$Tr = new Tag('TR',new Tag('TD','С даты'));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('jQuery/DatePicker',SPrintF('%s1',$ColumnID),$Date1?$Date1:Time()-189216000);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD',$Comp));
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD','по дату'));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('jQuery/DatePicker',SPrintF('%s2',$ColumnID),$Date2?$Date2:Time());
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD',$Comp));
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'onclick' => 'TableSuperReload();',
    'type'    => 'button',
    'value'   => 'Вывести'
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD',$Comp));
#-------------------------------------------------------------------------------
$Table = new Tag('TABLE',Array('class'=>'Standard','cellspacing'=>5),$Tr);
#-------------------------------------------------------------------------------
return $Table;
#-------------------------------------------------------------------------------

?>
