<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/** created from greenDetect.class.php
# 15-th February 2013, 3:11PM
# Green detect PHP class
# This class detect poor words
# (C)2013, Katana
*/
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Text');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
#Debug(SPrintF('[comp/Formats/Edesk/Message/CheckFoul]: Text = %s',print_r($Text,true)));
$Regulars = Regulars();
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Interface']['Edesks']['DenyFoulLanguage'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$EnCharMap = array(
			"jo", "j", "c", "u", "k", "e", "n", "g", "sh", "sh", "z",
			"h", "'", "f", "u", "v", "a", "p", "r", "o", "l", "d",
			"zh", "je", "ja", "ch", "s", "m", "i", "t", "'", "b", "ju"
		);
#-------------------------------------------------------------------------------
# For transliteration russian char map
$RuCharMap = array(
			"ё", "й", "ц", "у", "к", "е", "н", "г", "ш", "щ", "з",
			"х", "ъ", "ф", "ы", "в", "а", "п", "р", "о", "л", "д",
			"ж", "э", "я", "ч", "с", "м", "и", "т", "ь", "б", "ю"
		);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach(Preg_Split('/[\s,]+/',$Text) as $Word){
	#Debug(SPrintF('[comp/Formats/Edesk/Message/CheckFoul]: Word = "%s"',print_r($Word,true)));
	#-------------------------------------------------------------------------------
	$Word = Trim(Str_Replace($EnCharMap,$RuCharMap,StrToLower($Word)));
	#-------------------------------------------------------------------------------
	if(Mb_StrLen($Word) > $Settings['FoulMaxLength'])
		continue;
	#-------------------------------------------------------------------------------
	foreach(Preg_Split('/\s+/', $Regulars['Fouls']) as $Foul){
		#-------------------------------------------------------------------------------
		$Foul = Trim($Foul);
		#Debug(SPrintF('[comp/Formats/Edesk/Message/CheckFoul]: Foul = "%s"',print_r($Foul,true)));
		#-------------------------------------------------------------------------------
		if(Preg_Match($Foul, $Word)){
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/Formats/Edesk/Message/CheckFoul]: Foul found: "%s"',$Word));
			return Array('Word'=>$Word);	# нецензурщина детектед
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		continue;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------

?>
