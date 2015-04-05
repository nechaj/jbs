<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/

function TemplateReplace($Text,$Params = Array(),$NoBody = TRUE){
	#-------------------------------------------------------------------------------
	$Text = Trim($Text);
	#-------------------------------------------------------------------------------
	# проверяем что нам сунули - текст или файл
	if(!Preg_Match('/\s/',$Text)){
		#-------------------------------------------------------------------------------
		# достаём текст из файла
		$Path = System_Element(SPrintF('templates/modules/%s.html',$Text));
		#-------------------------------------------------------------------------------
		if(Is_Error($Path)){
			#-------------------------------------------------------------------------------
			$Text = SprintF('Отсутствует шаблон сообщения (templates/modules/%s.html)',$Text);
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			$Text = Trim(IO_Read($Path));
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if($NoBody)
		$Text = SPrintF('<NOBODY><SPAN>%s</SPAN></NOBODY>',$Text);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Replace = Array_ToLine($Params,'%');
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($Replace) as $Key)
		$Text = Str_Replace($Key,$Replace[$Key],$Text);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return $Text;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>