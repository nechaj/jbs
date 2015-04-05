<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
$__args_list = Array('Args');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(!IsSet($Args))
  $Args = Args();
#-------------------------------------------------------------------------------
$TableID = (string) @$Args['TableID'];
$RowsIDs =  (array) @$Args['RowsIDs'];
#-------------------------------------------------------------------------------
$Regulars = Regulars();
#-------------------------------------------------------------------------------
if(!Preg_Match($Regulars['ID'],$TableID))
  return ERROR | @Trigger_Error(201);
#-------------------------------------------------------------------------------
if(Count($RowsIDs) < 1)
  return new gException('ROWS_NOT_SELECTED','Записи для удаления не выбраны');
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Array = Array();
#-------------------------------------------------------------------------------
foreach($RowsIDs as $RowID)
  $Array[] = (integer)$RowID;
#-------------------------------------------------------------------------------
$Where = SPrintF('`ID` IN (%s)',Implode(',',$Array));
#-------------------------------------------------------------------------------
$Rows = DB_Select(SPrintF('%sOwners',$TableID),'*',Array('Where'=>$Where));
#-------------------------------------------------------------------------------
switch(ValueOf($Rows)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return new gException('ROWS_NOT_FOUND','Записи не найдены');
  case 'array':
    #-------------------------------TRANSACTION---------------------------------
    if(Is_Error(DB_Transaction($TransactionID = UniqID('Delete'))))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Trigger = System_Element(SPrintF('comp/%s.comp.php',$Path = SPrintF('Triggers/OnDelete/%s',$TableID)));
    #---------------------------------------------------------------------------
    $RowsIDs = Array();
    #---------------------------------------------------------------------------
    foreach($Rows as $Row){
      #-------------------------------------------------------------------------
      $IsPermission = Permission_Check(SPrintF('%sDelete',$TableID),(integer)$GLOBALS['__USER']['ID'],(integer)$Row['UserID']);
      #-------------------------------------------------------------------------
      switch(ValueOf($IsPermission)){
        case 'error':
          return ERROR | @Trigger_Error(500);
        case 'exception':
          return ERROR | @Trigger_Error(400);
        case 'false':
          return ERROR | @Trigger_Error(700);
        case 'true':
          #---------------------------------------------------------------------
          if(!Is_Error($Trigger)){
            #-------------------------------------------------------------------
            $OnDelete = Comp_Load($Path,$Row,COMP_ALL_HOSTS);
            #-------------------------------------------------------------------
            switch(ValueOf($OnDelete)){
              case 'error':
                return ERROR | @Trigger_Error(500);
              case 'array':
                #---------------------------------------------------------------
                foreach($OnDelete as $Result){
                  #-------------------------------------------------------------
                  switch(ValueOf($Result)){
                    case 'exception':
                      return new gException('CAN_NOT_DELETE','Не удалось удалить выбранные записи',$Result);
                    case 'true':
                      # No more...
                    break;
                    default:
                      return ERROR | @Trigger_Error(101);
                  }
                }
              break;
              default:
                return ERROR | @Trigger_Error(101);
            }
          }
          #---------------------------------------------------------------------
          $RowsIDs[] = $Row['ID'];
        break;
        default:
          return ERROR | @Trigger_Error(101);
      }
    }
    #---------------------------------------------------------------------------
    $IsDelete = DB_Delete($TableID,Array('Where'=>$Where));
    if(Is_Error($IsDelete))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $IsDelete = DB_Delete('StatusesHistory',Array('Where'=>Array(SPrintF("`ModeID` = '%s'",$TableID),SPrintF('`RowID` IN (%s)',Implode(',',$Array)))));
    if(Is_Error($IsDelete))
      return ERROR | @Trigger_Error(500);
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