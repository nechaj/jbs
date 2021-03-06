<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','Address','Message','Attribs');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
// возможно, параметры не заданы/требуется немедленная отправка - время не опредлеяем
if(!IsSet($Attribs['IsImmediately']) || !$Attribs['IsImmediately']){
	#-------------------------------------------------------------------------------
	// проверяем, можно ли отправлять в заданное время
	$TransferTime = Comp_Load('Formats/Task/TransferTime',$Attribs['UserID'],$Address,'Email',$Attribs['TimeBegin'],$Attribs['TimeEnd']);
	#-------------------------------------------------------------------------------
	switch(ValueOf($TransferTime)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'integer':
		return $TransferTime;
	case 'false':
		break;
	default:
		return ERROR | @Trigger_Error(100);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('libs/Server.php','classes/SendMailSmtp.class.php')))
        return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Tasks/Email]: отправка письма для (%s), тема (%s)',$Address,$Attribs['Theme']));
#-------------------------------------------------------------------------------
#Debug(SPrintF('[comp/Tasks/Email]: %s',print_r($Attribs,true)));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// достаём данные юзера которому идёт письмо
$User = DB_Select('Users', Array('ID','Params'), Array('UNIQ', 'ID' => $Attribs['UserID']));
if(!Is_Array($User))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
// добавляем идентфикаторы вложений
$Attachments = Array();
#-------------------------------------------------------------------------------
if(IsSet($Attribs['Attachments']) && Is_Array($Attribs['Attachments']) && SizeOf($Attribs['Attachments']))
	if($User['Params']['Settings']['SendEdeskFilesToEmail'] == "Yes")
		foreach($Attribs['Attachments'] as $Attachment)
			$Attachments[UniqId()] = $Attachment;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// получатель, с именем
