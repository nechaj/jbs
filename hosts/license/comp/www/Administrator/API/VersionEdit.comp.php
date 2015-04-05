
#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$VersionID  = (integer) @$Args['VersionID'];
$CreateDate = (integer) @$Args['CreateDate'];
$Name       =  (string) @$Args['Name'];
$StatusID   =  (string) @$Args['StatusID'];
$Comment    =  (string) @$Args['Comment'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($Name){
  #-----------------------------------------------------------------------------
  if(!Preg_Match('/([0-9]+.[0-9]+.[0-9]+.[0-9]+)/',$Name))
    return new gException('WRONG_NAME','Имя версии указано неверно');
}
#-------------------------------------------------------------------------------
$IVersion = Array(
  #-----------------------------------------------------------------------------
  'CreateDate' => $CreateDate,
  'UserID'     => $GLOBALS['__USER']['ID'],
  'Name'       => $Name,
  'Comment'    => $Comment
);
#-------------------------------------------------------------------------------
if($VersionID){
  #-----------------------------------------------------------------------------
  $IsUpdate = DB_Update('Versions',$IVersion,Array('ID'=>$VersionID));
  if(Is_Error($IsUpdate))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
}else{
  #-----------------------------------------------------------------------------
  $VersionID = DB_Insert('Versions',$IVersion);
  if(Is_Error($VersionID))
    return ERROR | @Trigger_Error(500);
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>'Versions','StatusID'=>$StatusID,'RowsIDs'=>$VersionID));
#-------------------------------------------------------------------------------
switch(ValueOf($Comp)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return ERROR | @Trigger_Error(400);
  case 'array':
    # No more...
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok');
#-------------------------------------------------------------------------------