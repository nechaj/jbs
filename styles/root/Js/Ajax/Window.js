//------------------------------------------------------------------------------
/** @author Бреславский А.В. (Joonte Ltd.) */
//------------------------------------------------------------------------------
var $WindowOnLoad = null;
//------------------------------------------------------------------------------
var $WindowPostElementsLoaded = 0;
//------------------------------------------------------------------------------
var $WindowPosition = 0;
//------------------------------------------------------------------------------
var $WindowHistory = [];
//------------------------------------------------------------------------------
function ShowWindow($Url,$Args){
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
      case 'Eval':
        eval($Answer.Eval);
      break;
      case 'Ok':
        //----------------------------------------------------------------------
        LockPage('Window');
        //----------------------------------------------------------------------
        var $Window = document.getElementById('Window');
        //----------------------------------------------------------------------
        with($Window.style){
          //--------------------------------------------------------------------
          display = 'none';
          left    = -1000;
          top     = -1000;
        }
        //----------------------------------------------------------------------
        var $DOM = new DOM($Answer.DOM);
        //----------------------------------------------------------------------
        var $Collection = $DOM.Links.Floating.Childs;
        //----------------------------------------------------------------------
        for(var $i=0;$i<$Collection.length;$i++){
          //--------------------------------------------------------------------
          var $Element = $Collection[$i];
          //--------------------------------------------------------------------
          if(!document.getElementById($Element.Attribs.id))
            document.getElementById('Floating').innerHTML += $Element.ToXMLString();
        }
        //----------------------------------------------------------------------
        var $Collection = $DOM.Links.Head.Childs;
        //----------------------------------------------------------------------
        var $PostElementsLoaded = [];
        //----------------------------------------------------------------------
        var $ExistsElements = document.getElementsByTagName('SCRIPT');
        //----------------------------------------------------------------------
        for(var $i=0;$i<$Collection.length;$i++){
          //--------------------------------------------------------------------
          var $Element = $Collection[$i];
          //--------------------------------------------------------------------
          if(!['LINK','STYLE','SCRIPT'].IsExists($Element.Name))
            continue;
          //--------------------------------------------------------------------
          var $Adding = document.createElement($Element.Name);
          //--------------------------------------------------------------------
          var $Attribs = $Element.Attribs;
          //--------------------------------------------------------------------
          if($Attribs.src){
            //------------------------------------------------------------------
            var $IsElementExists = false;
            //------------------------------------------------------------------
            for(var $j=0;$j<$ExistsElements.length;$j++){
              //----------------------------------------------------------------
              var $Src = $ExistsElements[$j].src;
              //----------------------------------------------------------------
              if($Src.substring($Src.length-$Element.Attribs.src.length) == $Element.Attribs.src){
                //--------------------------------------------------------------
                $IsElementExists = true;
                //--------------------------------------------------------------
                break;
              }
            }
            //------------------------------------------------------------------
            if(!$IsElementExists)
              $PostElementsLoaded.push($Element);
            else
              Debug('Элемент уже был загружен: '+$Attribs.src);
            //------------------------------------------------------------------
            continue;
          }
          //--------------------------------------------------------------------
          for(var $AttribID in $Attribs)
            $Adding[$AttribID] = $Attribs[$AttribID];
          //--------------------------------------------------------------------
          if($Element.Text)
            $Adding.text = $Element.Text;
          //--------------------------------------------------------------------
          document.body.appendChild($Adding);
        }
        //----------------------------------------------------------------------
        $WindowOnLoad = ($DOM.Links.Body.Attribs.onload?$DOM.Links.Body.Attribs.onload:null);
        //----------------------------------------------------------------------
        $WindowPostElementsLoaded = $PostElementsLoaded.length;
        //----------------------------------------------------------------------
        if($WindowPostElementsLoaded > 0){
          //--------------------------------------------------------------------
          for(var $i=0;$i<$WindowPostElementsLoaded;$i++){
            //------------------------------------------------------------------
            var $Element = $PostElementsLoaded[$i];
            //------------------------------------------------------------------
            var $Adding = document.createElement($Element.Name);
            //------------------------------------------------------------------
            var $Attribs = $Element.Attribs;
            //------------------------------------------------------------------
            for(var $AttribID in $Attribs)
              $Adding[$AttribID] = $Attribs[$AttribID];
            //------------------------------------------------------------------
            if($BrouserID == 'MSIE')
              $Adding.onreadystatechange = WindowElementLoadedMSIE;
            else
              $Adding.onload = WindowElementLoaded;
            //------------------------------------------------------------------
            SetProgress($i,$WindowPostElementsLoaded);
            //------------------------------------------------------------------
            document.body.appendChild($Adding);
          }
        }
        //----------------------------------------------------------------------
        var $Title = $DOM.Links.Title.Text;
        //----------------------------------------------------------------------
        document.getElementById('WindowTitle').innerHTML = ($Title.length > 50?$Title.substr(0,50):$Title);
        //----------------------------------------------------------------------
        var $HTML = '';
        //----------------------------------------------------------------------
        var $Into = $DOM.Links.Into;
        //----------------------------------------------------------------------
        for(var $i=0;$i<$Into.Childs.length;$i++)
          $HTML += $Into.Childs[$i].ToXMLString();
        //----------------------------------------------------------------------
        document.getElementById('WindowBody').innerHTML = $HTML;
        //----------------------------------------------------------------------
        var $Window = document.getElementById('Window');
        //----------------------------------------------------------------------
        with($Window){
          //--------------------------------------------------------------------
          style.zIndex  = GetMaxZIndex() + 1;
          style.display = 'block';
          //--------------------------------------------------------------------
          var $WindowBody = document.getElementById('WindowBody');
          //--------------------------------------------------------------------
          with($WindowBody){
            //------------------------------------------------------------------
            style.width  = '';
            style.height = '';
            //------------------------------------------------------------------
            //$wMax = document.body.clientWidth - 150;
            //$hMax = document.body.clientHeight - 100;
            $wMax = document.body.clientWidth - 10;
            $hMax = document.body.clientHeight - 10;
            //------------------------------------------------------------------
            var $IsWidthResize = (offsetWidth > $wMax);
            //------------------------------------------------------------------
            if(offsetHeight > $hMax || $IsWidthResize){
              //----------------------------------------------------------------
              if($IsWidthResize)
                style.width = $wMax;
              //----------------------------------------------------------------
              var $Adding = document.createElement('DIV');
              //----------------------------------------------------------------
              with($Adding.style) {
                //--------------------------------------------------------------
                height    = '100%';
                width     = '100%';
                overflow  = 'scroll';
                overflowX = 'auto';
                //--------------------------------------------------------------
                style.width = offsetWidth + 15;
              }
              //----------------------------------------------------------------
              $Adding.innerHTML = innerHTML.Replacing('\\"','&quot;');
              //----------------------------------------------------------------
              innerHTML = '';
              //----------------------------------------------------------------
              appendChild($Adding);
              //----------------------------------------------------------------
              style.height = Math.min(offsetHeight,$hMax);
            }
          }
          //--------------------------------------------------------------------
          var $Body = document.body;
          //--------------------------------------------------------------------
          style.left = $Body.scrollLeft + ($Body.clientWidth  - $WindowBody.offsetWidth)/2;
          style.top  = $Body.scrollTop + Math.max(($Body.clientHeight - $WindowBody.offsetHeight)/2-20,10);
        }
        //----------------------------------------------------------------------
        if(!$WindowPostElementsLoaded){
          //--------------------------------------------------------------------
          if($WindowOnLoad)
            eval($WindowOnLoad);
        }
        //----------------------------------------------------------------------
        $WindowHistory.push({Url:$Url,Args:$Args});
        //----------------------------------------------------------------------
        $WindowPosition = $WindowHistory.length-1;
      break;
      default:
        alert('Не известный ответ');
    }
  };
  //----------------------------------------------------------------------------
  if(!$HTTP.Send($Url,$Args)){
    //--------------------------------------------------------------------------
    alert('Не удалось отправить запрос на сервер');
    //--------------------------------------------------------------------------
    return false;
  }
  //----------------------------------------------------------------------------
  ShowProgress('Пожалуйста, подождите');
}
//------------------------------------------------------------------------------
function WindowElementLoaded($Event){
  //----------------------------------------------------------------------------
  Debug('Загружен элемент: '+$Event.currentTarget.src);
  //----------------------------------------------------------------------------
  $WindowPostElementsLoaded -= 1;
  //----------------------------------------------------------------------------
  if(!$WindowPostElementsLoaded){
    //--------------------------------------------------------------------------
    if($WindowOnLoad)
      eval($WindowOnLoad);
  }
  //----------------------------------------------------------------------------
  $Event.currentTarget.onload = null;
}
//------------------------------------------------------------------------------
function WindowElementLoadedMSIE($Event){
  //----------------------------------------------------------------------------
  if(this.readyState != 'loaded')
    return null;
  //----------------------------------------------------------------------------
  $WindowPostElementsLoaded -= 1;
  //----------------------------------------------------------------------------
  if(!$WindowPostElementsLoaded){
    //--------------------------------------------------------------------------
    if($WindowOnLoad)
      eval($WindowOnLoad);
  }
}
//------------------------------------------------------------------------------
function HideWindow(){
  //----------------------------------------------------------------------------
  with(document.getElementById('Window').style){
    //--------------------------------------------------------------------------
    display = 'none';
    left    = -1000;
    top     = -1000;
  }
  //----------------------------------------------------------------------------
  UnLockPage('Window');
}
//------------------------------------------------------------------------------
function WindowPrev(){
  //----------------------------------------------------------------------------
  var $Current = $WindowHistory[--$WindowPosition];
  //----------------------------------------------------------------------------
  ShowWindow($Current.Url,$Current.Args);
}
