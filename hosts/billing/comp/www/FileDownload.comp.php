<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$FileID = (integer) @$Args['FileID'];
$TypeID =  (string) @$Args['TypeID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','libs/HTMLDoc.php','libs/Upload.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$TypeID = DB_Escape($TypeID);
#-------------------------------------------------------------------------------
$FileData = DB_Select($TypeID,'*',Array('UNIQ','ID'=>$FileID));
#-------------------------------------------------------------------------------
switch(ValueOf($FileData)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return ERROR | @Trigger_Error(400);
  case 'array':
    #---------------------------------------------------------------------------
    $Permission = Permission_Check('EdeskRead',(integer)$GLOBALS['__USER']['ID'],(integer)$FileData['UserID']);
    #---------------------------------------------------------------------------
    switch(ValueOf($Permission)){
      case 'error':
        return ERROR | @Trigger_Error(500);
      case 'exception':
        return ERROR | @Trigger_Error(400);
      case 'false':
        return ERROR | @Trigger_Error(700);
      case 'true':
        #-----------------------------------------------------------------------
        $Length = GetUploadedFileSize($TypeID, $FileID);
        #-----------------------------------------------------------------------
        if(!$Length)
          return new gException('CANNOT_GET_FILE_SIZE','Не удалось получить размер файла');
        #-----------------------------------------------------------------------
        $Data = GetUploadedFile($TypeID, $FileID);
	#-----------------------------------------------------------------------
	$FileName = SPrintF('%s.bin',$FileData['ID']);
	if($TypeID == 'EdesksMessages')	{$FileName = $FileData['FileName'];}
	if($TypeID == 'Profiles')	{$FileName = SPrintF('document_%s.%s',$FileData['ID'],$FileData['Format']);}
        #-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Ext = StrToLower(SubStr($FileName, -4));
	#-------------------------------------------------------------------------------
	if(StrPos($Ext, '.') !== FALSE)
		$Ext = SubStr($Ext, StrPos($Ext, '.') + 1, StrLen($Ext));
	#-------------------------------------------------------------------------------
	$Types = Array(
			'jpg'	=> 'image/jpeg',
			'jpeg'	=> 'image/jpeg',
			'gif'	=> 'image/gif',
			'png'	=> 'image/png',
			'bmp'	=> 'image/bmp',
			'pdf'	=> 'application/pdf',
			'tiff'	=> 'image/tiff',
			'tif'	=> 'image/tiff',
	);
	#-------------------------------------------------------------------------------
	$ContentType = IsSet($Types[$Ext])?$Types[$Ext]:'application/octetstream; charset=utf-8';
	#-------------------------------------------------------------------------------
        Header(SPrintF('Content-Type: %s',$ContentType));
        Header(SPrintF('Content-Length: %u',$Length));
        Header(SPrintF('Content-Disposition: attachment; filename="%s";',$FileName));
        Header('Pragma: nocache');
        #-----------------------------------------------------------------------
        return $Data['Data'];
      default:
        return ERROR | @Trigger_Error(101);
    }
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------


?>
