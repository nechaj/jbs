<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Adding');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
$CacheID = Md5(SPrintF('%s:%u',$__FILE__,$__USER['ID']));
#-------------------------------------------------------------------------------
$Result = CacheManager::get($CacheID);
if($Result)
	return $Result;
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Where = Array(
		"`StatusID` = 'Working' OR `StatusID` = 'Newest'","`Flags` != 'CloseOnSee'",
		"(SELECT `IsDepartment` FROM `Groups` WHERE `Groups`.`ID` = `Edesks`.`TargetGroupID`) = 'yes'",
		"(SELECT `IsDepartment` FROM `Groups` WHERE `Groups`.`ID` = (SELECT `GroupID` FROM `Users` WHERE `Users`.`ID` = `Edesks`.`UserID`)) = 'no'"
		);
#-------------------------------------------------------------------------------
$Count = DB_Count('Edesks',Array('Where'=>$Where));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($Count)
	$NoBody->AddChild(new Tag('A',Array('href'=>'/Administrator/Tickets','class'=>'Image'),new Tag('IMG',Array('alt'=>'Новые сообщения','border'=>0,'width'=>13,'height'=>9,'src'=>'SRC:{Images/Icons/Message1.gif}'))));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Count($NoBody->Childs)){
	#-------------------------------------------------------------------------------
	$NoBody->AddChild(new Tag('SPAN',$Adding));
	#-------------------------------------------------------------------------------
	$Adding = $NoBody;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
CacheManager::add($CacheID,$Adding,60);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Adding;
#-------------------------------------------------------------------------------

?>