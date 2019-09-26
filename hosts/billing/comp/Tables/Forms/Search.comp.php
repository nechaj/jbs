<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('LinkID','ColumnsIDs');
/******************************************************************************/
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Links = &Links();
# Коллекция ссылок
$Template = &$Links[$LinkID];
/******************************************************************************/
/******************************************************************************/
$Search = '';
#-------------------------------------------------------------------------------
$Session = &$Template['Session'];
#-------------------------------------------------------------------------------
if(IsSet($Session['Search']))
	$Search = $Session['Search'];
#-------------------------------------------------------------------------------
$Args = Args();
#-------------------------------------------------------------------------------
if(IsSet($Args['Search']))
	$Search = $Args['Search'];
#-------------------------------------------------------------------------------
if($Search){
	#-------------------------------------------------------------------------------
	$dSearch = DB_Escape(SPrintF('%%%s%%',$Search));
	#-------------------------------------------------------------------------------
	$Variants = Array();
	#-------------------------------------------------------------------------------
	foreach($ColumnsIDs as $ColumnID) {
		#-------------------------------------------------------------------------------
		$ColumnID = Preg_Match('/^[a-zA-Z0-9]+$/', $ColumnID) ? SPrintF('`%s`',$ColumnID) : $ColumnID;
		#-------------------------------------------------------------------------------
		$Variants[] = SPrintF("%s LIKE '%s'", $ColumnID, $dSearch);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Query = Implode(' OR ', $Variants);
	#-------------------------------------------------------------------------------
	$Where = SPrintF('( %s )', $Query);
	#-------------------------------------------------------------------------------
	$Users = DB_Select('Users','ID',Array('Where'=>SPrintF("`Name` LIKE '%s' OR (SELECT COUNT(*) FROM `Contacts` WHERE `Contacts`.`UserID` = `Users`.`ID` AND `Address` LIKE '%s') > 0",$dSearch,$dSearch)));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Users)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		# No more...
		break;
	case 'array':
		#-------------------------------------------------------------------------------
		$UsersIDs = Array();
		#-------------------------------------------------------------------------------
		foreach($Users as $User)
			$UsersIDs[] = $User['ID'];
		#-------------------------------------------------------------------------------
		$Where = SPrintF('(%s OR `%s` IN (%s))',$Where,(In_Array('UserID',$ColumnsIDs)?'UserID':'ID'),Implode(',',$UsersIDs));
		#-------------------------------------------------------------------------------
		if(In_Array('TargetUserID',$ColumnsIDs))
			$Where = SPrintF('(%s OR `TargetUserID` IN (%s))',$Where,Implode(',',$UsersIDs));
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	$Template['Source']['Adding']['Where'][] = $Where;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Session['Search'] = $Search;
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY');
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('name'=>'Search','size'=>15,'onkeydown'=>'if(IsEnter(event)){ TableSuperSearch(); }','type'=>'text','value'=>$Search));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody->AddChild($Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('onclick'=>'TableSuperSearch();','type'=>'button','value'=>'Найти'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody->AddChild($Comp);
#-------------------------------------------------------------------------------
$Div = new Tag('DIV', Array('style'=>'cursor:pointer;','OnClick'=>'s = document.forms.TableSuperForm.Search; s.value = \'\'; s.focus();'),'Поиск');
#-------------------------------------------------------------------------------
$Table = Array(Array($Div,$NoBody));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
//return $Comp;
// непонятно какого хрена оно цепляет идентфикатор к тегу TD уровнем выше... фича чтоле
return new Tag('NOBODY',Array('id'=>'TableSuperFormSearch'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
