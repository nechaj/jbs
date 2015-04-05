<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('IsCreate','Folder','StartDate','FinishDate','Details');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('libs/Artichow.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Result = Array('Title'=>'Распределение доменов по серверам DNS');
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY');
#-------------------------------------------------------------------------------
$NoBody->AddChild(new Tag('P','Данный вид статистики содержит информацию о используемых DNS серверах конкурентов'));
#-------------------------------------------------------------------------------
if(!$IsCreate)
	return $Result;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Servers = DB_Select('Servers',Array('ID','Params'),Array('Where'=>'(SELECT `ServiceID` FROM `ServersGroups` WHERE `ServersGroups`.`ID` = `Servers`.`ServersGroupID`) = 20000'));
#-------------------------------------------------------------------------------
switch(ValueOf($Servers)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return $Result;
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Where = Array();
#-------------------------------------------------------------------------------
foreach($Servers as $Server)
	$Where[] = '`Ns1Name` NOT LIKE "%' . SubStr($Server['Params']['Ns1Name'], StrPos($Server['Params']['Ns1Name'], '.') + 1, StrLen($Server['Params']['Ns1Name'])) . '%"';
#-------------------------------------------------------------------------------
$Where[] = '`Ns1Name` != ""';
$Where[] = '`StatusID` = "Active"';
$Where[] = '`Ns1Name` NOT LIKE CONCAT ("%",`DomainName`,".",`Name`)';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Columns = Array(
		'SUBSTRING_INDEX(`Ns1Name`, ".", -2) AS Address',
		'COUNT(*) AS Count',
		);
#-------------------------------------------------------------------------------
$DNSs = DB_Select('DomainOrdersOwners',$Columns,Array('Where'=>$Where,'SortOn'=>'Count','IsDesc'=>TRUE,'GroupBy'=>'Address'));
#-------------------------------------------------------------------------------
switch(ValueOf($DNSs)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return $Result;
  case 'array':
    #---------------------------------------------------------------------------
    $Params = $Labels = Array();
    #---------------------------------------------------------------------------
    $Table = Array(Array(new Tag('TD',Array('class'=>'Head'),'Провайдер'),new Tag('TD',Array('class'=>'Head'),'Кол-во доменов')));
    #---------------------------------------------------------------------------
    foreach($DNSs as $DNS){
      #-------------------------------------------------------------------------
      $Params[] = $DNS['Count'];
      $Labels[] = $DNS['Address'];
      #-------------------------------------------------------------------------
      $Table[] = Array($DNS['Address'],(integer)$DNS['Count']);
    }
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Tables/Extended',$Table);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $NoBody->AddChild($Comp);
    #---------------------------------------------------------------------------
    if(Count($Params) > 1){
      #-------------------------------------------------------------------------
      $File = SPrintF('%s.jpg',Md5('DNS1'));
      #-------------------------------------------------------------------------$output = array_slice($input, 0, 3);   // "a", "b", "c"
      Artichow_Pie('Распределение заказов по чужим DNS',SPrintF('%s/%s',$Folder,$File),Array_Slice($Params,0,16),Array_Slice($Labels,0,16));
      #-------------------------------------------------------------------------
      $NoBody->AddChild(new Tag('BR'));
      #-------------------------------------------------------------------------
      $NoBody->AddChild(new Tag('IMG',Array('src'=>$File)));
    }
    #---------------------------------------------------------------------------
    $Result['DOM'] = $NoBody;
    #---------------------------------------------------------------------------
    return $Result;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>