<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = IsSet($Args)?$Args:Args();
#-------------------------------------------------------------------------------
$WorkCompliteID = (integer) @$Args['WorkCompliteID'];
$ContractID     = (integer) @$Args['ContractID'];
$Month          = (integer) @$Args['Month'];
$ServiceID      = (integer) @$Args['ServiceID'];
$Comment        =  (string) @$Args['Comment'];
$Amount         = (integer) @$Args['Amount'];
$Cost           =  (double) @$Args['Cost'];
$Discont        =  (double) @$Args['Discont'];
$IsPostingMake	= (boolean) @$Args['IsPostingMake'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Count = DB_Count('Contracts',Array('ID'=>$ContractID));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!$Count)
	return new gException('CONTRACT_NOT_FOUND','Договор клиента не найден');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IWorkComplite = Array(
			'ContractID'	=> $ContractID,
			'Month'		=> $Month,
			'ServiceID'	=> $ServiceID,
			'Comment'	=> $Comment,
			'Amount'	=> $Amount,
			'Cost'		=> $Cost,
			'Discont'	=> $Discont/100
			);
#-------------------------------------------------------------------------------
#------------------------------TRANSACTION--------------------------------------
if(Is_Error(DB_Transaction($TransactionID = UniqID('WorkCompliteEdit'))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($WorkCompliteID){
	#-------------------------------------------------------------------------------
	$IsUpdate = DB_Update('WorksComplite',$IWorkComplite,Array('ID'=>$WorkCompliteID));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	if($IsPostingMake){
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load(
				'www/Administrator/API/PostingMake',
				Array(
					'ContractID'    => $ContractID,
					'ServiceID'     => $ServiceID,
					'Comment'       => $Comment,
					'Summ'          => $Amount * $Cost * (100 - $Discont) / 100
					)
				);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$IsInsert = DB_Insert('WorksComplite',$IWorkComplite);
	if(Is_Error($IsInsert))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error(DB_Commit($TransactionID)))
	return ERROR | @Trigger_Error(500);
#-------------------------END TRANSACTION---------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>