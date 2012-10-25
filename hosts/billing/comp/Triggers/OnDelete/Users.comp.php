<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('User');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
// проверяем, не системный ли
if($User['ID'] < 2001)
	return new gException('USER_CAN_NOT_DELETED',SPrintF('Пользователь [%s] не может быть удален, поскольку он системный',$User['Email']));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверяем наличие любых счетов
$Count = DB_Count('InvoicesOwners',Array('Where'=>SPrintF("`UserID` = %u",$User['ID'])));
if($Count)
	return new gException('USER_HAVE_INVOICES',SPrintF('Пользователь [%s] не может быть удален, поскольку у него есть счета на оплату',$User['Email']));
# проверяем наличие оплаченных счетов
$Count = DB_Count('InvoicesOwners',Array('Where'=>SPrintF("`StatusID` = 'Payed' AND `UserID` = %u",$User['ID'])));
if($Count)
	return new gException('USER_HAVE_PAYED_INVOICES',SPrintF('Пользователь [%s] не может быть удален, поскольку у него есть оплаченные счета',$User['Email']));
# проверяем наличие заказов
$Count = DB_Count('OrdersOwners',Array('Where'=>SPrintF("`UserID` = %u",$User['ID'])));
if($Count)
	return new gException('USER_HAVE_INVOICES',SPrintF('Пользователь [%s] не может быть удален, поскольку у него есть заказанные услуги',$User['Email']));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// удаляем события этого юзера
$Where = SPrintF('`UserID` = %u',$User['ID']);
$IsDelete = DB_Delete('Events',Array('Where'=>$Where));
if(Is_Error($IsDelete))
	return new gException('USERs_EVENTS_CAN_NOT_DELETED',SPrintF('Не удалось удалить события пользователя [%s]',$User['Email']));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Personal = DB_Select('Users',Array('ID','Email'),Array('UNIQ','ID'=>$GLOBALS['__USER']['ID']));
#-------------------------------------------------------------------------------
switch(ValueOf($Personal)){
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
$Event = Array(
		'UserID'        => 1,
		'PriorityID'    => 'Billing',
		'Text'          => SPrintF('Соотрудником %s удалён пользователь %s',$Personal['Email'],$User['Email'])
		);

$Event = Comp_Load('Events/EventInsert',$Event);
if(!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------

?>
