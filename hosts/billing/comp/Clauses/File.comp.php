<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('FileID');
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$ClauseFile = DB_Select('ClausesFiles',Array('ID','FileName','Comment'),Array('UNIQ','ID'=>$FileID));
#-------------------------------------------------------------------------------
switch(ValueOf($ClauseFile)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return new Tag('SPAN',SPrintF('Файл не найден (%s)',$FileID));
  break;
  case 'array':
    #---------------------------------------------------------------------------
    $FileName = $ClauseFile['FileName'];
    #---------------------------------------------------------------------------
    $Mime = SubStr($FileName,StrRiPos($FileName,'.')+1);
    #---------------------------------------------------------------------------
    $Element = SPrintF('Images/Mime/%s.gif',$Mime);
    #---------------------------------------------------------------------------
    if(Is_Error(Styles_Element($Element)))
      $Element = 'Images/Mime/unknown.gif';
    #---------------------------------------------------------------------------
    $Img = new Tag('IMG',Array('title'=>$ClauseFile['Comment'],'border'=>0,'width'=>48,'height'=>48,'src'=>SPrintF('SRC:{%s}',$Element)));
    #---------------------------------------------------------------------------
    $Table = new Tag('TABLE',new Tag('TR',new Tag('TD',Array('align'=>'center'),new Tag('A',Array('class'=>'Image','href'=>SPrintF('/ClauseFileDownload?ClauseFileID=%s',$ClauseFile['ID'])),$Img))));
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Formats/String',$FileName,10);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Table->AddChild(new Tag('TR',new Tag('TD',Array('align'=>'center'),new Tag('FONT',Array('size'=>'1'),$Comp))));
    #---------------------------------------------------------------------------
    return $Table;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>