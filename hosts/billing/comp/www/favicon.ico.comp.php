<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
#-------------------------------------------------------------------------------
$Path = Styles_Element('Images/favicon.ico');
if(Is_Error($Path))
  return 'favicon.ico not found';
#-------------------------------------------------------------------------------
$Icon = IO_Read($Path);
if(Is_Error($Icon))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
Header('Content-type: image');
Header('Cache-Control: private, max-age=86400');
#-------------------------------------------------------------------------------
return $Icon;
#-------------------------------------------------------------------------------

?>