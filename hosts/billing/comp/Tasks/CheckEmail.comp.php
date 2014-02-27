<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('classes/ImapMailbox.php','libs/StripTagsSmart.php','libs/Server.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Tasks']['Types']['CheckEmail'];
#-------------------------------------------------------------------------------
# проверяем, есть ли функции для работы с IMAP
if(!Function_Exists('imap_open'))
	return 24*3600;
#-------------------------------------------------------------------------------
$ExecuteTime = Comp_Load('Formats/Task/ExecuteTime',Array('ExecutePeriod'=>$Settings['ExecutePeriod']));
if(Is_Error($ExecuteTime))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!$Settings['IsActive'])
	return 3600;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$ServerSettings = SelectServerSettingsByTemplate('EmailClient');
switch(ValueOf($ServerSettings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'] = 'server with template: EmailClient, params: IsActive, IsDefault not found';
	#-------------------------------------------------------------------------------
	return $ExecuteTime;
	#-------------------------------------------------------------------------------
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = Array();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Count = DB_Count('Users',Array('ID'=>10));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!$Count){
	#-------------------------------------------------------------------------------
	Debug('[comp/Tasks/CheckEmail]: сообщения не обработаны, т.к. пользователь "Гость", идентификатор 10 не найден');
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'][] = "no message processing, because user 'Guest', ID=10 does not exists";
	#-------------------------------------------------------------------------------
	return $ExecuteTime;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Server = SPrintF("{%s/%s/%s}INBOX",$ServerSettings['Address'],$ServerSettings['Params']['Method'],($ServerSettings['Protocol'] == 'ssl')?'ssl/novalidate-cert':'notls');
#-------------------------------------------------------------------------------
$attachmentsDir = SPrintF('%s/hosts/%s/tmp/imap',SYSTEM_PATH,HOST_ID);
#-------------------------------------------------------------------------------
if(!File_Exists($attachmentsDir))
	MkDir($attachmentsDir, 0700, true);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
try{
	#-------------------------------------------------------------------------------
	$mailbox = new ImapMailbox($Server,$ServerSettings['Login'],$ServerSettings['Password'],$attachmentsDir);
	#-------------------------------------------------------------------------------
}catch(Exception $e){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/CheckEmail]: Exception = %s',$e->getMessage()));
	return 3600;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'][] = SPrintF('%s messages',SizeOf($mailbox->searchMailbox()));
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Tasks/CheckEmail]: сообщений = %s',SizeOf($mailbox->searchMailbox())));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Mails = $mailbox->searchMailbox();
#-------------------------------------------------------------------------------
foreach($Mails as $mailId){
	#-------------------------------------------------------------------------------
	$mail = $mailbox->getMail($mailId);
	#-------------------------------------------------------------------------------
	if(SizeOf($mailbox->searchMailbox()) < 1){
		#-------------------------------------------------------------------------------
		$mailbox->disconnect();
		#-------------------------------------------------------------------------------
		UnSet($GLOBALS['TaskReturnInfo']);
		#-------------------------------------------------------------------------------
		return $ExecuteTime;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Subject = $mail->subject;
	$fromAddress = StrToLower($mail->fromAddress);
	$textPlain = $mail->textPlain;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$replyTo = Array($fromAddress);
	#-------------------------------------------------------------------------------
	if(IsSet($mail->replyTo)){
		#-------------------------------------------------------------------------------
		foreach(Array_Keys($mail->replyTo) as $replyToAddr)
			if($fromAddress != StrToLower($replyToAddr))
				$replyTo[] = StrToLower($replyToAddr);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# если сообщение в html to textPlain - пустая
	if(!$textPlain)
		$textPlain = StripTagsSmart($mail->textHtml);
	#-------------------------------------------------------------------------------
	# перебираем аттачменты
	UnSet($_FILES);
	UnSet($Hash);
	#-------------------------------------------------------------------------------
	$Files = $mail->attachments;
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($Files) as $FileName){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Tasks/CheckEmail]: tmp_name = %s; FileName = %s',$Files[$FileName],$FileName));
		#-------------------------------------------------------------------------------
		if(FileSize($Files[$FileName]) == 0)
			continue;
		#-------------------------------------------------------------------------------
		$FileData = Array(
					'size'		=> FileSize($Files[$FileName]),
					'error'		=> 0,
					'tmp_name'	=> $Files[$FileName],
					'name'		=> $FileName
				);
		#-------------------------------------------------------------------------------
		$_FILES = Array('Upload'=>$FileData);
		#-------------------------------------------------------------------------------
		global $_FILES;
		#-------------------------------------------------------------------------------
		$Hash = Comp_Load('www/API/Upload');
		if(Is_Error($Hash))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	# надо ли вырезать цитаты из текста
	if($Settings['CutQuotes']){
		#-------------------------------------------------------------------------------
		$textPlain = Trim(Preg_Replace('#^>(.*)$#m', '',$textPlain));
		$textPlain = preg_replace("/\r/", "\n",$textPlain);
		$textPlain = trim(preg_replace('/[\n]+/m',"\n",$textPlain));
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		$textPlain = Trim(Preg_Replace('/\n(\>.*(\n\>.*)*)/',"\n[quote]\\1[/quote]",$textPlain));
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# надо ли отпиливать подпись из сообщения
	if($Settings['CutSign']){
		#-------------------------------------------------------------------------------
		$Texts = Explode("\n",$textPlain);
		#-------------------------------------------------------------------------------
		$textPlain = Array();
		#-------------------------------------------------------------------------------
		foreach($Texts as $Text){
			#-------------------------------------------------------------------------------
			$textPlain[] = Trim($Text);
			#-------------------------------------------------------------------------------
			if(Trim($Text) == '--')
				$SignPos = SizeOf($textPlain) - 1;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$Length = (IsSet($SignPos))?$SignPos:SizeOf($textPlain);
		#-------------------------------------------------------------------------------
		$textPlain = Implode("\n",Array_Slice($textPlain,0,$Length));
		#-------------------------------------------------------------------------------
		UnSet($SignPos);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# достаём все заголовки
	$References = FALSE;
	#-------------------------------------------------------------------------------
	$Headers = Explode("\n", Trim($mailbox->fetchHeader($mail->mId)));
	#-------------------------------------------------------------------------------
	if(Is_Array($Headers) && Count($Headers)){
		foreach($Headers as $Line){
			#-------------------------------------------------------------------------------
			$HeaderLine = Explode(" ",Trim($Line));
			#-------------------------------------------------------------------------------
			if(StrToLower($HeaderLine[0]) == 'in-reply-to:')
				$References = (IsSet($HeaderLine[1])?$HeaderLine[1]:'[empty header]');
			#-------------------------------------------------------------------------------
			if(StrToLower($HeaderLine[0]) == 'references:')
				$References = (IsSet($HeaderLine[1])?$HeaderLine[1]:'[empty header]');
			#-------------------------------------------------------------------------------
			if(StrToLower($HeaderLine[0]) == 'x-autoreply:')
				$AutoReply = SPrintF('%s %s',$HeaderLine[0],(IsSet($HeaderLine[1])?$HeaderLine[1]:'[empty header]'));
			#-------------------------------------------------------------------------------
			if(StrToLower($HeaderLine[0]) == 'auto-submitted:')
				$AutoReply = SPrintF('%s %s',$HeaderLine[0],(IsSet($HeaderLine[1])?$HeaderLine[1]:'[empty header]'));
			#-------------------------------------------------------------------------------
			if(StrToLower($HeaderLine[0]) == 'x-auto-response-suppress:')
				$AutoReply = SPrintF('%s %s',$HeaderLine[0],(IsSet($HeaderLine[1])?$HeaderLine[1]:'[empty header]'));
			#-------------------------------------------------------------------------------
		}
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(StrLen($textPlain) < 2 && !IsSet($Hash)){
		#-------------------------------------------------------------------------------
		# пустое сообщение, или вместе с подписью текст выпилился
		Debug(SPrintF('[comp/Tasks/CheckEmail]: Пустое сообщение с адреса %s',$fromAddress));
		$IsDelete = FALSE;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(IsSet($AutoReply) && $Settings['DeleteAutoreply']){
		#-------------------------------------------------------------------------------
		$IsDelete = TRUE;
		#-------------------------------------------------------------------------------
		if(StrLen($Settings['DeleteAutoreplyExclude']) > 5){
			#-------------------------------------------------------------------------------
			$Emails = Explode(",",StrToLower($Settings['DeleteAutoreplyExclude']));
			#-------------------------------------------------------------------------------
			foreach($Emails as $Email){
				#-------------------------------------------------------------------------------
				if(Trim($Email) == $fromAddress){
					#-------------------------------------------------------------------------------
					Debug(SPrintF('[comp/Tasks/CheckEmail]: Excluded AutoReply from: "%s", header: "%s"',$fromAddress,$AutoReply));
					$IsDelete = FALSE;
					#-------------------------------------------------------------------------------
				}
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(IsSet($IsDelete) && $IsDelete){
		#-------------------------------------------------------------------------------
		# это автоответ. удаляем сообщение и продолжаем
		Debug(SPrintF('[comp/Tasks/CheckEmail]: AutoReply from: "%s", header: "%s"',$fromAddress,$AutoReply));
		$mailbox->deleteMessage($mail->mId, TRUE);
		UnSet($AutoReply);
		UnSet($IsDelete);
		continue;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# проверяем наличие ссылки на тикет
	if($References){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Tasks/CheckEmail]: References %s',$References));
		#-------------------------------------------------------------------------------
		$Address = MailParse_RFC822_Parse_Addresses($References);
		$Address = Explode("@",$Address[0]['address']);
		#-------------------------------------------------------------------------------
		if(IsSet($Address[1]) && $Address[1] == HOST_ID && IntVal($Address[0]) == $Address[0]){
			#-------------------------------------------------------------------------------
			# проверяем наличие такого тикета
			$Columns = Array('*','(SELECT `UserID` FROM `Edesks` WHERE `EdesksMessagesOwners`.`EdeskID` = `Edesks`.`ID`) AS `EdeskUserID`');
			$Edesk = DB_Select('EdesksMessagesOwners',$Columns,Array('UNIQ','ID'=>$Address[0]));
			switch(ValueOf($Edesk)){
			case 'error':
				return ERROR | @Trigger_Error(500);
			case 'exception':
				break;
			case 'array':
				#-------------------------------------------------------------------------------
				$MessageID = $Address[0];
				#-------------------------------------------------------------------------------
				Debug(SPrintF('[comp/Tasks/CheckEmail]: EdeskID = %s',$Edesk['EdeskID']));
				#-------------------------------------------------------------------------------
				break;
			default:
				return ERROR | @Trigger_Error(101);
			}
		}
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# ищщем юзера, по email отправителяa
	$IsUser = FALSE;
	$IsAdmin= FALSE;
	#-------------------------------------------------------------------------------
	foreach($replyTo as $Addr){
		#-------------------------------------------------------------------------------
		$User = DB_Select('Users',Array('*'),Array('UNIQ','Where'=>SPrintF('`Email` = "%s"',$Addr)));
		#-------------------------------------------------------------------------------
		switch(ValueOf($User)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/Tasks/CheckEmail]: user not found: %s',$Addr));
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		case 'array':
			#-------------------------------------------------------------------------------
			$IsUser = TRUE;
			#-------------------------------------------------------------------------------
			$IsAdmin = Permission_Check('/Administrator/',(integer)$User['ID']);
			switch(ValueOf($IsAdmin)){
			case 'error':
				return ERROR | @Trigger_Error(500);
			case 'exception':
				return ERROR | @Trigger_Error(400);
			case 'false':
				break;
			case 'true':
				#-------------------------------------------------------------------------------
				$IsAdmin = TRUE;
				#-------------------------------------------------------------------------------
				break 2;
				#-------------------------------------------------------------------------------
			default:
				return ERROR | @Trigger_Error(101);
			}
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Regulars = Regulars();
	if(!Preg_Match($Regulars['Email'],$fromAddress))
		Debug(SPrintF('[comp/Tasks/CheckEmail]: неверный fromAddress = %s',$fromAddress));
	#-------------------------------------------------------------------------------
	if($Settings['CreateNewUserAccounts'] && !$IsUser && Preg_Match($Regulars['Email'],$fromAddress)){
		#-------------------------------------------------------------------------------
		#Name Password Email
		$Array = Explode('@',$fromAddress);
		$User = Comp_Load('www/API/UserRegister',Array('Name'=>$Array['0'],'Password'=>SubStr(Md5(UniqID(Time())),0,8),'Email'=>$fromAddress,'Message'=>'Автоматическая регистрация пользователя по почтовому сообщению','IsInternal'=>TRUE));
		#-------------------------------------------------------------------------------
		switch(ValueOf($User)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			return ERROR | @Trigger_Error(400);
		case 'array':
			#-------------------------------------------------------------------------------
			$IsUser = TRUE;
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# added by lissyara, 2013-09-10 in 13:50, for JBS-724
	if($Config['Interface']['Edesks']['DenyFoulLanguage']['IsEmailActive'] && $Config['Interface']['Edesks']['DenyFoulLanguage']['IsEvent']){
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Formats/Edesk/Message/CheckFoul',$textPlain);
		#-------------------------------------------------------------------------------
		switch(ValueOf($Comp)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			return ERROR | @Trigger_Error(400);
		case 'array':
			#-------------------------------------------------------------------------------
			$Event = Array(
					'UserID'	=> ($IsUser)?$User['ID']:10,
					'PriorityID'	=> 'Error',
					'Text'		=> SPrintF('Удалено почтовое сообщение с нецензурной лексикой (%s) c адреса (%s)',$Comp['Word'],$fromAddress),
					'IsReaded'	=> FALSE
					);
			$Event = Comp_Load('Events/EventInsert', $Event);
			if(!$Event)
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
		case 'true':
			break;
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		if($Subject){
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Formats/Edesk/Message/CheckFoul',$Subject);
			#-------------------------------------------------------------------------------
			switch(ValueOf($Comp)){
			case 'error':
				return ERROR | @Trigger_Error(500);
			case 'exception':
				return ERROR | @Trigger_Error(400);
			case 'array':
				#-------------------------------------------------------------------------------
				$Event = Array(
						'UserID'	=> ($IsUser)?$User['ID']:10,
						'PriorityID'	=> 'Error',
						'Text'		=> SPrintF('Удалено почтовое сообщение с нецензурной темой (%s) c адреса (%s)',$Comp['Word'],$fromAddress),
						'IsReaded'	=> FALSE
						);
				$Event = Comp_Load('Events/EventInsert', $Event);
				if(!$Event)
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
			case 'true':
				break;
			default:
				return ERROR | @Trigger_Error(101);
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if($Settings['SaveHeaders'])
		$SaveHeaders = SPrintF("[hidden]\n%s[/hidden]\n",$mailbox->fetchHeader($mail->mId));
	#-------------------------------------------------------------------------------
	$Message = SPrintF("%s\n\n%s[size=10][color=gray]posted via email, from: %s[/color][/size]",Trim($textPlain),(IsSet($SaveHeaders))?$SaveHeaders:'',(($IsAdmin)?$User['Name']:$fromAddress));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# имеем 2 ситуации, задан или не задан $MessageID - соответственно, добавление в тикет или создание тикета
	if(IsSet($MessageID)){
		#-------------------------------------------------------------------------------
		#$NewUserID = (($IsUser)?$User['ID']:100);
		#-------------------------------------------------------------------------------
		# снимаем флаг у треда
		$IsUpdate = DB_Update('Edesks',Array('Flags'=>'No'),Array('ID'=>$Edesk['EdeskID']));
		if(Is_Error($IsUpdate))
			return ERROR | @Trigger_Error(500);
		# постим от админа, т.к. пост может идти от другого юзера в ответ на...
		$GLOBALS['__USER']['ID'] = 100;
		#-------------------------------------------------------------------------------
		$Params = Array('Message'=>$Message,'TicketID'=>$Edesk['EdeskID'],'UserID'=>(($IsUser)?$User['ID']:10));
		#-------------------------------------------------------------------------------
		if(IsSet($Hash))
			$Params['TicketMessageFile'] = $Hash;
		#-------------------------------------------------------------------------------
		$IsAdd = Comp_Load('www/API/TicketMessageEdit',$Params);
		if(Is_Error($IsAdd))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$GLOBALS['__USER']['ID'] = 100;
		#-------------------------------------------------------------------------------
		$mailbox->deleteMessage($mail->mId, TRUE);
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		$Params = Array('Theme'=>($Subject)?$Subject:'[no message theme, v2]','PriorityID'=>'Low','Flags'=>'No','TargetGroupID'=>3100000);
		#-------------------------------------------------------------------------------
		# разное поведение, в зависимости от того - юзер это или нет
		if(!$IsUser){
			#-------------------------------------------------------------------------------
			$NewUserID = 10;
			#-------------------------------------------------------------------------------
			$Params['Message']	= $Message;
			$Params['NotifyEmail']	= Implode(',',$replyTo);
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			if($IsAdmin && !$Settings['DeletePersonalEmails']){
				#-------------------------------------------------------------------------------
				$NewUserID = 10;
				$Message = SPrintF("[hidden]\nПисьмо от сотрудника '%s/%s'[/hidden]\n%s",$User['Name'],$User['Email'],$Message);
				#-------------------------------------------------------------------------------
			}else{
				#-------------------------------------------------------------------------------
				$NewUserID = $User['ID'];
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			$Params['Message'] = $Message;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		# загружаем файл
		if(IsSet($Hash))
			$Params['TicketMessageFile'] = $Hash;
		#-------------------------------------------------------------------------------
		# присваиваем себе нужный идентификатор юзера
		$GLOBALS['__USER']['ID'] = $NewUserID;
		#-------------------------------------------------------------------------------
		# шлём сообщение на www/API/TicketEdit
		$IsAdd = Comp_Load('www/API/TicketEdit',$Params);
		if(Is_Error($IsAdd))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$GLOBALS['__USER']['ID'] = 100;
		#-------------------------------------------------------------------------------
		$mailbox->deleteMessage($mail->mId, TRUE);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	# ампутируем переменную, чтоб в один тикет не напостило все письма
	UnSet($MessageID);
	#-------------------------------------------------------------------------------
	# удаляем файлы
	$Files = IO_Scan($attachmentsDir);
	if(Is_Error($Files))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	foreach($Files as $File){
		#-----------------------------------------------------------------------------
		$Path = SPrintF('%s/%s',$attachmentsDir,$File);
		#-----------------------------------------------------------------------------
		if(!UnLink($Path))
			return ERROR | @Trigger_Error(SPrintF('Не удалось удалить файл (%s)',$Path));
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(SizeOf($mailbox->searchMailbox()) < 1)
	UnSet($GLOBALS['TaskReturnInfo']);
#-------------------------------------------------------------------------------
$mailbox->disconnect();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $ExecuteTime;
#-------------------------------------------------------------------------------

?>