$Recipient = SPrintF('=?UTF-8?B?%s?= <%s>',Base64_Encode($Attribs['UserName']),$Address);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config		= Config();
$Regulars	= Regulars();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = $Address;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// заголовки, могут быть не заданы
$Array = (IsSet($Attribs['Heads']) && SizeOf($Attribs['Heads']))?Explode("\n",$Attribs['Heads']):Array();
#-------------------------------------------------------------------------------
// заголовок от кого может быть с текстом а не тока почтой
if(Is_Array($Attribs['From'])){
	#-------------------------------------------------------------------------------
	if(IsSet($Attribs['From']['Name'])){
		#-------------------------------------------------------------------------------
		$Array[] = SPrintF('From: =?UTF-8?B?%s?= <%s>',Base64_Encode($Attribs['From']['Name']),$Attribs['From']['Email']);
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		// имя пользователя не задано
		$Array[] = SPrintF('From: %s', $Attribs['From']['Email']);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	// просто почтовый адрес
	$Array[] = SPrintF('From: %s', $Attribs['From']);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Array[] = 'MIME-Version: 1.0';
$Array[] = 'Content-Transfer-Encoding: 8bit';
$Array[] = SPrintF('Content-Type: multipart/related; boundary="----==--%s"',HOST_ID);
$Array[] = 'X-Priority: 3';
$Array[] = 'X-MSMail-Priority: Normal';
$Array[] = 'X-Mailer: JBS';
$Array[] = 'X-MimeOLE: JBS';
$Array[] = SPrintF('X-JBS-Origin: %s',HOST_ID);
#-------------------------------------------------------------------------------
// идентфикатор сообщения
// added by lissyara 2013-02-13 in 15:45 MSK, for JBS-609
if(IsSet($Attribs['MessageID']) && $Attribs['MessageID'])
	$Array[] = SPrintF('Message-ID: <%s@%s>',$Attribs['MessageID'],HOST_ID);
#-------------------------------------------------------------------------------
$Heads = Implode("\n",$Array);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Boundary = "\r\n\r\n------==--" . HOST_ID;
#-------------------------------------------------------------------------------
if(IsSet($Attribs['HTML']) && $Attribs['HTML']){
	#-------------------------------------------------------------------------------
	// JBS-1315 - если задан HTML то оставляем только его
	$Message = SPrintF("\r\n%s",$Attribs['HTML']);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	// у нас две версии письма в одном - и текстовая и HTML
	$Plain = Comp_Load('Edesks/Text',Array('String'=>Trim($Message),'IsEmail'=>TRUE));
	if(Is_Error($Plain))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Html = Comp_Load('Edesks/Text',Array('String'=>$Message));
	if(Is_Error($Html))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Params = Array('HOST_ID'=>HOST_ID,'PLAIN_TEXT'=>$Plain,'HTML_THEME'=>$Attribs['Theme'],'HTML_TEXT'=>$Html,'HTML_SIGN'=>'','HTML_GREETING'=>'');
	#-------------------------------------------------------------------------------
	// добавляем привествие, если необходимо
	if($Config['Notifies']['Methods']['Email']['Greeting']){
		#-------------------------------------------------------------------------------
		$Plain = SPrintF("%s\n\n%s",SPrintF(Trim($Config['Notifies']['Methods']['Email']['Greeting']),$Attribs['UserName']),$Plain);
		#-------------------------------------------------------------------------------
		$Params['HTML_GREETING'] = SPrintF('<p>%s</p>',SPrintF(Trim($Config['Notifies']['Methods']['Email']['Greeting']),$Attribs['UserName']));
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	// добавляем подпись, если необходимо
	if(!$Config['Notifies']['Methods']['Email']['CutSign']){
		#-------------------------------------------------------------------------------
		$EmailSign = DB_Select('Config','Value',Array('UNIQ','Where'=>"`Param` = 'EmailSign'"));
		if(!Is_Array($EmailSign))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Plain = SPrintF("%s\n\n--\n%s",Trim($Plain),Trim($GLOBALS['__USER']['Sign']));
		#-------------------------------------------------------------------------------
		$Params['HTML_SIGN'] = $EmailSign['Value']?$EmailSign['Value']:Trim($GLOBALS['__USER']['Sign']);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	// достаём профиль исполнителя - данные организации, сайт и т.п.
	$Compile = Comp_Load('www/Administrator/API/ProfileCompile',Array('ProfileID'=>100));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Compile)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	$Params['Executor'] = $Compile;
	#Debug(SPrintF('[comp/Tasks/Email]: Params = %s',print_r($Params,true)));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// строим HTML c картинками
	$Params['ATTACHMENTS'] = '';
	#-------------------------------------------------------------------------------
	if(SizeOf($Attachments)){
		#-------------------------------------------------------------------------------
		foreach(Array_Keys($Attachments) as $Key){
			#-------------------------------------------------------------------------------
			$Mime = Explode('/',$Attachments[$Key]['Mime']);
			#-------------------------------------------------------------------------------
			// если это НЕ картинка - пропускаем
			if($Mime[0] != 'image')
				continue;
			#-------------------------------------------------------------------------------
			// если картинка в списке исключений (которые браузер не умеет показывать), пропускаем
			if(In_Array($Mime[1],Array('tiff')))
				continue;
			#-------------------------------------------------------------------------------
			$Params['ATTACHMENTS'] = SPrintF('%s <IMG src="cid:%s" alt="%s"><BR />',$Params['ATTACHMENTS'],$Key,$Attachments[$Key]['Name']);
			#-------------------------------------------------------------------------------
			$Attachments[$Key]['CID'] = SPrintF("Content-ID: <%s>",$Key);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// готовим HTML часть сообщения
	$Params['HTML_TEXT'] = Chunk_Split(Base64_Encode(TemplateReplace('Email.HTML',$Params,FALSE)));
	#-------------------------------------------------------------------------------
	// заменяем текст и хтмл в шаблоне
	$Message = TemplateReplace('Email',$Params,FALSE);
	#-------------------------------------------------------------------------------
	
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// шаблон заголовков вложения, заколебался ковыряться в одну строку
$HeaderTpl = "Content-Disposition: attachment;\r\n\tfilename=\"%s\";\r\nContent-Transfer-Encoding: base64\r\nContent-Type: %s;\r\n\tname=\"%s\"";
#-------------------------------------------------------------------------------
# достаём вложения, если они есть, и прикладываем к сообщению
if(SizeOf($Attachments)){
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/Tasks/Email]: письмо содержит %u вложений',SizeOf($Attribs['Attachments'])));
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($Attachments) as $Key){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Tasks/Email]: обработка вложения (%s), размер (%s), тип (%s)',$Attachments[$Key]['Name'],$Attachments[$Key]['Size'],$Attachments[$Key]['Mime']));
		#-------------------------------------------------------------------------------
		$Header = SPrintF($HeaderTpl,Mb_Encode_MimeHeader($Attachments[$Key]['Name']),$Attachments[$Key]['Mime'],Mb_Encode_MimeHeader($Attachments[$Key]['Name']),$Key);
		#-------------------------------------------------------------------------------
		if(IsSet($Attachments[$Key]['CID']))
			$Header = SPrintF("%s\r\n%s",$Header,$Attachments[$Key]['CID']);
		#-------------------------------------------------------------------------------
		$Message = SPrintF("%s%s\r\n%s\r\n\r\n%s",$Message,$Boundary,$Header,$Attachments[$Key]['Data']);
		#Debug(SPrintF('[comp/Tasks/Email]: %s',$Attachment['Data']));
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
# закрываем сообщение
$Message = SPrintF("%s\r\n\r\n%s--",$Message,$Boundary);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Settings = SelectServerSettingsByTemplate('Email');
#-------------------------------------------------------------------------------
switch(ValueOf($Settings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/Email]: не найден сервер для отправки почты, используется функция mail()'));
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Array($Settings)){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/Email]: отправка через SMTP'));
	#-------------------------------------------------------------------------------
	$mailSMTP = new SendMailSmtpClass($Settings['Login'],$Settings['Password'], SPrintF('%s://%s',$Settings['Protocol'],$Settings['Address']), '', $Settings['Port']);   
	#-------------------------------------------------------------------------------
	$IsMail = $mailSMTP->send($Recipient,$Attribs['Theme'],$Message,$Heads);
	if(!$IsMail)
		return ERROR | @Trigger_Error('[comp/Tasks/Email]: ошибка отправки почты через SMTP ');
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$IsMail = @Mail($Recipient,Mb_Encode_MimeHeader($Attribs['Theme']),$Message,$Heads);
	if(!$IsMail)
		return ERROR | @Trigger_Error('[comp/Tasks/Email]: ошибка отправки сообщения, проверьте работу функции mail в PHP');
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Config['Notifies']['Methods']['Email']['IsEvent'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Event = Array(
		'UserID'=> $Attribs['UserID'],
		'Text'	=> SPrintF('Сообщение для (%s) с темой (%s) отправлено по электронной почте',$Address,$Attribs['Theme'])
		);
$Event = Comp_Load('Events/EventInsert',$Event);
#-------------------------------------------------------------------------------
if(!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------

?>
