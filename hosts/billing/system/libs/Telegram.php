<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/

// отправка сообщения
function TgSendMessage($Settings,$ChatID,$Text = 'not set',$Attachments = Array()){
	#-------------------------------------------------------------------------------
	// внутри всех функций прописываем подёргивание WEB-hook'a. ответ не интересен...
	TgRegWebHook($Settings);
	#-------------------------------------------------------------------------------
	$HTTP = TgBuild_HTTP($Settings);
	#-------------------------------------------------------------------------------
	$Query = Array('chat_id'=>$ChatID,'text'=>$Text,'disable_web_page_preview'=>'TRUE');
	#-------------------------------------------------------------------------------
	$Result = HTTP_Send(SPrintF('/bot%s/sendMessage',$Settings['Params']['Token']),$HTTP,Array(),$Query);
	if(Is_Error($Result))
		return ERROR | @Trigger_Error('[TgSendMessage]: не удалось выполнить запрос к серверу');
	#-------------------------------------------------------------------------------
        $Result = Trim($Result['Body']);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Result = Json_Decode($Result,TRUE);
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[system/libs/Telegram]: $Result = %s',print_r($Result,true)));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(IsSet($Result['ok']) && $Result['ok']){
		#-------------------------------------------------------------------------------
		return TRUE;
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		// TODO по идее там есть человекочитемое сообщение о ошибке. надо словить и выдать в ответе
		return FALSE;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}


#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// отправка файла
function TgSendFile($Settings,$ChatID,$Attachments = Array()){
	#-------------------------------------------------------------------------------
	// внутри всех функций прописываем подёргивание WEB-hook'a. ответ не интересен...
	TgRegWebHook($Settings);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Boundary = SPrintF('------------------------%s',Md5(Rand()));
	#-------------------------------------------------------------------------------
	$Headers = Array(SPrintF('Content-Type: multipart/form-data; boundary=%s',$Boundary)/*,'Connection: keep-alive','Keep-Alive: 300'*/);
	#-------------------------------------------------------------------------------
	$HTTP = TgBuild_HTTP($Settings);
	$HTTP['Charset'] = '';
	#-------------------------------------------------------------------------------
	foreach ($Attachments as $Attachment){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[system/libs/Telegram]: обработка вложения (%s), размер (%s), тип (%s)',$Attachment['Name'],$Attachment['Size'],$Attachment['Mime']));
		#-------------------------------------------------------------------------------
		$Body = SPrintF("--%s\r\n",$Boundary);
		$Body = SPrintF("%sContent-Disposition: form-data; name=\"document\"; filename=\"%s\"\r\n",$Body,$Attachment['Name']);
		$Body = SPrintF("%sContent-Type: %s\r\n",$Body,$Attachment['Mime']);
		$Body = SPrintF("%s\r\n%s",$Body,base64_decode($Attachment['Data']));
		$Body = SPrintF("%s\r\n%s",$Body,$Attachment['Data']);
		$Body = SPrintF("%s\r\n--%s--\r\n\r\n",$Body,$Boundary);
		#-------------------------------------------------------------------------------
		$Query = Array('chat_id'=>$ChatID);
		#-------------------------------------------------------------------------------
		$Result = HTTP_Send(SPrintF('/bot%s/sendDocument',$Settings['Params']['Token']),$HTTP,$Query,$Body,$Headers);
		if(Is_Error($Result))
			return ERROR | @Trigger_Error('[TgSendFile]: не удалось выполнить запрос к серверу');
		#-------------------------------------------------------------------------------
        	$Result = Trim($Result['Body']);
		#-------------------------------------------------------------------------------
		$Result = Json_Decode($Result,TRUE);
		#-------------------------------------------------------------------------------
		if(IsSet($Result['ok']) && $Result['ok']){
			#-------------------------------------------------------------------------------
			continue;
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[system/libs/Telegram]: $Result = %s',print_r($Result,true)));
			#-------------------------------------------------------------------------------
			// TODO по идее там есть человекочитемое сообщение о ошибке. надо словить и выдать в ответе
			return FALSE;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}


// внутренние функции
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function TgBuild_HTTP($Settings){
	/******************************************************************************/
	$__args_types = Array('array');
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$HTTP = Array(
			'Address'	=> $Settings['Address'],
			'Port'		=> 443,
			'Host'		=> $Settings['Address'],
			'Protocol'	=> 'ssl',
			);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return $HTTP;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// регистрируем WEB-hook для бота
function TgRegWebHook($Settings){
	#-------------------------------------------------------------------------------
	// проверяем, не прописывался ли веб-хук ранее
	$CacheID = Md5($Settings['Params']['Token']);
	#-------------------------------------------------------------------------------
	$Result = CacheManager::get($CacheID);
	if($Result){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[TgRegWebHook]: WebHook last register time = %s',Date('Y-m-d/H:i:s',$Result)));
		#-------------------------------------------------------------------------------
		return TRUE;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$HTTP = TgBuild_HTTP($Settings);
	#-------------------------------------------------------------------------------
	$Query = Array('url'=>SPrintF('https://%s/API/Telegram?Secret=%s',HOST_ID,$Settings['Params']['Secret']));
	#-------------------------------------------------------------------------------
	$Result = HTTP_Send(SPrintF('/bot%s/setWebhook',$Settings['Params']['Token']),$HTTP,Array(),$Query);
	if(Is_Error($Result))
		return ERROR | @Trigger_Error('[TgRegWebHook]: не удалось выполнить запрос к серверу');
	#-------------------------------------------------------------------------------
        $Result = Trim($Result['Body']);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Result = Json_Decode($Result,TRUE);
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[system/libs/Telegram]: $Result = %s',print_r($Result,true)));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(IsSet($Result['ok']) && $Result['ok']){
		#-------------------------------------------------------------------------------
		CacheManager::add($CacheID, Time(), 30 * 24 * 3600);
		#-------------------------------------------------------------------------------
		return TRUE;
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		// TODO по идее там есть человекочитемое сообщение о ошибке. надо словить и выдать в ответе
		Debug(SPrintF('[TgRegWebHook]: $Result = %s',print_r($Result,true)));
		#-------------------------------------------------------------------------------
		return FALSE;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}


?>