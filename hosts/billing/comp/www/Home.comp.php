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
$Eval = (string) @$Args['Eval'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','libs/Tree.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Base')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($Eval){
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Config = Config();
	$Settings = $Config['Other']['Eval'];
	#-------------------------------------------------------------------------------
	if($Settings['IsActive']){
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Result = Crypt_Decode(str_replace(" ",'+',$Eval),$Settings['EvalKey']);
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		if(!Is_Error($Result))
			$DOM->AddAttribs('Body',Array('onload'=>$Result));
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Домашняя страница');
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Widgets','User','100%',400);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Comp);
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/OrderManage.js}')));
#-------------------------------------------------------------------------------
$Out = $DOM->Build();
#-------------------------------------------------------------------------------
if(Is_Error($Out))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Out;
#-------------------------------------------------------------------------------

?>