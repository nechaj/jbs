<?php

#-------------------------------------------------------------------------------
/** @author Rootden, for lowhosting.ru */
/* * *************************************************************************** */
/* * *************************************************************************** */
Eval(COMP_INIT);
/* * *************************************************************************** */
/* * *************************************************************************** */
$Result = Array();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if (!CacheManager::isEnabled())
    return $Result;
#-------------------------------------------------------------------------------
if (is_numeric($GLOBALS['__USER']['Mobile']) && $GLOBALS['__USER']['MobileConfirmed'] < 1) {
    #-------------------------------------------------------------------------------
    $NoBody = new Tag('NOBODY');
    $NoBody->AddHTML(TemplateReplace('Notes.User.MobileConfirmation', Array('User' => $GLOBALS['__USER'])));
    $NoBody->AddChild(new Tag('STRONG', new Tag('A', Array('href' => "javascript:ShowWindow('/UserPersonalDataChange');"), '[Мои настройки]')));
    #-------------------------------------------------------------------------------
    $Result[] = $NoBody;
    #-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Result;
#-------------------------------------------------------------------------------
?>