<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Attribs','Value');
/******************************************************************************/
Eval(COMP_INIT);
#******************************************************************************#
#******************************************************************************#
$TextArea = new Tag('TEXTAREA');
#-------------------------------------------------------------------------------
if(IsSet($Attribs['prompt'])){
  #-----------------------------------------------------------------------------
  $Prompt = $Attribs['prompt'];
  #-----------------------------------------------------------------------------
  UnSet($Attribs['prompt']);
  #-----------------------------------------------------------------------------
  $LinkID = UniqID('TextArea');
  #-----------------------------------------------------------------------------
  $Links = &Links();
  #-----------------------------------------------------------------------------
  $Links[$LinkID] = &$TextArea;
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load('Form/Prompt',$LinkID,$Prompt);
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  UnSet($Links[$LinkID]);
}
#-------------------------------------------------------------------------------
$TextArea->AddAttribs($Attribs);
#-------------------------------------------------------------------------------
if(!Is_Null($Value))
  $TextArea->AddText($Value);
#-------------------------------------------------------------------------------
return $TextArea;
#-------------------------------------------------------------------------------

?>