//------------------------------------------------------------------------------
/** @author Бреславский А.В. (Joonte Ltd.) */
//------------------------------------------------------------------------------
function ClauseEdit(){
  //----------------------------------------------------------------------------
  var $Form = document.forms['ClauseEditForm'];
  //----------------------------------------------------------------------------
  $HTTP = new HTTP();
  //----------------------------------------------------------------------------
  if(!$HTTP.Resource){
    //--------------------------------------------------------------------------
    alert('Не удалось создать HTTP соединение');
    //--------------------------------------------------------------------------
    return false;
  }
  //----------------------------------------------------------------------------
  $HTTP.onLoaded = function(){
    //--------------------------------------------------------------------------
    HideProgress();
  }
  //----------------------------------------------------------------------------
  $HTTP.onAnswer = function($Answer){
    //--------------------------------------------------------------------------
    switch($Answer.Status){
      case 'Error':
        ShowAlert($Answer.Error.String,'Warning');
      break;
      case 'Exception':
        ShowAlert(ExceptionsStack($Answer.Exception),'Warning');
      break;
      case 'Ok':
        //----------------------------------------------------------------------
        if($Form.ClauseID){
          //--------------------------------------------------------------------
          ShowTick('Статья успешно сохранена');
          //--------------------------------------------------------------------
          if(!document.forms['ClauseEditForm'].IsReturn.checked)
            return;
          //--------------------------------------------------------------------
          window.close();
          //--------------------------------------------------------------------
          GetURL(opener.document.location,opener);
        }else
           GetURL('/Administrator/ClauseEdit?ClauseID='+$Answer.ClauseID);
      break;
      default:
        alert('Не известный ответ');
    }
  }
  //----------------------------------------------------------------------------
  $Form.Text.value = GetWYSIWYG();
  //----------------------------------------------------------------------------
  var $Args = FormGet($Form);
  //----------------------------------------------------------------------------
  if(!$HTTP.Send('/Administrator/API/ClauseEdit',$Args)){
    //--------------------------------------------------------------------------
    alert('Не удалось отправить запрос на сервер');
    //--------------------------------------------------------------------------
    return false;
  }
  //----------------------------------------------------------------------------
  ShowProgress('Сохранение статьи');
}
//------------------------------------------------------------------------------
SaveWYSIWYG = ClauseEdit;
//------------------------------------------------------------------------------
function ClauseFileEdit(){
  //----------------------------------------------------------------------------
  var $Form = document.forms['ClauseFileEditForm'];
  //----------------------------------------------------------------------------
  $HTTP = new HTTP();
  //----------------------------------------------------------------------------
  if(!$HTTP.Resource){
    //--------------------------------------------------------------------------
    alert('Не удалось создать HTTP соединение');
    //--------------------------------------------------------------------------
    return false;
  }
  //----------------------------------------------------------------------------
  $HTTP.onLoaded = function(){
    //--------------------------------------------------------------------------
    HideProgress();
  }
  //----------------------------------------------------------------------------
  $HTTP.onAnswer = function($Answer){
    //--------------------------------------------------------------------------
    switch($Answer.Status){
      case 'Exception':
        ShowAlert(ExceptionsStack($Answer.Exception),'Warning');
      break;
      case 'Ok':
        //----------------------------------------------------------------------
        ShowTick('Файл успешно добавлен');
        //----------------------------------------------------------------------
        window.frames['ClauseFiles'].document.location.reload();
      break;
      default:
        alert('Не известный ответ');
    }
  }
  //----------------------------------------------------------------------------
  var $Args = FormGet($Form);
  //----------------------------------------------------------------------------
  $Args.ClauseID = document.forms['ClauseEditForm'].ClauseID.value;
  //----------------------------------------------------------------------------
  if(!$HTTP.Send('/Administrator/API/ClauseFileEdit',$Args)){
    //--------------------------------------------------------------------------
    alert('Не удалось отправить запрос на сервер');
    //--------------------------------------------------------------------------
    return false;
  }
  //----------------------------------------------------------------------------
  ShowProgress('Добавление файла статьи');
}