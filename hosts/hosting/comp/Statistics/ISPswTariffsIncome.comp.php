<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('IsCreate','Folder');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('libs/Artichow.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Result = Array('Title'=>'Распределение доходов по тарифам на ПО ISPsystem');
#-------------------------------------------------------------------------------
if(!$IsCreate)
  return $Result;
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY');
#-------------------------------------------------------------------------------
$NoBody->AddChild(new Tag('P','Данный вид статистики содержит информацию о доходности каждого из тарифов ПО ISPsystem за 1 мес.'));
$NoBody->AddChild(new Tag('P','Суммируются цены за месяц тарифов всех активных заказов'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Tables = Array('ISPswOrders','ISPswSchemes');
$Columns = Array('SUM(`CostDay`*`MinDaysPay`) as `Income`','Name');
$Condition = Array(
			'Where'	 =>Array(
					'`ISPswSchemes`.`ID` = `ISPswOrders`.`SchemeID`',
					'`ISPswOrders`.`StatusID`="Active"'
					),
			'GroupBy'=>'SchemeID',
			'SortOn' =>'Name'
		);
#-------------------------------------------------------------------------------
$Incomes = DB_Select($Tables,$Columns,$Condition);
#-------------------------------------------------------------------------------
switch(ValueOf($Incomes)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return $Result;
  case 'array':
   #----------------------------------------------------------------------------
   $Balance = 0;
   #----------------------------------------------------------------------------
   $Params = $Labels = Array();
   #----------------------------------------------------------------------------
   $Table = Array(Array(new Tag('TD',Array('class'=>'Head'),'Тариф'),new Tag('TD',Array('class'=>'Head'),'Доход')));
   #----------------------------------------------------------------------------
   foreach($Incomes as $Income){
     #--------------------------------------------------------------------------
     $Balance += $Income['Income'];
     #--------------------------------------------------------------------------
     $Params[] = $Income['Income'];
     $Labels[] = $Income['Name'];
     #--------------------------------------------------------------------------
     $Summ = Comp_Load('Formats/Currency',$Income['Income']);
     if(Is_Error($Summ))
       return ERROR | @Trigger_Error(500);
     #--------------------------------------------------------------------------
     $Table[] = Array($Income['Name'],$Summ);
   }
   #----------------------------------------------------------------------------
   $Comp = Comp_Load('Formats/Currency',$Balance);
   if(Is_Error($Comp))
     return ERROR | @Trigger_Error(500);
   #----------------------------------------------------------------------------
   $Table[] = Array(new Tag('TD',Array('colspan'=>3,'class'=>'Standard','align'=>'right'),SPrintF('Общий доход: %s',$Comp)));
   #----------------------------------------------------------------------------
   $Comp = Comp_Load('Tables/Extended',$Table);
   if(Is_Error($Comp))
     return ERROR | @Trigger_Error(500);
   #----------------------------------------------------------------------------
   $NoBody->AddChild($Comp);
   #----------------------------------------------------------------------------
   if(Count($Params) > 1){
     #-------------------------------------------------------------------------
     $File = SPrintF('%s.jpg',Md5('ISPswIncome1'));
     #-------------------------------------------------------------------------
     Artichow_Pie('Распределение доходов по тарифам',SPrintF('%s/%s',$Folder,$File),$Params,$Labels);
     #-------------------------------------------------------------------------
     $NoBody->AddChild(new Tag('BR'));
     #-------------------------------------------------------------------------
     $NoBody->AddChild(new Tag('IMG',Array('src'=>$File)));
   }
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Result['DOM'] = $NoBody;
#-------------------------------------------------------------------------------
return $Result;

?>
