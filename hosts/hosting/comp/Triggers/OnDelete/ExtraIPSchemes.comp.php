<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('ExtraIPScheme');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Count = DB_Count('ExtraIPOrders',Array('Where'=>SPrintF('`SchemeID` = %u',$ExtraIPScheme['ID'])));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($Count)
	return new gException('DELETE_DENIED',SPrintF('Удаление тарифа (%s) не возможно, %u заказ(ов) на ExtraIP используют данный тариф',$ExtraIPScheme['Name'],$Count));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
