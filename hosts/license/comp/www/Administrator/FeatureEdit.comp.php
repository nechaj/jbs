
#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$FeatureID = (integer) @$Args['FeatureID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Window')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title',$FeatureID?'Редактиварование изменения':'Добавление изменения');
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/Administrator/FeatureEdit.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'FeatureEditForm','onsubmit'=>'return false;'));
#-------------------------------------------------------------------------------
$Table = Array();
#-------------------------------------------------------------------------------
if($FeatureID){
  #-----------------------------------------------------------------------------
  $Feature = DB_Select('Features','*',Array('UNIQ','ID'=>$FeatureID));
  #-----------------------------------------------------------------------------
  switch(ValueOf($Feature)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      return ERROR | @Trigger_Error(400);
    case 'array':
      #-------------------------------------------------------------------------
      $Comp = Comp_Load(
        'Form/Input',
        Array(
          'name'   => 'FeatureID',
          'size'   => 30,
          'type'   => 'Hidden',
          'value'  => $Feature['ID']
        )
      );
      #-------------------------------------------------------------------------
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $Form->AddChild($Comp);
    break;
    default:
      return ERROR | @Trigger_Error(101);
  }
}else{
  #-----------------------------------------------------------------------------
  $Feature = Array(
    #---------------------------------------------------------------------------
    'CreateDate' => Time(),
    'VersionID'  => 1,
    'TypeID'     => 'new',
    'Title'      => 'Добавлена возможность',
    'Comment'    => 'Описание'
  );
}
#-------------------------------------------------------------------------------
$Options = Array('Требование');
#-------------------------------------------------------------------------------
$Versions = DB_Select('Versions',Array('ID','Name'),Array('SortOn'=>'CreateDate','IsDesc'=>TRUE));
#-------------------------------------------------------------------------------
switch(ValueOf($Versions)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    # No more...
  break;
  case 'array':
    #---------------------------------------------------------------------------
    foreach($Versions as $Version)
      $Options[$Version['ID']] = $Version['Name'];
    #---------------------------------------------------------------------------
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'VersionID'),$Options,$Feature['VersionID']);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Номер версии',$Comp);
#-------------------------------------------------------------------------------
$Options = Array('new'=>'Новая возможность','bug'=>'Исправление ошибки','update'=>'Улучшение');
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'TypeID'),$Options,$Feature['TypeID']);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Тип изменения',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'name'  => 'Title',
    'type'  => 'text',
    'size'  => 40,
    'value' => $Feature['Title']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Заголовок',$Comp);
#-------------------------------------------------------------------------------
$Table[] = 'Описание';
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/TextArea',
  Array(
    'name'  => 'Comment',
    'type'  => 'text',
    'rows'  => 10,
    'style' => 'width:100%;'
  ),
  $Feature['Comment']
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'button',
    'onclick' => 'FeatureEdit();',
    'value'   => ($FeatureID?'Сохранить':'Добавить')
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form->AddChild($Comp);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#-------------------------------------------------------------------------------
$Out = $DOM->Build(FALSE);
#-------------------------------------------------------------------------------
if(Is_Error($Out))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------