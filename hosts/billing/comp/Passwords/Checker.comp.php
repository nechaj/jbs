<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Password','Mode');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
$Settings = $Config['Other']['PasswordChecker'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# Админам можно всё
if(IsSet($GLOBALS['__USER']['IsAdmin']) && $GLOBALS['__USER']['IsAdmin'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IsCheck = Array();;
#-------------------------------------------------------------------------------
#Debug(SPrintF('[comp/Passwords/Checker]: Password = %s Mode = %s',$Password,$Mode));
#-------------------------------------------------------------------------------
if(IsSet($Settings[$Mode]['IsActive']) && $Settings[$Mode]['IsActive']){
	#-------------------------------------------------------------------------------
	if($Settings[$Mode]['UseLetters'])
		if(!Preg_Match("/([a-zA-Z]+)/",$Password))
			return new gException('NO_LETTERS','В пароле должны использоваться буквы английского алфавита!');
	#-------------------------------------------------------------------------------
	if($Settings[$Mode]['UseDigits'])
		if(!Preg_Match("/([0-9]+)/",$Password))
			return new gException('NO_DIGITS','В пароле должны использоваться цифры!');
	#-------------------------------------------------------------------------------
	if($Settings[$Mode]['Length'] > StrLen($Password))
		return new gException('NO_DIGITS',SPrintF('Пароль не должен быть короче %u символов!',$Settings[$Mode]['Length']));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
