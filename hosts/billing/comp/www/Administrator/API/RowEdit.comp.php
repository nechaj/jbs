<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$TableID =  (string) @$Args['TableID'];
$RowID   = (integer) @$Args['RowID'];
#-------------------------------------------------------------------------------
$Regulars = Regulars();
#-------------------------------------------------------------------------------
if(!Preg_Match($Regulars['ID'],$TableID))
  return ERROR | @Trigger_Error(201);
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','libs/Upload.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Result = DB_Query(SPrintF('SHOW COLUMNS FROM `%s`',$TableID));
if(Is_Error($Result))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Rows = MySQL::Result($Result);
if(Is_Error($Rows))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$ColumnsTypes = Array();
#------------------------------------------------------------------------------
foreach($Rows as $Row)
  $ColumnsTypes[$Row['Field']] = $Row;
#-------------------------------------------------------------------------------
$NoTypesDB = &Link_Get('NoTypesDB','boolean');
#-------------------------------------------------------------------------------
$NoTypesDB = TRUE;
#-------------------------------------------------------------------------------
$Row = DB_Select($TableID,'*',Array('UNIQ','ID'=>$RowID));
#-------------------------------------------------------------------------------
switch(ValueOf($Row)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return ERROR | @Trigger_Error(400);
  case 'array':
    #---------------------------------------------------------------------------
    $URow = Array();
    #---------------------------------------------------------------------------
    foreach(Array_Keys($Row) as $ColumnID){
      #-------------------------------------------------------------------------
      if(!IsSet($Args[$ColumnID]))
        continue;
      #-------------------------------------------------------------------------
      $Column = $Args[$ColumnID];
      #-------------------------------------------------------------------------
      if($Column == '[NULL]')
        $Column = NULL;
      #-------------------------------------------------------------------------
      $TypeID = $ColumnsTypes[$ColumnID]['Type'];
      #-------------------------------------------------------------------------
      if(Preg_Match('/blob/',$TypeID)){
        #-----------------------------------------------------------------------
        $Upload = Upload_Get($ColumnID);
        #-----------------------------------------------------------------------
        switch(ValueOf($Upload)){
          case 'error':
            return ERROR | @Trigger_Error(500);
          case 'exception':
            # No more...
          break;
          case 'array':
            $URow[$ColumnID] = GzCompress($Upload['Data']);
          break;
          default:
            return ERROR | @Trigger_Error(101);
        }
        #-----------------------------------------------------------------------
      }else
        $URow[$ColumnID] = $Column;
    }
    #---------------------------------------------------------------------------
    $IsUpdate = DB_Update($TableID,$URow,Array('ID'=>$Row['ID']));
    if(Is_Error($IsUpdate)){
      #-------------------------------------------------------------------------
      $Link = &Link_Get('DB');
      #-------------------------------------------------------------------------
      return new gException('QUERY_ERROR',$Link->GetError());
    }
    #---------------------------------------------------------------------------
    return Array('Status'=>'Ok');
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>