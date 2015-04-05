<?php
#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
#-------------------------------------------------------------------------------

function DB_Query($Query){
  /****************************************************************************/
  $__args_types = Array('string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Link = &Link_Get('DB');
  #-----------------------------------------------------------------------------
  if(!Is_Object($Link)){
    #---------------------------------------------------------------------------
    $Config = Config();
    #---------------------------------------------------------------------------
    $Link = new MySQL($Config['DBConnection']);
    #---------------------------------------------------------------------------
    if(Is_Error($Link->Open())){
      #-------------------------------------------------------------------------
      $Link = NULL;
      #-------------------------------------------------------------------------
      return ERROR | @Trigger_Error('[DB_Query]: невозможно соединиться с базой данных');
    }
    #---------------------------------------------------------------------------
    if(Is_Error($Link->SelectDB())){
      #-------------------------------------------------------------------------
      $Link = NULL;
      #-------------------------------------------------------------------------
      return ERROR | @Trigger_Error('[DB_Query]: невозможно выбрать базу данных');
    }
  }
  #-----------------------------------------------------------------------------
  #-----------------------------------------------------------------------------
  List($Micro,$Seconds) = Explode(' ',MicroTime());
  $StartTime = $Micro + $Seconds;
  #-----------------------------------------------------------------------------
  $Result = $Link->Query($Query);
  #-----------------------------------------------------------------------------
  List($Micro,$Seconds) = Explode(' ',MicroTime());
  $EndTime = $Micro + $Seconds;
  $GLOBALS['__TIME_MYSQL'] = $GLOBALS['__TIME_MYSQL'] + $EndTime - $StartTime;
  #-----------------------------------------------------------------------------
  #-----------------------------------------------------------------------------
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[DB_Query]: невозможно осуществить запрос');
  #-----------------------------------------------------------------------------
  $GLOBALS['__COUNTER_MYSQL']++;
  return $Result;
}

function DB_Escape($String){
  /****************************************************************************/
  #$__args_types = Array('string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Link = &Link_Get('DB');
  #-----------------------------------------------------------------------------
  if(!Is_Object($Link)){
    #---------------------------------------------------------------------------
    $Config = Config();
    #---------------------------------------------------------------------------
    $Link = new MySQL($Config['DBConnection']);
    #---------------------------------------------------------------------------
    if(Is_Error($Link->Open())){
      #-------------------------------------------------------------------------
      $Link = NULL;
      #-------------------------------------------------------------------------
      return ERROR | @Trigger_Error('[DB_Query]: невозможно соединиться с базой данных');
    }
    #---------------------------------------------------------------------------
    if(Is_Error($Link->SelectDB())){
      #-------------------------------------------------------------------------
      $Link = NULL;
      #-------------------------------------------------------------------------
      return ERROR | @Trigger_Error('[DB_Query]: невозможно выбрать базу данных');
    }
  }
  #-----------------------------------------------------------------------------
  #-----------------------------------------------------------------------------
  return MySQL_Real_Escape_String($String);
}
#-------------------------------------------------------------------------------
function DB_Types($Row,$ActionID = 'Compress'){
  /****************************************************************************/
  $__args_types = Array('array','string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Types = System_XML('config/TypesDB.xml');
  if(!Is_Error($Types)){
    #---------------------------------------------------------------------------
    foreach(Array_Keys($Types) as $ColumnID){
      #-------------------------------------------------------------------------
      $Type = $Types[$ColumnID];
      #-------------------------------------------------------------------------
      if(!IsSet($Row[$ColumnID]))
        continue;
      #-------------------------------------------------------------------------
      $Column = &$Row[$ColumnID];
      #-------------------------------------------------------------------------
      if(Is_Null($Column))
        continue;
      #-------------------------------------------------------------------------
      $Column = Comp_Load(SPrintF('Formats/%s/%s',$ActionID,$Type),$Column);
      if(Is_Error($Column))
        return ERROR | @Trigger_Error('[DB_Types]: не удалось отформатировать значение');
    }
  }
  #-----------------------------------------------------------------------------
  return $Row;
}
#-------------------------------------------------------------------------------
function DB_Select($TablesIDs,$ColumnsIDs = '*',$Query = Array()){
  /****************************************************************************/
  $__args_types = Array('string,array','string,array','array','boolean');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  if(!$TablesIDs)
    return ERROR | @Trigger_Error('[DB_Select]: имена таблиц не указаны');
  #-----------------------------------------------------------------------------
  $Default = Array('GroupBy'=>'','SortOn'=>'','IsDesc'=>FALSE,'Limits'=>Array());
  #-----------------------------------------------------------------------------
  Array_Union($Default,$Query);
  #-----------------------------------------------------------------------------
  $Query = $Default;
  #-----------------------------------------------------------------------------
  if(!Is_Array($TablesIDs))
    $TablesIDs = Array($TablesIDs);
  #-----------------------------------------------------------------------------
  $Array = Array();
  #-----------------------------------------------------------------------------
  foreach($TablesIDs as $TableID)
    $Array[] = (Preg_Match('/^[a-zA-Z]+$/u',$TableID)?SPrintF('`%s`',$TableID):$TableID);
  #-----------------------------------------------------------------------------
  $TablesIDs = $Array;
  #-----------------------------------------------------------------------------
  if(!Is_Array($ColumnsIDs))
    $ColumnsIDs = Array($ColumnsIDs);
  #-----------------------------------------------------------------------------
  $Array = Array();
  #-----------------------------------------------------------------------------
  foreach($ColumnsIDs as $ColumnID)
    $Array[] = (Preg_Match('/^[a-zA-Z]+$/u',$ColumnID)?SPrintF('`%s`',$ColumnID):$ColumnID);
  #-----------------------------------------------------------------------------
  $ColumnsIDs = $Array;
  #-----------------------------------------------------------------------------
  $Sql = SPrintF('SELECT %s FROM %s',Implode(',',$ColumnsIDs),Implode(',',$TablesIDs));
  #-----------------------------------------------------------------------------
  if($Where = DB_Where($Query))
    $Sql = SPrintF('%s WHERE %s',$Sql,$Where);
  #-----------------------------------------------------------------------------
  $GroupBy = $Query['GroupBy'];
  #-----------------------------------------------------------------------------
  if($GroupBy){
    #---------------------------------------------------------------------------
    if(!Is_Array($GroupBy))
      $GroupBy = Array($GroupBy);
    #---------------------------------------------------------------------------
    $Array = Array();
    #---------------------------------------------------------------------------
    foreach($GroupBy as $ColumnID)
      $Array[] = SPrintF('`%s`',$ColumnID);
    #---------------------------------------------------------------------------
    $GroupBy = $Array;
    #---------------------------------------------------------------------------
    $Sql = SPrintF('%s GROUP BY %s',$Sql,Implode(',',$GroupBy));
  }
  #-----------------------------------------------------------------------------
  $SortOn = $Query['SortOn'];
  #-----------------------------------------------------------------------------
  if($SortOn){
    #---------------------------------------------------------------------------
    if(!Is_Array($SortOn))
      $SortOn = Array($SortOn);
    #---------------------------------------------------------------------------
    $Array = Array();
    #---------------------------------------------------------------------------
    foreach($SortOn as $ColumnID){
       #-------------------------------------------------------------------------
      $ColumnID = DB_Escape($ColumnID);
      #-------------------------------------------------------------------------
      $Array[] = StrPos($ColumnID,'.')?$ColumnID:SPrintF('`%s`',$ColumnID);
      #Debug(SPrintF('[system/libs/auto/DB]: SortOn = %s',$ColumnID));
      #$Array[] = $ColumnID;
    }
    #---------------------------------------------------------------------------
    $Sql = SPrintF('%s ORDER BY %s',$Sql,Implode(',',$Array));
  }
  #-----------------------------------------------------------------------------
  if($Query['IsDesc'])
    $Sql = SPrintF('%s DESC',$Sql);
  #-----------------------------------------------------------------------------
  $Limits = $Query['Limits'];
  if(Count($Limits) > 1)
    $Sql = SPrintF('%s LIMIT %s, %s',$Sql,Current($Limits),Next($Limits));
  #-----------------------------------------------------------------------------
  $CacheID = SPrintF('[DB_Select]:%s',Md5($Sql));
  #-----------------------------------------------------------------------------
  if(!$Rows = Cache_Get($CacheID)){
    #---------------------------------------------------------------------------
    $Result = DB_Query($Sql);
    #---------------------------------------------------------------------------
    switch(ValueOf($Result)){
      case 'error':
        return ERROR | @Trigger_Error('[DB_Select]: невозможно осуществить запрос');
      case 'resource':
        #-----------------------------------------------------------------------
        $Rows = MySQL::Result($Result);
        #-----------------------------------------------------------------------
        if(Count($Rows) < 1)
          return new gException('ROWS_NOT_FOUND','Записи не найдены');
        #-----------------------------------------------------------------------
        $NoTypesDB = Link_Get('NoTypesDB','boolean');
        #-----------------------------------------------------------------------
        if(!$NoTypesDB){
          #---------------------------------------------------------------------
          for($i=0;$i<Count($Rows);$i++){
            #-------------------------------------------------------------------
            $Row = &$Rows[$i];
            #-------------------------------------------------------------------
            $Row = DB_Types($Row,'Explode');
            if(Is_Error($Row))
              return ERROR | @Trigger_Error('[DB_Select]: не удалось произвести преобразование типов');
          }
        }
        #-----------------------------------------------------------------------
        Cache_Add($CacheID,$Rows);
      break;
      default:
        return ERROR | @Trigger_Error(101);
    }
  }
  #-----------------------------------------------------------------------------
  if(In_Array('UNIQ',$Query,TRUE)){
    #---------------------------------------------------------------------------
    if(Count($Rows) > 1)
      return ERROR | @Trigger_Error('[DB_Select]: запись не является уникальной');
    #---------------------------------------------------------------------------
    $Rows = Current($Rows);
  }
  #-----------------------------------------------------------------------------
  return $Rows;
}
#-------------------------------------------------------------------------------
function DB_Update($TableID,$Columns,$Query = Array()){
  /****************************************************************************/
  $__args_types = Array('string','array');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $NoTypesDB = Link_Get('NoTypesDB','boolean');
  #-----------------------------------------------------------------------------
  if(!$NoTypesDB){
    #---------------------------------------------------------------------------
    $Columns = DB_Types($Columns);
    if(Is_Error($Columns))
      return ERROR | @Trigger_Error('[DB_Update]: не удалось произвести преобразование типов');
  }
  #-----------------------------------------------------------------------------
  $Array = Array();
  #-----------------------------------------------------------------------------
  $ColumnsIDs = Array_Keys($Columns);
  #-----------------------------------------------------------------------------
  foreach($ColumnsIDs as $ColumnID){
    #---------------------------------------------------------------------------
    $Column = $Columns[$ColumnID];
    #---------------------------------------------------------------------------
    $Array[] = SPrintF('`%s` = %s',$ColumnID,Is_Null($Column)?'NULL':SPrintF("'%s'",DB_Escape($Column)));
  }
  #-----------------------------------------------------------------------------
  $String = Implode(',',$Array);
  #-----------------------------------------------------------------------------
  $Sql = SPrintF('UPDATE `%s` SET %s',$TableID,$String);
  #-----------------------------------------------------------------------------
  if($Where = DB_Where($Query))
    $Sql = SPrintF('%s WHERE %s',$Sql,$Where);
  #-----------------------------------------------------------------------------
  $Result = DB_Query($Sql);
  #-----------------------------------------------------------------------------
  switch(ValueOf($Result)){
    case 'error':
      return ERROR | @Trigger_Error('[DB_Update]: не возможно осуществить запрос');
    case 'true':
      #-------------------------------------------------------------------------
      Cache_Delete('[DB_Select]');
      #-------------------------------------------------------------------------
      return TRUE;
    default:
      return ERROR | @Trigger_Error(101);
  }
}
#-------------------------------------------------------------------------------
function DB_Insert($TableID,$Columns){
  /****************************************************************************/
  $__args_types = Array('string','array');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Columns = DB_Types($Columns);
  if(Is_Error($Columns))
    return ERROR | @Trigger_Error('[DB_Insert]: не удалось произвести преобразование типов');
  #-----------------------------------------------------------------------------
  $Names = Array();
  #-----------------------------------------------------------------------------
  $ColumnsIDs = Array_Keys($Columns);
  #-----------------------------------------------------------------------------
  foreach($ColumnsIDs as $ColumnID){
    $Names[] = SPrintF('`%s`',$ColumnID);
  }
  #-----------------------------------------------------------------------------
  $String = Implode(',',$Names);
  #-----------------------------------------------------------------------------
  $Sql = SPrintF('INSERT INTO `%s` (%s)',$TableID,$String);
  #-----------------------------------------------------------------------------
  $Values = Array();
  #-----------------------------------------------------------------------------
  foreach($ColumnsIDs as $ColumnID){
    #---------------------------------------------------------------------------
    $Column = $Columns[$ColumnID];
    #---------------------------------------------------------------------------
    $Values[] = (Is_Null($Column)?'NULL':SPrintF("'%s'",DB_Escape($Column)));
  }
  #-----------------------------------------------------------------------------
  $Sql = SPrintF("%s VALUES ( %s )",$Sql,Implode(',',$Values));
  #-----------------------------------------------------------------------------
  $Result = DB_Query($Sql);
  #-----------------------------------------------------------------------------
  switch(ValueOf($Result)){
    case 'error':
      return ERROR | @Trigger_Error('[DB_Insert]: невозможно осуществить запрос для вставки записи');
    case 'true':
      #-------------------------------------------------------------------------
      if(IsSet($Columns['ID'])){
        #-----------------------------------------------------------------------
        return (integer)$Columns['ID'];
      }else{
        #-----------------------------------------------------------------------
        $Result = DB_Query('SELECT LAST_INSERT_ID()');
        #-----------------------------------------------------------------------
        switch(ValueOf($Result)){
          case 'error':
            return ERROR | @Trigger_Error('[DB_Insert]: невозможно осуществить запрос по взятию идентификатора записи');
          case 'resource':
            #-------------------------------------------------------------------
            Cache_Delete('[DB_Select]');
            #-------------------------------------------------------------------
            $Result = Mysql_Fetch_Row($Result);
            #-------------------------------------------------------------------
            return (integer)Current($Result);
          default:
            return ERROR | @Trigger_Error(101);
        }
      }
    default:
      return ERROR | @Trigger_Error(101);
  }
}
#-------------------------------------------------------------------------------
function DB_Delete($TableID,$Query = Array()){
  /****************************************************************************/
  $__args_types = Array('string','array');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  if(!$Where = DB_Where($Query))
    return ERROR | @Trigger_Error('[DB_Delete]: попытка удаления всех записей');
  #-----------------------------------------------------------------------------
  $Sql = SPrintF('DELETE FROM `%s` WHERE %s',$TableID,$Where);
  #-----------------------------------------------------------------------------
  $Result = DB_Query($Sql);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[DB_Delete]: невозможно осуществить запрос');
  #-----------------------------------------------------------------------------
  Cache_Delete('[DB_Select]');
  #-----------------------------------------------------------------------------
  return TRUE;
}
#-------------------------------------------------------------------------------
function DB_Count($TablesIDs,$Query = Array()){
  /****************************************************************************/
  $__args_types = Array('string,array','array');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  if(!Is_Array($TablesIDs))
    $TablesIDs = Array($TablesIDs);
  #-----------------------------------------------------------------------------
  $Sql = SPrintF('SELECT COUNT(*) FROM %s',Implode(',',$TablesIDs));
  #-----------------------------------------------------------------------------
  if($Where = DB_Where($Query))
    $Sql = SPrintF('%s WHERE %s',$Sql,$Where);

  if(isSet($Query['GroupBy'])) {
    $Sql = SPrintF('%s GROUP BY %s', $Sql, $Query['GroupBy']);
  }
  
  $Result = DB_Query($Sql);
  #-----------------------------------------------------------------------------
  switch(ValueOf($Result)){
    case 'error':
      return ERROR | @Trigger_Error('[DB_Count]: невозможно осуществить запрос');
    case 'resource':
      #-------------------------------------------------------------------------
      if (isSet($Query['GroupBy'])) {
            return mysql_num_rows($Result);
      }
      else {
          $Result = MySQL_Fetch_Row($Result);
          
          return (int)Current($Result);
      }
    default:
      return ERROR | @Trigger_Error(101);
  }
}
#-------------------------------------------------------------------------------
function DB_Transaction($TransactionID){
  /****************************************************************************/
  $__args_types = Array('string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Transactions = &Link_Get('Transactions','array');
  #-----------------------------------------------------------------------------
  if(Count($Transactions)){
    #---------------------------------------------------------------------------
    $Result = DB_Query(SPrintF('SAVEPOINT `%s`',$TransactionID));
    #---------------------------------------------------------------------------
    switch(ValueOf($Result)){
      case 'error':
        return ERROR | @Trigger_Error('[DB_Transaction]: невозможно установить именованную точку начала транзакции');
      case 'true':
        #-----------------------------------------------------------------------
        $Transactions[] = $TransactionID;
        #-----------------------------------------------------------------------
        return TRUE;
      default:
        return ERROR | @Trigger_Error(101);
    }
  }
  #-----------------------------------------------------------------------------
  $Result = DB_Query('SET AUTOCOMMIT=0');
  #-----------------------------------------------------------------------------
  switch(ValueOf($Result)){
    case 'error':
      return ERROR | @Trigger_Error('[DB_Transaction]: невозможно осуществить запрос для установки режима транзакций');
    case 'true':
      #-------------------------------------------------------------------------
      $Result = DB_Query('BEGIN');
      #-------------------------------------------------------------------------
      switch(ValueOf($Result)){
        case 'error':
          return ERROR | @Trigger_Error('[DB_Transaction]: невозможно осуществить запрос для начала транзакции');
        case 'true':
          #---------------------------------------------------------------------
          $Transactions[] = $TransactionID;
          #---------------------------------------------------------------------
          return TRUE;
        default:
          return ERROR | @Trigger_Error(101);
      }
    default:
      return ERROR | @Trigger_Error(101);
  }
}
#-------------------------------------------------------------------------------
function DB_Roll($TransactionID){
  /****************************************************************************/
  $__args_types = Array('string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Transactions = &Link_Get('Transactions','array');
  #-----------------------------------------------------------------------------
  if(!Count($Transactions))
    return ERROR | @Trigger_Error('[DB_Roll]: транзакций не обнаружено');
  #-----------------------------------------------------------------------------
  $Index = Array_Search($TransactionID,$Transactions);
  #-----------------------------------------------------------------------------
  if($Index === FALSE)
    return ERROR | @Trigger_Error(SPrintF('[DB_Roll]: точка отката транзакции (%s) не найдена',$TransactionID));
  #-----------------------------------------------------------------------------
  $IsEnd = ($Index < 1);
  #-----------------------------------------------------------------------------
  $Sql = ($IsEnd?'ROLLBACK':SPrintF('ROLLBACK TO SAVEPOINT `%s`',$TransactionID));
  #-----------------------------------------------------------------------------
  Debug(SPrintF('[DB_Roll]: откат до транзакции [%s]',$TransactionID));
  #-----------------------------------------------------------------------------
  $Transactions = Array_Slice($Transactions,0,$Index);
  #-----------------------------------------------------------------------------
  Debug(SPrintF('[DB_Roll]: текущие транзакции [%s]',Count($Transactions)?Implode(',',$Transactions):'нет'));
  #-----------------------------------------------------------------------------
  $Result = DB_Query($Sql);
  #-----------------------------------------------------------------------------
  switch(ValueOf($Result)){
    case 'error':
      return ERROR | @Trigger_Error('[DB_Roll]: невозможно откатить транзакцию');
    case 'true':
      #-------------------------------------------------------------------------
      if($IsEnd){
        #-----------------------------------------------------------------------
        $Result = DB_Query('SET AUTOCOMMIT=1');
        #-----------------------------------------------------------------------
        switch(ValueOf($Result)){
          case 'error':
            return ERROR | @Trigger_Error('[DB_Roll]: невозможно осуществить запрос для установки режима транзакций');
          case 'true':
            # No more...
          break;
          default:
            return ERROR | @Trigger_Error(101);
        }
      }
      #-------------------------------------------------------------------------
      return TRUE;
    default:
      return ERROR | @Trigger_Error(101);
  }
}
#-------------------------------------------------------------------------------
function DB_Commit($TransactionID){
  /****************************************************************************/
  $__args_types = Array('string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Transactions = &Link_Get('Transactions','array');
  #-----------------------------------------------------------------------------
  if(!Count($Transactions))
    return ERROR | @Trigger_Error('[DB_Commit]: транзакций не обнаружено');
  #-----------------------------------------------------------------------------
  if($Transactions[Count($Transactions) - 1] != $TransactionID)
    return ERROR | @Trigger_Error(SPrintF('[DB_Commit]: точка применения транзакции (%s) не найдена',$TransactionID));
  #-----------------------------------------------------------------------------
  if(Count($Transactions) < 2){
    #---------------------------------------------------------------------------
    $Result = DB_Query('COMMIT');
    #---------------------------------------------------------------------------
    switch(ValueOf($Result)){
      case 'error':
        return ERROR | @Trigger_Error('[DB_Commit]: невозможно завершить транзакцию');
      case 'true':
        #-----------------------------------------------------------------------
        $Result = DB_Query('SET AUTOCOMMIT=1');
        #-----------------------------------------------------------------------
        switch(ValueOf($Result)){
          case 'error':
            return ERROR | @Trigger_Error('[DB_Commit]: невозможно осуществить запрос для установки режима транзакций');
          case 'true':
            # No more...
          break 2;
          default:
            return ERROR | @Trigger_Error(101);
        }
      default:
        return ERROR | @Trigger_Error(101);
    }
  }
  #-----------------------------------------------------------------------------
  Array_Pop($Transactions);
  #-----------------------------------------------------------------------------
  return TRUE;
}
#-------------------------------------------------------------------------------
function DB_Where($Query = Array(),$Logic = 'AND'){
  /****************************************************************************/
  $__args_types = Array('array','string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Default = Array('ID'=>'','Where'=>'');
  #-----------------------------------------------------------------------------
  Array_Union($Default,$Query);
  #-----------------------------------------------------------------------------
  $Query = $Default;
  #-----------------------------------------------------------------------------
  $IDs = $Query['ID'];
  #-----------------------------------------------------------------------------
  if($IDs)
    return SPrintF('`ID` = %s',(integer)$IDs);
  #-----------------------------------------------------------------------------
  $Where = $Query['Where'];
  #-----------------------------------------------------------------------------
  if($Where){
    #---------------------------------------------------------------------------
    if(!Is_Array($Where))
      $Where = Array($Where);
    #---------------------------------------------------------------------------
    $Array = Array();
    #---------------------------------------------------------------------------
    foreach($Where as $Condition)
      $Array[] = SPrintF('(%s)',$Condition);
    #---------------------------------------------------------------------------
    return Implode(SPrintF(' %s ',$Logic),$Array);
  }
  #-----------------------------------------------------------------------------
  return FALSE;
}
#-------------------------------------------------------------------------------
?>