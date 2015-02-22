<?php

#-------------------------------------------------------------------------------
/** @author Sergey Sedov (for www.host-food.ru) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$Theme = "Проверка баланса счета регистратора";
$Message = "";
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('classes/DomainServer.class.php','libs/BillManager.php','libs/Server.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Servers = DB_Select('Servers',Array('ID','Params'),Array('Where'=>Array('`IsActive` = "yes"','(SELECT `ServiceID` FROM `ServersGroups` WHERE `Servers`.`ServersGroupID` = `ServersGroups`.`ID`) = 20000')));
#-------------------------------------------------------------------------------
switch(ValueOf($Servers)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	# No more...
	Debug("[comp/Tasks/GC/CheckBalance]: Регистраторы не найдены");
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'array':
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'] = Array();
	#-------------------------------------------------------------------------------
	foreach($Servers as $NowReg){
		#-------------------------------------------------------------------------------
		$GLOBALS['TaskReturnInfo'][] = $NowReg['Params']['Name'];
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Tasks/GC/CheckBalance]: Проверка баланса для %s (ID %d, тип %s)',$NowReg['Params']['Name'],$NowReg['ID'],$NowReg['Params']['SystemID']));
		#-------------------------------------------------------------------------------
		$Server = new DomainServer();
		#-------------------------------------------------------------------------------
		$IsSelected = $Server->Select((integer)$NowReg['ID']);
		#-------------------------------------------------------------------------------
		switch(ValueOf($IsSelected)){
			case 'error':
				return ERROR | @Trigger_Error(500);
			case 'exception':
				return new gException('TRANSFER_TO_OPERATOR','Задание не может быть выполнено автоматически и передано оператору');
			case 'true':
				break;
			default:
				return new gException('WRONG_STATUS','Регистратор не определён');
		}
		#-------------------------------------------------------------------------------
		$Balance = $Server->GetBalance();
		#-------------------------------------------------------------------------------
		switch(ValueOf($Balance)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			#-------------------------------------------------------------------------------
			switch($Balance->CodeID){
			case 'REGISTRATOR_ERROR':
				#-------------------------------------------------------------------------------
				Debug(SPrintF('[comp/Tasks/GC/CheckBalance]: %s: %s',$NowReg['Params']['Name'],$Balance->String));
				#-------------------------------------------------------------------------------
				break;
				#-------------------------------------------------------------------------------
			default:
				#-------------------------------------------------------------------------------
				Debug(SPrintF('[comp/Tasks/GC/CheckBalance]: Для регистратора %s (ID %d, тип %s) проверка баланса счета не реализована.',$NowReg['Params']['Name'],$NowReg['ID'],$NowReg['Params']['SystemID']));
				#-------------------------------------------------------------------------------
				$Message .= SPrintF("Для регистратора %s (ID %d, тип %s) проверка баланса счета не реализована. \n",$NowReg['Params']['Name'],$NowReg['ID'],$NowReg['Params']['SystemID']);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		case 'array':
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/Tasks/GC/CheckBalance]: Регистратор (%s), баланс: %s',$NowReg['Params']['Name'],$Balance['Prepay']));
			#-------------------------------------------------------------------------------
			if((float)$Balance['Prepay'] < IntVal($NowReg['Params']['BalanceLowLimit'])){
				#-------------------------------------------------------------------------------
				Debug(SPrintF('[comp/Tasks/GC/CheckBalance]: Баланс (%s) ниже порога уведомления',$NowReg['Params']['Name']));
				#-------------------------------------------------------------------------------
				$Message .= SPrintF("Остаток на счете регистратора %s ниже допустимого минимума - %01.2f\n",$NowReg['Params']['Name'],$Balance['Prepay']);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			return new gException('WRONG_STATUS','Задание не может быть в данном статусе');
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
        return ERROR | @Trigger_Error(101);
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# баланс ISPsystem
$Settings = SelectServerSettingsByService(51000);
#Debug(SPrintF('[comp/Tasks/GC/CheckBalance]: Settings = %s',print_r($Settings,true)));
switch(ValueOf($Settings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	Debug(SPrintF('[comp/Tasks/GC/CheckBalance]: Исключение при поиске сервера, Settings = %s',print_r($Settings,true)));
	break;
	case 'array':
		#-------------------------------------------------------------------------------
		if(IntVal($Settings['Params']['BalanceLowLimit']) > 0){
			#-------------------------------------------------------------------------------
			# получаем баланс
			$Balances = BillManager_Get_Balance($Settings);
			Debug("[comp/Tasks/GC/CheckBalance]: " . print_r($Balances, true) );
			#-------------------------------------------------------------------------------
			foreach($Balances as $Balance){
				#-------------------------------------------------------------------------------
				if(IsSet($Balance['project']) && $Balance['project'] == 'ISPsystem'){
					#-------------------------------------------------------------------------------
					Debug(SPrintF('[comp/Tasks/GC/CheckBalance]: %s / %s',$Balance['project'],$Balance['balance']));
					#-------------------------------------------------------------------------------
					#-------------------------------------------------------------------------------
					if((double)$Balance['balance'] < IntVal($Settings['Params']['BalanceLowLimit'])){
						#-------------------------------------------------------------------------------
						Debug(SPrintF('[comp/Tasks/GC/CheckBalance]: add to message: %s / %s',$Balance['project'],$Balance['balance']));
						#-------------------------------------------------------------------------------
						$Message .= SPrintF("Остаток на счете ISPsystem ниже допустимого минимума - %s \n",$Balance['balance']);
						#-------------------------------------------------------------------------------
					}
					#-------------------------------------------------------------------------------
				}
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# баланс SMS машинки
$ServerSettings = SelectServerSettingsByTemplate('SMS');
#-------------------------------------------------------------------------------
switch(ValueOf($ServerSettings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	break;
case 'array':
	#-------------------------------------------------------------------------------
	if(IntVal($ServerSettings['Params']['BalanceLowLimit']) > 0){
		#-------------------------------------------------------------------------------
		if(Is_Error(System_Load(SPrintF('classes/%s.class.php', $ServerSettings['Params']['Provider']))))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$SMS = new $ServerSettings['Params']['Provider']($ServerSettings['Login'],$ServerSettings['Password'],$ServerSettings['Params']['ApiKey'],$ServerSettings['Params']['Sender']);
		if (Is_Error($SMS))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$IsAuth = $SMS->balance('rur');
		#-------------------------------------------------------------------------------
	        switch (ValueOf($IsAuth)){
		case 'true':
			#-------------------------------------------------------------------------------
			$Balance = (double)$SMS->balance;
			Debug(SPrintF('[comp/Tasks/GC/CheckBalance]: баланс SMS шлюза "%s" равен: %s',$ServerSettings['Params']['Provider'],$Balance));
			#-------------------------------------------------------------------------------
			if($Balance < IntVal($ServerSettings['Params']['BalanceLowLimit'])){
				#-------------------------------------------------------------------------------
				Debug(SPrintF('[comp/Tasks/GC/CheckBalance]: SMS provider low balance = %s',$Balance));
				$Message .= SPrintF("Остаток на счете SMS шлюза \"%s\" ниже допустимого минимума: %01.2f руб.\n",$ServerSettings['Params']['Provider'],$Balance);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			break;
		}
		#-------------------------------------------------------------------------------
	}
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# если нет сообщения, то нефига и отсылать пустое
if(StrLen($Message) < 10)
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# ищщем сторудников бухгалтерии
$Entrance = Tree_Entrance('Groups',3200000);
#-------------------------------------------------------------------
switch(ValueOf($Entrance)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	#---------------------------------------------------------------
	$String = Implode(',',$Entrance);
	#---------------------------------------------------------------
	$Employers = DB_Select('Users','ID',Array('Where'=>SPrintF('`GroupID` IN (%s)',$String)));
	#---------------------------------------------------------------
	switch(ValueOf($Employers)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		# найти всех сотрудников, раз нет сотрудников в бухгалтерии
		$Entrance = Tree_Entrance('Groups',3000000);
		#-------------------------------------------------------------------
		switch(ValueOf($Entrance)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			return ERROR | @Trigger_Error(400);
		case 'array':
			#---------------------------------------------------------------
			$String = Implode(',',$Entrance);
			#---------------------------------------------------------------
			$Employers = DB_Select('Users','ID',Array('Where'=>SPrintF('`GroupID` IN (%s)',$String)));
			#---------------------------------------------------------------
			switch(ValueOf($Employers)){
			case 'error':
				return ERROR | @Trigger_Error(500);
			case 'exception':
				return ERROR | @Trigger_Error(400);
			case 'array':
				Debug(SPrintF("[comp/Tasks/GC/CheckBalance]: найдено %s сотрудников любых отделов",SizeOf($Employers)));
				break;
			default:
				return ERROR | @Trigger_Error(101);
			}
			break;
		default:
			return ERROR | @Trigger_Error(101);
		}
		break;
	case 'array':
		Debug(SPrintF("[comp/Tasks/GC/CheckBalance]: найдено %s сотрудников отдела бухгалтерии",SizeOf($Employers)));
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#---------------------------------------------------------
#---------------------------------------------------------
foreach($Employers as $Employer){
	#---------------------------------------------------------
	$msg = new DispatchMsg(Array('Theme'=>$Theme,'Message'=>$Message), (integer)$Employer['ID'], 100 /*$FromID*/);
    	$IsSend = NotificationManager::sendMsg($msg);
	#---------------------------------------------------------
	switch(ValueOf($IsSend)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		# No more...
	case 'true':
		# No more...
		Debug(SPrintF("[comp/Tasks/GC/CheckBalance]: Сообщение для сотрудника #%s отослано",$Employer['ID']));
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
}
#---------------------------------------------------------
#---------------------------------------------------------
return TRUE;


?>
