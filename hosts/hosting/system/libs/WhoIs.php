<?php
#-------------------------------------------------------------------------------
/** @author Бреславский А.В. (Joonte Ltd.) */
#-------------------------------------------------------------------------------
function WhoIs_Parse($Domain){
  #-----------------------------------------------------------------------------
  $Regulars = Regulars();
  #-----------------------------------------------------------------------------
  if(!Preg_Match($Regulars['Domain'],$Domain))
    return new gException('WRONG_DOMAIN_NAME','Неверное доменное имя');
  #-----------------------------------------------------------------------------
  $DomainsZones = System_XML('config/DomainsZones.xml');
  if(Is_Error($DomainsZones))
    return ERROR | @Trigger_Error('[WhoIs_Parse]: не удалось загрузить базу WhoIs серверов');
  #-----------------------------------------------------------------------------
  foreach($DomainsZones as $DomainZone){
    #---------------------------------------------------------------------------
    $Name = $DomainZone['Name'];
    #---------------------------------------------------------------------------
    if(Preg_Match(SPrintF('/^([0-9a-zабвгдеёжзийклмнопрстуфхцчшщьыъэюя\-]+)\.%s$/',Str_Replace('.','\.',$Name)),$Domain,$Matches))
      return Array('DomainName'=>Next($Matches),'DomainZone'=>$DomainZone['Name']);
  }
  #-----------------------------------------------------------------------------
  return FALSE;
}
#-------------------------------------------------------------------------------
function WhoIs_Check($DomainName,$ZoneName){
  #-----------------------------------------------------------------------------
  $Regulars = Regulars();
  #-----------------------------------------------------------------------------
  if(!Preg_Match($Regulars['DomainName'],$DomainName))
    return new gException('WRONG_DOMAIN_NAME','Неверное доменное имя');
  #-----------------------------------------------------------------------------
  $DomainsZones = System_XML('config/DomainsZones.xml');
  if(Is_Error($DomainsZones))
    return ERROR | @Trigger_Error('[WhoIs_Check]: не удалось загрузить базу WhoIs серверов');
  #-----------------------------------------------------------------------------
  $IsSuppoted = FALSE;
  #-----------------------------------------------------------------------------
  foreach($DomainsZones as $DomainZone){
    #---------------------------------------------------------------------------
    if($DomainZone['Name'] == $ZoneName){
      #-------------------------------------------------------------------------
      $IsSuppoted = TRUE;
      #-------------------------------------------------------------------------
      break;
    }
  }
  #-----------------------------------------------------------------------------
  if(!$IsSuppoted)
    return FALSE;
  #-----------------------------------------------------------------------------
  if(Mb_StrLen($DomainName) < ($MinChars = $DomainZone['MinChars']))
    return new gException('WRONG_DOMAIN_NAME_LENGTH',SPrintF('Длина доменного имени должна быть не менее %u символа(ов)',$MinChars));
  #-----------------------------------------------------------------------------
  $Domain = SPrintF('%s.%s',$DomainName,$DomainZone['Name']);
  #-----------------------------------------------------------------------------
  $CacheID = SPrintF('WhoIs_Check[%s]',$Domain);
  #-----------------------------------------------------------------------------
  $Answer = CacheManager::get($CacheID);
  #-----------------------------------------------------------------------------
  if(!$Answer){
    #---------------------------------------------------------------------------
    $Socket = @FsockOpen($DomainZone['Server'],43,$nError,$sError,5);
    #---------------------------------------------------------------------------
    if(!$Socket)
      return ERROR | @Trigger_Error('[WhoIs_Check]: ошибка соединения с сервером WhoIs');
    #---------------------------------------------------------------------------
    $IDNAConverter = new IDNAConvert();
    #---------------------------------------------------------------------------
    if(!@Fputs($Socket,SPrintF("%s\r\n",$IDNAConverter->encode($Domain))))
      return ERROR | @Trigger_Error('[WhoIs_Check]: ошибка работы с серверов WhoIs');
    #---------------------------------------------------------------------------
    $Answer = '';
    #---------------------------------------------------------------------------
    do{
      #-------------------------------------------------------------------------
      $Line = @Fgets($Socket,10);
      #-------------------------------------------------------------------------
      $Answer .= $Line;
      #-------------------------------------------------------------------------
    }while($Line);
    #---------------------------------------------------------------------------
    Debug($Answer);
    #---------------------------------------------------------------------------
    Fclose($Socket);
    #---------------------------------------------------------------------------
//    if (CacheManager::isEnabled()) {
//      $Answer = CacheManager::getInstance()->add($CacheID,$Answer,1800);
//    }
    #---------------------------------------------------------------------------
  }
  #-----------------------------------------------------------------------------
  if(Preg_Match(SPrintF('/%s/',$DomainZone['Available']),$Answer))
    return TRUE;
  #-----------------------------------------------------------------------------
  if(Preg_Match(SPrintF('/%s/',$DomainZone['NotAvailable']),$Answer))
    return new gException('DOMAIN_NOT_AVAILABLE','Доменное имя не доступно для регистрации');
  #-----------------------------------------------------------------------------
  $Result = Array('Info'=>Preg_Replace('/\n\s+\n/sU',"\n",Preg_Replace('/\%.+\n/sU','',$Answer)),'ExpirationDate'=>0);
  #-----------------------------------------------------------------------------
  $ExpirationDate = $DomainZone['ExpirationDate'];
  #-----------------------------------------------------------------------------
  if($ExpirationDate){
    #---------------------------------------------------------------------------
    if(Preg_Match(SPrintF('/%s/',$ExpirationDate),$Answer,$Mathes)){
      #-------------------------------------------------------------------------
      if(Count($Mathes) < 2)
        return ERROR | @Trigger_Error('[WhoIs_Check]: шаблон поиска даты окончания задан неверно');
      #-------------------------------------------------------------------------
      $ExpirationDate = $Mathes[1];
      #-------------------------------------------------------------------------
      $Months = Array('jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec');
      #-------------------------------------------------------------------------
      if(Preg_Match('/^[0-9]{4}\.[0-9]{2}\.[0-9]{2}$/',$ExpirationDate)){
        #-----------------------------------------------------------------------
        $Date = Array_Combine(Array('Year','Month','Day'),Explode('.',$ExpirationDate));
        #-----------------------------------------------------------------------
        $ExpirationDate = MkTime(0,0,0,$Date['Month'],$Date['Day'],$Date['Year']);
        #-----------------------------------------------------------------------
      }elseif(Preg_Match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/',$ExpirationDate)){
        #-----------------------------------------------------------------------
        $Date = Array_Combine(Array('Year','Month','Day'),Explode('-',$ExpirationDate));
        #-----------------------------------------------------------------------
        $ExpirationDate = MkTime(0,0,0,$Date['Month'],$Date['Day'],$Date['Year']);
        #-----------------------------------------------------------------------
      }elseif(Preg_Match('/^[0-9]{2}\-[a-zA-Z]{3}\-[0-9]{4}$/',$ExpirationDate)){
        #-----------------------------------------------------------------------
        $Date = Array_Combine(Array('Day','Month','Year'),Explode('-',$ExpirationDate));
        #-----------------------------------------------------------------------
        $Month = Array_Search(StrToLower($Date['Month']),$Months);
        #-----------------------------------------------------------------------
        $ExpirationDate = MkTime(0,0,0,$Month+1,$Date['Day'],$Date['Year']);
        #-----------------------------------------------------------------------
      }elseif(Preg_Match('/^[0-9]{2}\s[a-zA-Z]{2,10}\s[0-9]{4}$/',$ExpirationDate)){
        #-----------------------------------------------------------------------
        $Months = Array('january','february','march','april','may','june','july','august','september','octember','november','decemeber');
        #-----------------------------------------------------------------------
        $Date = Array_Combine(Array('Day','Month','Year'),Preg_Split('/\s+/',$ExpirationDate));
        #-----------------------------------------------------------------------
        $Month = Array_Search(StrToLower($Date['Month']),$Months);
        #-----------------------------------------------------------------------
        $ExpirationDate = MkTime(0,0,0,$Month+1,$Date['Day'],$Date['Year']);
        #-----------------------------------------------------------------------
      }else{
        #-----------------------------------------------------------------------
        $Date = Array_Combine(Array('Week','Month','Day','Time','GMT','Year'),Preg_Split('/\s+/',$ExpirationDate));
        #-----------------------------------------------------------------------
        $Month = Array_Search(StrToLower($Date['Month']),$Months);
        #-----------------------------------------------------------------------
        $ExpirationDate = MkTime(0,0,0,$Month+1,$Date['Day'],$Date['Year']);
      }
      #-------------------------------------------------------------------------
      $Result['ExpirationDate'] = $ExpirationDate;
    }
  }
  #-----------------------------------------------------------------------------
  $NsName = $DomainZone['NsName'];
  #-----------------------------------------------------------------------------
  if($NsName){
    #---------------------------------------------------------------------------
    if(Preg_Match_All(SPrintF('/%s/',$NsName),$Answer,$Mathes)){
      #-------------------------------------------------------------------------
      if(Count($Mathes) < 2)
        return ERROR | @Trigger_Error('[WhoIs_Check]: шаблон поиска именных серверов задан неверно');
      #-------------------------------------------------------------------------
      $NsNames = $Mathes[1];
      #-------------------------------------------------------------------------
      for($i=0;$i<Count($NsNames);$i++){
        #-----------------------------------------------------------------------
        $NsName = Trim(StrToLower($NsNames[$i]),'.');
        #-----------------------------------------------------------------------
        $Result[SPrintF('Ns%uName',$i+1)] = $NsName;
        #-----------------------------------------------------------------------
        if($NsName){
          #---------------------------------------------------------------------
          if(Mb_SubStr($NsName,-Mb_StrLen($Domain)) == $Domain){
            #-------------------------------------------------------------------
            $IP = GetHostByName($NsName);
            #-------------------------------------------------------------------
            if($IP != $NsName)
              $Result[SPrintF('Ns%uIP',$i+1)] = $IP;
          }
        }
      }
    }
  }
  #-----------------------------------------------------------------------------
  $Registrar = $DomainZone['Registrar'];
  #-----------------------------------------------------------------------------
  if($Registrar){
    #---------------------------------------------------------------------------
    if(Preg_Match(SPrintF('/%s/',$Registrar),$Answer,$Mathes)){
      #-------------------------------------------------------------------------
      if(Count($Mathes) < 2)
        return ERROR | @Trigger_Error('[WhoIs_Check]: шаблон поиска регистратора серверов задан неверно');
      #-------------------------------------------------------------------------
      $Registrar = Next($Mathes);
      #-------------------------------------------------------------------------
      $Result['Registrar'] = $Registrar;
    }
  }
  #-----------------------------------------------------------------------------
  return $Result;
}
#-------------------------------------------------------------------------------
?>
