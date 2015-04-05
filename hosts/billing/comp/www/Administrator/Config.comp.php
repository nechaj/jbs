<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$Location =  (string) @$Args['Location'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('classes/DOM.class.php','modules/Authorisation.mod')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
#-------------------------------------------------------------------------------
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Base')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddAttribs('MenuLeft',Array('args'=>'Administrator/AddIns'));
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Дополнения → Мастера настройки → Конфигурация ');
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/Administrator/Config.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
function Config_Read($Array,$Path = Array(),$Level = 1){
  #-------------------------------------------------------------------------------
  # ASort added by lissyara for test purpose, 2014-01-16 in 15:22 MSK
  ASort($Array);
  #-------------------------------------------------------------------------------
  $TmpArray = Array();
  #-------------------------------------------------------------------------------
  $Names = Array('Name','IsActive','IsEvent','Valute','Course','Measure','IsCourseUpdate','MinimumPayment','MaximumPayment');
  #-------------------------------------------------------------------------------
  foreach($Names as $Name){
    #-------------------------------------------------------------------------------
    if(IsSet($Array[$Name])){
      #-------------------------------------------------------------------------------
      $TmpArray[$Name] = $Array[$Name];
      #-------------------------------------------------------------------------------
      UnSet($Array[$Name]);
      #-------------------------------------------------------------------------------
    }
    #-------------------------------------------------------------------------------
  }
  #-------------------------------------------------------------------------------
  $Array = $TmpArray + $Array;
  #-------------------------------------------------------------------------------
  #-----------------------------------------------------------------------------
  static $Index = 1;
  #-----------------------------------------------------------------------------
  $Links = &Links();
  #-----------------------------------------------------------------------------
  $ConfigNames = &$Links['ConfigNames'];
  #-----------------------------------------------------------------------------
  $Node = new Tag('DIV',Array('class'=>'Node'));
  #-----------------------------------------------------------------------------
  foreach(Array_Keys($Array) as $ElementID){
    #---------------------------------------------------------------------------
    $Element = $Array[$ElementID];
    #---------------------------------------------------------------------------
    $ID = SPrintF('ID%06u',$Index++);
    #---------------------------------------------------------------------------
    $StringPath = Implode('/',$CurrentPath = Array_Merge($Path,Array($ElementID)));
    #---------------------------------------------------------------------------
    if(IsSet($ConfigNames[$ElementID])){
      #-------------------------------------------------------------------------
      $Item = Explode('|',$ConfigNames[$ElementID]);
      #-------------------------------------------------------------------------
      if(IsSet($ConfigNames[SPrintF('Prompt.%s',$ElementID)]))
        $ElementPrompt = $ConfigNames[SPrintF('Prompt.%s',$ElementID)];
      #-------------------------------------------------------------------------
    }else{
      #-------------------------------------------------------------------------
      if(!IsSet($ConfigNames[$StringPath]))
        continue;
      #-------------------------------------------------------------------------
      $Item = Explode('|',$ConfigNames[$StringPath]);
      #-------------------------------------------------------------------------
      if(IsSet($ConfigNames[SPrintF('Prompt.%s',$StringPath)]))
        $ElementPrompt = $ConfigNames[SPrintF('Prompt.%s',$StringPath)];
      #-------------------------------------------------------------------------
    }
    #---------------------------------------------------------------------------
    $ElementName = Current($Item);
    #---------------------------------------------------------------------------
    if(Count($Item) > 1){
      #-------------------------------------------------------------------------
      $Type = Next($Item);
      #-------------------------------------------------------------------------
      switch($Type){
        case 'select':
          #---------------------------------------------------------------------
          $Select = Array();
          #---------------------------------------------------------------------
          $Options = Explode(',',Next($Item));
          #---------------------------------------------------------------------
          foreach($Options as $Option){
            #-------------------------------------------------------------------
            $Option = Explode('=',$Option);
            #-------------------------------------------------------------------
            $Select[Next($Option)] = Prev($Option);
          }
          #---------------------------------------------------------------------
          $Comp = Comp_Load('Form/Select',Array('onchange'=>SPrintF("ConfigChange('%s',this.value);",$StringPath)),$Select,$Element);
          if(Is_Error($Comp))
            return ERROR | @Trigger_Error(500);
        break;
        default:
          return ERROR | @Trigger_Error(101);
      }
    }else
      $Comp = new Tag('SPAN',Array('class'=>'NodeParam','onclick'=>SPrintF("Value = prompt('Значение1:',this.innerHTML.XMLUnEscape());if(Value != null){ ConfigChange('%s',Value); this.innerHTML = Value; }",$StringPath)),Is_Array($Element)?'[EMPTY]':(($Element == '')?'[EMPTY]':$Element));
    #---------------------------------------------------------------------------
    if(Is_Array($Element) && Count($Element)){
      #-------------------------------------------------------------------------
      $Result = Config_Read($Element,$CurrentPath,$Level+1);
      #-------------------------------------------------------------------------
      if($Result){
        #-----------------------------------------------------------------------
        $NodeName = new Tag('P',Array('class'=>'NodeName'),new Tag('IMG',Array('align'=>'left','src'=>'SRC:{Images/Icons/Node.gif}')));
        #-----------------------------------------------------------------------
        $NodeName->AddChild(new Tag('A',Array('href'=>SPrintF("javascript:ConfigSwitch('%s');",$ID)),$ElementName));
        #-----------------------------------------------------------------------
        $Node->AddChild($NodeName);
        #-----------------------------------------------------------------------
        $Node->AddChild(new Tag('DIV',Array('id'=>$ID,'style'=>'display:none;'),$Result));
      }
    }else{
      #-----------------------------------------------------------------------
      $Params = (IsSet($ElementPrompt))?Array('onMouseOver'=>SPrintF('PromptShow(event,\'%s\',this);',$ElementPrompt)):Array();
      #-----------------------------------------------------------------------
      $Node->AddChild(new Tag('P',Array('class'=>'NodeParam'),new Tag('SPAN',$Params,SPrintF('%s: ',$ElementName)),$Comp));
      #-----------------------------------------------------------------------
      UnSet($ElementPrompt);
      #-----------------------------------------------------------------------
    }
    #-----------------------------------------------------------------------
  }
  #-----------------------------------------------------------------------------
  return (Count($Node->Childs)?$Node:FALSE);
}
#-------------------------------------------------------------------------------
$ConfigNames = Array();
#-------------------------------------------------------------------------------
$HostsIDs = $GLOBALS['HOST_CONF']['HostsIDs'];
#-------------------------------------------------------------------------------
foreach(Array_Reverse($HostsIDs) as $HostID){
  #-----------------------------------------------------------------------------
  $Path = SPrintF('%s/hosts/%s/config/Config.ini',SYSTEM_PATH,$HostID);
  #-----------------------------------------------------------------------------
  if(File_Exists($Path)){
    #---------------------------------------------------------------------------
    $Adding = Parse_Ini_File($Path);
    if(!$Adding)
      return ERROR | Trigger_Error(500);
    #---------------------------------------------------------------------------
    Array_Union($ConfigNames,$Adding);
  }
}
#-------------------------------------------------------------------------------
$Links['ConfigNames'] = &$ConfigNames;
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Location = ($Location?Explode('-',$Location):Array());
#-------------------------------------------------------------------------------
foreach($Location as $Name)
  $Config = $Config[$Name];
#-------------------------------------------------------------------------------
$Into = Config_Read($Config,$Location);
#-------------------------------------------------------------------------------
$Into = Comp_Load('Tab','Administrator/Masters',$Into);
if(Is_Error($Into))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Into);
#-------------------------------------------------------------------------------
$Out = $DOM->Build();
#-------------------------------------------------------------------------------
if(Is_Error($Out))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return $Out;
#-------------------------------------------------------------------------------

?>