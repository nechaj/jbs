<?php
/**
 *
 *  Joonte Billing System
 *
 *  Copyright © 2012 Vitaly Velikodnyy
 *
 */
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
class DomainOrdersForTransferMsg extends Message {
	#-------------------------------------------------------------------------------
	public function __construct(array $params, $toUser) {
	parent::__construct('DomainOrdersForTransfer', $toUser, $params);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
public function getParams(){
	#-------------------------------------------------------------------------------
	# Достаем registrar и его префикс
	if(Preg_Match('/registrar:\s+((\w+|-)+)/', $this->params['WhoIs'], $String)){
		#-------------------------------------------------------------------------------
		$Registrar = Next($String);
		#-------------------------------------------------------------------------------
		if(Preg_Match('/((\w+|-)+)-(REG|RU)/', $Registrar, $String))
			$PrefixRegistrar = Next($String);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# Получаем параметры для формирования Event
	$Where = SPrintF('`OrderID` = %s', $this->params['OrderID']);
	$UserID = DB_Select('DomainOrdersOwners', 'UserID as ID', Array('UNIQ', 'Where' => $Where));
	$SchemeName = DB_Select('DomainSchemes', 'Name as SchemeName', Array('UNIQ', 'ID' => $this->params['SchemeID']));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(In_Array($SchemeName['SchemeName'],Array('su'))){
		#-------------------------------------------------------------------------------
		if(IsSet($Registrar) && IsSet($PrefixRegistrar)){
			#-------------------------------------------------------------------------------
			$this->params['registrar'] = $Registrar;
			$this->params['prefixRegistrar'] = $PrefixRegistrar;
			#-------------------------------------------------------------------------------
			# Получаем параметры регистратора к которому осуществляется трансфер
			$ServerID = DB_Select('DomainSchemes','`ServerID` AS `ID`',Array('UNIQ','ID'=>$this->params['SchemeID']));
			$Settings = DB_Select('Servers','*',Array('UNIQ','ID'=>$ServerID['ID']));
			#-------------------------------------------------------------------------------
			Debug("[system/classes/DomainOrdersForTransferMsg.class.php]: Servers[SystemID] - " . $Settings['Params']['SystemID']);
			Debug("[system/classes/DomainOrdersForTransferMsg.class.php]: Settings[PrefixNic] - " . $Settings['Params']['PrefixNic']);
			Debug("[system/classes/DomainOrdersForTransferMsg.class.php]: Registrar - " . $Registrar);
			Debug("[system/classes/DomainOrdersForTransferMsg.class.php]: PrefixRegistrar - " . $PrefixRegistrar);
			#-------------------------------------------------------------------------------
			# Проверяем является ли регистрар нашим регистратором
			if($PrefixRegistrar == $Settings['Params']['PrefixNic']){
				#-------------------------------------------------------------------------------
				$this->params['internalRegister'] = true;
				#---------------------------------------------------------------------------
				Debug("[system/classes/DomainOrdersForTransferMsg.class.php]: IsOurRegistrar - TRUE");
				Debug("[system/classes/DomainOrdersForTransferMsg.class.php]: Инструкция по трансферу в пределах регистратора");
				#---------------------------------------------------------------------------
				$this->params['registratorID'] = $Settings['Params']['SystemID'];
				$this->params['partnerLogin'] = $Settings['Params']['PartnerLogin'];
				$this->params['partnerContract'] = $Settings['Params']['PartnerContract'];
				#-------------------------------------------------------------------------------
				# Достаем статью с информацией о шаблонах документов и контактами регистратора
				$Where = SPrintF('`Partition` = "Registrators/%s/internal"', $PrefixRegistrar);
				$Clause = DB_Select('Clauses','*',Array('UNIQ','Where'=>$Where));
				switch(ValueOf($Clause)) {
				case 'array':
					#-------------------------------------------------------------------------------
					$TransferDoc = trim(Strip_Tags($Clause['Text']));
					break;
					#-------------------------------------------------------------------------------
				default:
					#-------------------------------------------------------------------------------
					Debug(SPrintF('[system/classes/DomainOrdersForTransferMsg.class.php]: Статья не найдена. Ожидалась Registrators/%s/internal', $PrefixRegistrar));
					#-------------------------------------------------------------------------------
					$TransferDoc = "Для получения информации об оформлении писем Вашему текущему регистратору и его контактах перейдите на его сайт.";
					#-------------------------------------------------------------------------------
					# Уведомление об ошибке статьи
					$Event = Array(
							'UserID'    => $UserID['ID'],
							'PriorityID'=> 'Error',
							'Text'      => SPrintF('Статья по переносу домена не найдена. Ожидалась Registrators/%s/internal',$PrefixRegistrar),
							'IsReaded'  => FALSE
							);
					#-------------------------------------------------------------------------------
					$Event = Comp_Load('Events/EventInsert', $Event);
					if(!$Event)
						return ERROR | @Trigger_Error(500);
					#-------------------------------------------------------------------------------
				}
				#-------------------------------------------------------------------------------
				$this->params['transferDoc'] = $TransferDoc;
				#-------------------------------------------------------------------------------
			}else{
				#-------------------------------------------------------------------------------
				$this->params['internalRegister'] = false;
				#-------------------------------------------------------------------------------
				Debug("[system/classes/DomainOrdersForTransferMsg.class.php]: Инструкция по трансферу от стороннего регистратора");
				#-------------------------------------------------------------------------------
				# Формируем постфикс идентификатора
				switch ($SchemeName['SchemeName']){
				case 'su':
					$PostfixNic = 'FID';
					break;
				case 'рф':
					$PostfixNic = 'RF';
					break;
				default:
					$PostfixNic = 'RU';
				}
				#-------------------------------------------------------------------------------
				$this->params['registrar'] = $Registrar;
				$this->params['registratorID'] = $Settings['Params']['SystemID'];
				$this->params['jurName'] = $Settings['Params']['JurName'];
				$this->params['prefixNic'] = $Settings['Params']['PrefixNic'];
				$this->params['postfixNic'] = $PostfixNic;
				$this->params['schemeName'] = StrToUpper($SchemeName['SchemeName']);
				#-------------------------------------------------------------------------------
				# Достаем статью с информацией о шаблонах документов и контактами регистратора
				$Where = SPrintF('`Partition` = "Registrators/%s/external"',$PrefixRegistrar);
				$Clause = DB_Select('Clauses','*',Array('UNIQ','Where'=>$Where));
				switch(ValueOf($Clause)){
				case 'array':
					$TransferDoc = trim(Strip_Tags($Clause['Text']));
					break;
				default:
					#-------------------------------------------------------------------------------
					Debug(SPrintF('[system/classes/DomainOrdersForTransferMsg.class.php]: Статья не найдена. Ожидалась Registrators/%s/external',$PrefixRegistrar));
					#-------------------------------------------------------------------------------
					$TransferDoc = "\n\nДля получения информации об оформлении писем Вашему текущему регистратору и его контактах перейдите на его сайт.";
					#-------------------------------------------------------------------------------
					# Уведомление об ошибке статьи
					$Event = Array(
							'UserID'	=> $UserID['ID'],
							'PriorityID'	=> 'Error',
							'Text'		=> SPrintF('Статья по переносу домена не найдена. Ожидалась Registrators/%s/external',$PrefixRegistrar),
							'IsReaded'	=> FALSE
							);
					$Event = Comp_Load('Events/EventInsert',$Event);
					if(!$Event)
						return ERROR | @Trigger_Error(500);
					#-------------------------------------------------------------------------------
				}
				#-------------------------------------------------------------------------------
				$this->params['transferDoc'] = $TransferDoc;
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			Debug("[system/classes/DomainOrdersForTransferMsg.class.php]: Registrar или PrefixRegistrar не был определён.");
			# Уведомление об ошибке формирования инструкции
			$Event = Array (
					'UserID'    => $UserID['ID'],
					'PriorityID'=> 'Error',
					'Text'      => SPrintF('Ошибка автоматического формирования инструкции по переносу домена (%s.%s) к нам.', $this->params['DomainName'], $SchemeName['SchemeName'] /*$this->params['SchemeName']*/),
					'IsReaded'  => FALSE
					);
			$Event = Comp_Load('Events/EventInsert', $Event);
			if(!$Event)
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			// буржуйские домены, пеерносятся через AuthInfo
			$this->params['notUSSR'] = TRUE;
			$this->params['schemeName'] = StrToUpper($SchemeName['SchemeName']);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		return $this->params;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------


