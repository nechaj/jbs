<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Table','ID','OldComp','Value','Length','AdminNotice','UserNotice','IsDisabled');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
#Debug(SPrintF('[comp/Formats/Order/Notice]: Table = %s; ID = %s; OldComp = %s; Value = %s; Length = %s; AdminNotice = %s; UserNotice = %s;',$Table,$ID,$OldComp,$Value,$Length,$AdminNotice,$UserNotice));
$Tr = new Tag('TR');
#-------------------------------------------------------------------------------
# user notice
#-------------------------------------------------------------------------------
$Comp = Comp_Load('UserNotice',$Table,$ID,$UserNotice,$IsDisabled);
#-------------------------------------------------------------------------------
if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD',$Comp));
#-------------------------------------------------------------------------------
if($GLOBALS['__USER']['IsAdmin']){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Notice',$Table,$ID,$AdminNotice);
	#-------------------------------------------------------------------------------
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Tr->AddChild(new Tag('TD',$Comp));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# original value
$Comp = Comp_Load($OldComp,$Value,$Length);
#-------------------------------------------------------------------------------
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD',$Comp));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return new Tag('TABLE',Array('cellspacing'=>2,'cellpadding'=>0),$Tr);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
