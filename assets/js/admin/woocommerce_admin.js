/*
 * jQuery MultiSelect UI Widget 1.11pre
 * Copyright (c) 2011 Eric Hynds
 *
 * http://www.erichynds.com/jquery/jquery-ui-multiselect-widget/
 *
 * Depends:
 *   - jQuery 1.4.2+
 *   - jQuery UI 1.8 widget factory
 *
 * Optional:
 *   - jQuery UI effects
 *   - jQuery UI position utility
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
*/
(function($,undefined){var multiselectID=0;$.widget("ech.multiselect",{options:{header:true,height:175,minWidth:225,classes:'',checkAllText:'Check all',uncheckAllText:'Uncheck all',noneSelectedText:'Select options',selectedText:'# selected',selectedList:0,show:'',hide:'',autoOpen:false,multiple:true,position:{}},_create:function(){var el=this.element.hide(),o=this.options;this.speed=$.fx.speeds._default;this._isOpen=false;var
button=(this.button=$('<button type="button"><span class="ui-icon ui-icon-triangle-2-n-s"></span></button>')).addClass('ui-multiselect ui-widget ui-state-default ui-corner-all').addClass(o.classes).attr({'title':el.attr('title'),'aria-haspopup':true,'tabIndex':el.attr('tabIndex')}).insertAfter(el),buttonlabel=(this.buttonlabel=$('<span />')).html(o.noneSelectedText).appendTo(button),menu=(this.menu=$('<div />')).addClass('ui-multiselect-menu ui-widget ui-widget-content ui-corner-all').addClass(o.classes).insertAfter(button),header=(this.header=$('<div />')).addClass('ui-widget-header ui-corner-all ui-multiselect-header ui-helper-clearfix').appendTo(menu),headerLinkContainer=(this.headerLinkContainer=$('<ul />')).addClass('ui-helper-reset').html(function(){if(o.header===true){return'<li><a class="ui-multiselect-all" href="#"><span class="ui-icon ui-icon-check"></span><span>'+o.checkAllText+'</span></a></li><li><a class="ui-multiselect-none" href="#"><span class="ui-icon ui-icon-closethick"></span><span>'+o.uncheckAllText+'</span></a></li>';}else if(typeof o.header==="string"){return'<li>'+o.header+'</li>';}else{return'';}}).append('<li class="ui-multiselect-close"><a href="#" class="ui-multiselect-close"><span class="ui-icon ui-icon-circle-close"></span></a></li>').appendTo(header),checkboxContainer=(this.checkboxContainer=$('<ul />')).addClass('ui-multiselect-checkboxes ui-helper-reset').appendTo(menu);this._bindEvents();this.refresh(true);if(!o.multiple){menu.addClass('ui-multiselect-single');}},_init:function(){if(this.options.header===false){this.header.hide();}
if(!this.options.multiple){this.headerLinkContainer.find('.ui-multiselect-all, .ui-multiselect-none').hide();}
if(this.options.autoOpen){this.open();}
if(this.element.is(':disabled')){this.disable();}},refresh:function(init){var el=this.element,o=this.options,menu=this.menu,checkboxContainer=this.checkboxContainer,optgroups=[],html=[],id=el.attr('id')||multiselectID++;this.element.find('option').each(function(i){var $this=$(this),parent=this.parentNode,title=this.innerHTML,description=this.title,value=this.value,inputID=this.id||'ui-multiselect-'+id+'-option-'+i,isDisabled=this.disabled,isSelected=this.selected,labelClasses=['ui-corner-all'],optLabel;if(parent.tagName.toLowerCase()==='optgroup'){optLabel=parent.getAttribute('label');if($.inArray(optLabel,optgroups)===-1){html.push('<li class="ui-multiselect-optgroup-label"><a href="#">'+optLabel+'</a></li>');optgroups.push(optLabel);}}
if(isDisabled){labelClasses.push('ui-state-disabled');}
if(isSelected&&!o.multiple){labelClasses.push('ui-state-active');}
html.push('<li class="'+(isDisabled?'ui-multiselect-disabled':'')+'">');html.push('<label for="'+inputID+'" title="'+description+'" class="'+labelClasses.join(' ')+'">');html.push('<input id="'+inputID+'" name="multiselect_'+id+'" type="'+(o.multiple?"checkbox":"radio")+'" value="'+value+'" title="'+title+'"');if(isSelected){html.push(' checked="checked"');html.push(' aria-selected="true"');}
if(isDisabled){html.push(' disabled="disabled"');html.push(' aria-disabled="true"');}
html.push(' /><span>'+title+'</span></label></li>');if(parent.tagName.toLowerCase()==='optgroup'){var $next_parent=$this.next('option').parent();if(!$next_parent.is('optgroup')){html.push('<li class="ui-multiselect-optgroup-last"></li>');}}});checkboxContainer.html(html.join(''));this.labels=menu.find('label');this._setButtonWidth();this._setMenuWidth();this.button[0].defaultValue=this.update();if(!init){this._trigger('refresh');}},update:function(){var o=this.options,$inputs=this.labels.find('input'),$checked=$inputs.filter(':checked'),numChecked=$checked.length,value;if(numChecked===0){value=o.noneSelectedText;}else{if($.isFunction(o.selectedText)){value=o.selectedText.call(this,numChecked,$inputs.length,$checked.get());}else if(/\d/.test(o.selectedList)&&o.selectedList>0&&numChecked<=o.selectedList){value=$checked.map(function(){return this.title;}).get().join(', ');}else{value=o.selectedText.replace('#',numChecked).replace('#',$inputs.length);}}
this.buttonlabel.html(value);return value;},_bindEvents:function(){var self=this,button=this.button;function clickHandler(){self[self._isOpen?'close':'open']();return false;}
button.find('span').bind('click.multiselect',clickHandler);button.bind({click:clickHandler,keypress:function(e){switch(e.which){case 27:case 38:case 37:self.close();break;case 39:case 40:self.open();break;}},mouseenter:function(){if(!button.hasClass('ui-state-disabled')){$(this).addClass('ui-state-hover');}},mouseleave:function(){$(this).removeClass('ui-state-hover');},focus:function(){if(!button.hasClass('ui-state-disabled')){$(this).addClass('ui-state-focus');}},blur:function(){$(this).removeClass('ui-state-focus');}});this.header.delegate('a','click.multiselect',function(e){if($(this).hasClass('ui-multiselect-close')){self.close();}else{self[$(this).hasClass('ui-multiselect-all')?'checkAll':'uncheckAll']();}
e.preventDefault();});this.menu.delegate('li.ui-multiselect-optgroup-label a','click.multiselect',function(e){e.preventDefault();var $this=$(this),$inputs=$this.parent().nextUntil('li.ui-multiselect-optgroup-label, li.ui-multiselect-optgroup-last').find('input:visible:not(:disabled)'),nodes=$inputs.get(),label=$this.parent().text();if(self._trigger('beforeoptgrouptoggle',e,{inputs:nodes,label:label})===false){return;}
self._toggleChecked($inputs.filter(':checked').length!==$inputs.length,$inputs);self._trigger('optgrouptoggle',e,{inputs:nodes,label:label,checked:nodes[0].checked});}).delegate('label','mouseenter.multiselect',function(){if(!$(this).hasClass('ui-state-disabled')){self.labels.removeClass('ui-state-hover');$(this).addClass('ui-state-hover').find('input').focus();}}).delegate('label','keydown.multiselect',function(e){e.preventDefault();switch(e.which){case 9:case 27:self.close();break;case 38:case 40:case 37:case 39:self._traverse(e.which,this);break;case 13:$(this).find('input')[0].click();break;}}).delegate('input[type="checkbox"], input[type="radio"]','click.multiselect',function(e){var $this=$(this),val=this.value,checked=this.checked,tags=self.element.find('option');if(this.disabled||self._trigger('click',e,{value:val,text:this.title,checked:checked})===false){e.preventDefault();return;}
$this.attr('aria-selected',checked);tags.each(function(){if(this.value===val){this.selected=checked;if(checked){this.setAttribute('selected','selected');}else{this.removeAttribute('selected');}}else if(!self.options.multiple){this.selected=false;}});if(!self.options.multiple){self.labels.removeClass('ui-state-active');$this.closest('label').toggleClass('ui-state-active',checked);self.close();}
self.element.trigger("change");setTimeout($.proxy(self.update,self),10);});$(document).bind('mousedown.multiselect',function(e){if(self._isOpen&&!$.contains(self.menu[0],e.target)&&!$.contains(self.button[0],e.target)&&e.target!==self.button[0]){self.close();}});$(this.element[0].form).bind('reset.multiselect',function(){setTimeout(function(){self.update();},10);});},_setButtonWidth:function(){var width=this.element.outerWidth(),o=this.options;if(/\d/.test(o.minWidth)&&width<o.minWidth){width=o.minWidth;}
this.button.width(width);},_setMenuWidth:function(){var m=this.menu,width=this.button.outerWidth()-
parseInt(m.css('padding-left'),10)-
parseInt(m.css('padding-right'),10)-
parseInt(m.css('border-right-width'),10)-
parseInt(m.css('border-left-width'),10);m.width(width||this.button.outerWidth());},_traverse:function(which,start){var $start=$(start),moveToLast=which===38||which===37,$next=$start.parent()[moveToLast?'prevAll':'nextAll']('li:not(.ui-multiselect-disabled, .ui-multiselect-optgroup-label)')[moveToLast?'last':'first']();if(!$next.length){var $container=this.menu.find('ul:last');this.menu.find('label')[moveToLast?'last':'first']().trigger('mouseover');$container.scrollTop(moveToLast?$container.height():0);}else{$next.find('label').trigger('mouseover');}},_toggleCheckbox:function(prop,flag){return function(){!this.disabled&&(this[prop]=flag);if(flag){this.setAttribute('aria-selected',true);}else{this.removeAttribute('aria-selected');}}},_toggleChecked:function(flag,group){var $inputs=(group&&group.length)?group:this.labels.find('input'),self=this;$inputs.each(this._toggleCheckbox('checked',flag));this.update();var values=$inputs.map(function(){return this.value;}).get();this.element.find('option').each(function(){if(!this.disabled&&$.inArray(this.value,values)>-1){self._toggleCheckbox('selected',flag).call(this);}});if($inputs.length){this.element.trigger("change");}},_toggleDisabled:function(flag){this.button.attr({'disabled':flag,'aria-disabled':flag})[flag?'addClass':'removeClass']('ui-state-disabled');this.menu.find('input').attr({'disabled':flag,'aria-disabled':flag}).parent()[flag?'addClass':'removeClass']('ui-state-disabled');this.element.attr({'disabled':flag,'aria-disabled':flag});},open:function(e){var self=this,button=this.button,menu=this.menu,speed=this.speed,o=this.options;if(this._trigger('beforeopen')===false||button.hasClass('ui-state-disabled')||this._isOpen){return;}
var $container=menu.find('ul:last'),effect=o.show,pos=button.position();if($.isArray(o.show)){effect=o.show[0];speed=o.show[1]||self.speed;}
$container.scrollTop(0).height(o.height);if($.ui.position&&!$.isEmptyObject(o.position)){o.position.of=o.position.of||button;menu.show().position(o.position).hide().show(effect,speed);}else{menu.css({top:pos.top+button.outerHeight(),left:pos.left}).show(effect,speed);}
this.labels.eq(0).trigger('mouseover').trigger('mouseenter').find('input').trigger('focus');button.addClass('ui-state-active');this._isOpen=true;this._trigger('open');},close:function(){if(this._trigger('beforeclose')===false){return;}
var o=this.options,effect=o.hide,speed=this.speed;if($.isArray(o.hide)){effect=o.hide[0];speed=o.hide[1]||this.speed;}
this.menu.hide(effect,speed);this.button.removeClass('ui-state-active').trigger('blur').trigger('mouseleave');this._isOpen=false;this._trigger('close');},enable:function(){this._toggleDisabled(false);},disable:function(){this._toggleDisabled(true);},checkAll:function(e){this._toggleChecked(true);this._trigger('checkAll');},uncheckAll:function(){this._toggleChecked(false);this._trigger('uncheckAll');},getChecked:function(){return this.menu.find('input').filter(':checked');},destroy:function(){$.Widget.prototype.destroy.call(this);this.button.remove();this.menu.remove();this.element.show();return this;},isOpen:function(){return this._isOpen;},widget:function(){return this.menu;},_setOption:function(key,value){var menu=this.menu;switch(key){case'header':menu.find('div.ui-multiselect-header')[value?'show':'hide']();break;case'checkAllText':menu.find('a.ui-multiselect-all span').eq(-1).text(value);break;case'uncheckAllText':menu.find('a.ui-multiselect-none span').eq(-1).text(value);break;case'height':menu.find('ul:last').height(parseInt(value,10));break;case'minWidth':this.options[key]=parseInt(value,10);this._setButtonWidth();this._setMenuWidth();break;case'selectedText':case'selectedList':case'noneSelectedText':this.options[key]=value;this.update();break;case'classes':menu.add(this.button).removeClass(this.options.classes).addClass(value);break;}
$.Widget.prototype._setOption.apply(this,arguments);}});})(jQuery);

/*!
  jQuery blockUI plugin
  Version 2.37 (29-JAN-2011)
  @requires jQuery v1.2.3 or later
 
  Examples at: http://malsup.com/jquery/block/
  Copyright (c) 2007-2010 M. Alsup
  Dual licensed under the MIT and GPL licenses:
  http://www.opensource.org/licenses/mit-license.php
  http://www.gnu.org/licenses/gpl.html
 
  Thanks to Amir-Hossein Sobhi for some excellent contributions!
 */
;(function($){if(/1\.(0|1|2)\.(0|1|2)/.test($.fn.jquery)||/^1.1/.test($.fn.jquery)){alert('blockUI requires jQuery v1.2.3 or later!  You are using v'+$.fn.jquery);return;}
$.fn._fadeIn=$.fn.fadeIn;var noOp=function(){};var mode=document.documentMode||0;var setExpr=$.browser.msie&&(($.browser.version<8&&!mode)||mode<8);var ie6=$.browser.msie&&/MSIE 6.0/.test(navigator.userAgent)&&!mode;$.blockUI=function(opts){install(window,opts);};$.unblockUI=function(opts){remove(window,opts);};$.growlUI=function(title,message,timeout,onClose){var $m=$('<div class="growlUI"></div>');if(title)$m.append('<h1>'+title+'</h1>');if(message)$m.append('<h2>'+message+'</h2>');if(timeout==undefined)timeout=3000;$.blockUI({message:$m,fadeIn:700,fadeOut:1000,centerY:false,timeout:timeout,showOverlay:false,onUnblock:onClose,css:$.blockUI.defaults.growlCSS});};$.fn.block=function(opts){return this.unblock({fadeOut:0}).each(function(){if($.css(this,'position')=='static')
this.style.position='relative';if($.browser.msie)
this.style.zoom=1;install(this,opts);});};$.fn.unblock=function(opts){return this.each(function(){remove(this,opts);});};$.blockUI.version=2.37;$.blockUI.defaults={message:'<h1>Please wait...</h1>',title:null,draggable:true,theme:false,css:{padding:0,margin:0,width:'30%',top:'40%',left:'35%',textAlign:'center',color:'#000',border:'3px solid #aaa',backgroundColor:'#fff',cursor:'wait'},themedCSS:{width:'30%',top:'40%',left:'35%'},overlayCSS:{backgroundColor:'#000',opacity:0.6,cursor:'wait'},growlCSS:{width:'350px',top:'10px',left:'',right:'10px',border:'none',padding:'5px',opacity:0.6,cursor:'default',color:'#fff',backgroundColor:'#000','-webkit-border-radius':'10px','-moz-border-radius':'10px','border-radius':'10px'},iframeSrc:/^https/i.test(window.location.href||'')?'javascript:false':'about:blank',forceIframe:false,baseZ:1000,centerX:true,centerY:true,allowBodyStretch:true,bindEvents:true,constrainTabKey:true,fadeIn:200,fadeOut:400,timeout:0,showOverlay:true,focusInput:true,applyPlatformOpacityRules:true,onBlock:null,onUnblock:null,quirksmodeOffsetHack:4,blockMsgClass:'blockMsg'};var pageBlock=null;var pageBlockEls=[];function install(el,opts){var full=(el==window);var msg=opts&&opts.message!==undefined?opts.message:undefined;opts=$.extend({},$.blockUI.defaults,opts||{});opts.overlayCSS=$.extend({},$.blockUI.defaults.overlayCSS,opts.overlayCSS||{});var css=$.extend({},$.blockUI.defaults.css,opts.css||{});var themedCSS=$.extend({},$.blockUI.defaults.themedCSS,opts.themedCSS||{});msg=msg===undefined?opts.message:msg;if(full&&pageBlock)
remove(window,{fadeOut:0});if(msg&&typeof msg!='string'&&(msg.parentNode||msg.jquery)){var node=msg.jquery?msg[0]:msg;var data={};$(el).data('blockUI.history',data);data.el=node;data.parent=node.parentNode;data.display=node.style.display;data.position=node.style.position;if(data.parent)
data.parent.removeChild(node);}
var z=opts.baseZ;var lyr1=($.browser.msie||opts.forceIframe)?$('<iframe class="blockUI" style="z-index:'+(z++)+';display:none;border:none;margin:0;padding:0;position:absolute;width:100%;height:100%;top:0;left:0" src="'+opts.iframeSrc+'"></iframe>'):$('<div class="blockUI" style="display:none"></div>');var lyr2=$('<div class="blockUI blockOverlay" style="z-index:'+(z++)+';display:none;border:none;margin:0;padding:0;width:100%;height:100%;top:0;left:0"></div>');var lyr3,s;if(opts.theme&&full){s='<div class="blockUI '+opts.blockMsgClass+' blockPage ui-dialog ui-widget ui-corner-all" style="z-index:'+z+';display:none;position:fixed">'+'<div class="ui-widget-header ui-dialog-titlebar ui-corner-all blockTitle">'+(opts.title||'&nbsp;')+'</div>'+'<div class="ui-widget-content ui-dialog-content"></div>'+'</div>';}
else if(opts.theme){s='<div class="blockUI '+opts.blockMsgClass+' blockElement ui-dialog ui-widget ui-corner-all" style="z-index:'+z+';display:none;position:absolute">'+'<div class="ui-widget-header ui-dialog-titlebar ui-corner-all blockTitle">'+(opts.title||'&nbsp;')+'</div>'+'<div class="ui-widget-content ui-dialog-content"></div>'+'</div>';}
else if(full){s='<div class="blockUI '+opts.blockMsgClass+' blockPage" style="z-index:'+z+';display:none;position:fixed"></div>';}
else{s='<div class="blockUI '+opts.blockMsgClass+' blockElement" style="z-index:'+z+';display:none;position:absolute"></div>';}
lyr3=$(s);if(msg){if(opts.theme){lyr3.css(themedCSS);lyr3.addClass('ui-widget-content');}
else
lyr3.css(css);}
if(!opts.applyPlatformOpacityRules||!($.browser.mozilla&&/Linux/.test(navigator.platform)))
lyr2.css(opts.overlayCSS);lyr2.css('position',full?'fixed':'absolute');if($.browser.msie||opts.forceIframe)
lyr1.css('opacity',0.0);var layers=[lyr1,lyr2,lyr3],$par=full?$('body'):$(el);$.each(layers,function(){this.appendTo($par);});if(opts.theme&&opts.draggable&&$.fn.draggable){lyr3.draggable({handle:'.ui-dialog-titlebar',cancel:'li'});}
var expr=setExpr&&(!$.boxModel||$('object,embed',full?null:el).length>0);if(ie6||expr){if(full&&opts.allowBodyStretch&&$.boxModel)
$('html,body').css('height','100%');if((ie6||!$.boxModel)&&!full){var t=sz(el,'borderTopWidth'),l=sz(el,'borderLeftWidth');var fixT=t?'(0 - '+t+')':0;var fixL=l?'(0 - '+l+')':0;}
$.each([lyr1,lyr2,lyr3],function(i,o){var s=o[0].style;s.position='absolute';if(i<2){full?s.setExpression('height','Math.max(document.body.scrollHeight, document.body.offsetHeight) - (jQuery.boxModel?0:'+opts.quirksmodeOffsetHack+') + "px"'):s.setExpression('height','this.parentNode.offsetHeight + "px"');full?s.setExpression('width','jQuery.boxModel && document.documentElement.clientWidth || document.body.clientWidth + "px"'):s.setExpression('width','this.parentNode.offsetWidth + "px"');if(fixL)s.setExpression('left',fixL);if(fixT)s.setExpression('top',fixT);}
else if(opts.centerY){if(full)s.setExpression('top','(document.documentElement.clientHeight || document.body.clientHeight) / 2 - (this.offsetHeight / 2) + (blah = document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop) + "px"');s.marginTop=0;}
else if(!opts.centerY&&full){var top=(opts.css&&opts.css.top)?parseInt(opts.css.top):0;var expression='((document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop) + '+top+') + "px"';s.setExpression('top',expression);}});}
if(msg){if(opts.theme)
lyr3.find('.ui-widget-content').append(msg);else
lyr3.append(msg);if(msg.jquery||msg.nodeType)
$(msg).show();}
if(($.browser.msie||opts.forceIframe)&&opts.showOverlay)
lyr1.show();if(opts.fadeIn){var cb=opts.onBlock?opts.onBlock:noOp;var cb1=(opts.showOverlay&&!msg)?cb:noOp;var cb2=msg?cb:noOp;if(opts.showOverlay)
lyr2._fadeIn(opts.fadeIn,cb1);if(msg)
lyr3._fadeIn(opts.fadeIn,cb2);}
else{if(opts.showOverlay)
lyr2.show();if(msg)
lyr3.show();if(opts.onBlock)
opts.onBlock();}
bind(1,el,opts);if(full){pageBlock=lyr3[0];pageBlockEls=$(':input:enabled:visible',pageBlock);if(opts.focusInput)
setTimeout(focus,20);}
else
center(lyr3[0],opts.centerX,opts.centerY);if(opts.timeout){var to=setTimeout(function(){full?$.unblockUI(opts):$(el).unblock(opts);},opts.timeout);$(el).data('blockUI.timeout',to);}};function remove(el,opts){var full=(el==window);var $el=$(el);var data=$el.data('blockUI.history');var to=$el.data('blockUI.timeout');if(to){clearTimeout(to);$el.removeData('blockUI.timeout');}
opts=$.extend({},$.blockUI.defaults,opts||{});bind(0,el,opts);var els;if(full)
els=$('body').children().filter('.blockUI').add('body > .blockUI');else
els=$('.blockUI',el);if(full)
pageBlock=pageBlockEls=null;if(opts.fadeOut){els.fadeOut(opts.fadeOut);setTimeout(function(){reset(els,data,opts,el);},opts.fadeOut);}
else
reset(els,data,opts,el);};function reset(els,data,opts,el){els.each(function(i,o){if(this.parentNode)
this.parentNode.removeChild(this);});if(data&&data.el){data.el.style.display=data.display;data.el.style.position=data.position;if(data.parent)
data.parent.appendChild(data.el);$(el).removeData('blockUI.history');}
if(typeof opts.onUnblock=='function')
opts.onUnblock(el,opts);};function bind(b,el,opts){var full=el==window,$el=$(el);if(!b&&(full&&!pageBlock||!full&&!$el.data('blockUI.isBlocked')))
return;if(!full)
$el.data('blockUI.isBlocked',b);if(!opts.bindEvents||(b&&!opts.showOverlay))
return;var events='mousedown mouseup keydown keypress';b?$(document).bind(events,opts,handler):$(document).unbind(events,handler);};function handler(e){if(e.keyCode&&e.keyCode==9){if(pageBlock&&e.data.constrainTabKey){var els=pageBlockEls;var fwd=!e.shiftKey&&e.target===els[els.length-1];var back=e.shiftKey&&e.target===els[0];if(fwd||back){setTimeout(function(){focus(back)},10);return false;}}}
var opts=e.data;if($(e.target).parents('div.'+opts.blockMsgClass).length>0)
return true;return $(e.target).parents().children().filter('div.blockUI').length==0;};function focus(back){if(!pageBlockEls)
return;var e=pageBlockEls[back===true?pageBlockEls.length-1:0];if(e)
e.focus();};function center(el,x,y){var p=el.parentNode,s=el.style;var l=((p.offsetWidth-el.offsetWidth)/2)-sz(p,'borderLeftWidth');var t=((p.offsetHeight-el.offsetHeight)/2)-sz(p,'borderTopWidth');if(x)s.left=l>0?(l+'px'):'0';if(y)s.top=t>0?(t+'px'):'0';};function sz(el,p){return parseInt($.css(el,p))||0;};})(jQuery);

/**
 * jQuery Cookie plugin
 *
 * Copyright (c) 2010 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */
jQuery.cookie=function(key,value,options){if(arguments.length>1&&String(value)!=="[object Object]"){options=jQuery.extend({},options);if(value===null||value===undefined){options.expires=-1;}
if(typeof options.expires==='number'){var days=options.expires,t=options.expires=new Date();t.setDate(t.getDate()+days);}
value=String(value);return(document.cookie=[encodeURIComponent(key),'=',options.raw?value:encodeURIComponent(value),options.expires?'; expires='+options.expires.toUTCString():'',options.path?'; path='+options.path:'',options.domain?'; domain='+options.domain:'',options.secure?'; secure':''].join(''));}
options=value||{};var result,decode=options.raw?function(s){return s;}:decodeURIComponent;return(result=new RegExp('(?:^|; )'+encodeURIComponent(key)+'=([^;]*)').exec(document.cookie))?decode(result[1]):null;};

/*
 * Date prototype extensions. Doesn't depend on any
 * other code. Doens't overwrite existing methods.
 *
 * Adds dayNames, abbrDayNames, monthNames and abbrMonthNames static properties and isLeapYear,
 * isWeekend, isWeekDay, getDaysInMonth, getDayName, getMonthName, getDayOfYear, getWeekOfYear,
 * setDayOfYear, addYears, addMonths, addDays, addHours, addMinutes, addSeconds methods
 *
 * Copyright (c) 2006 Jšrn Zaefferer and Brandon Aaron (brandon.aaron@gmail.com || http://brandonaaron.net)
 *
 * Additional methods and properties added by Kelvin Luck: firstDayOfWeek, dateFormat, zeroTime, asString, fromString -
 * I've added my name to these methods so you know who to blame if they are broken!
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 */
Date.dayNames=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];Date.abbrDayNames=['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];Date.monthNames=['January','February','March','April','May','June','July','August','September','October','November','December'];Date.abbrMonthNames=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];Date.firstDayOfWeek=1;Date.format='dd/mm/yyyy';Date.fullYearStart='20';(function(){function add(name,method){if(!Date.prototype[name]){Date.prototype[name]=method;}};add("isLeapYear",function(){var y=this.getFullYear();return(y%4==0&&y%100!=0)||y%400==0;});add("isWeekend",function(){return this.getDay()==0||this.getDay()==6;});add("isWeekDay",function(){return!this.isWeekend();});add("getDaysInMonth",function(){return[31,(this.isLeapYear()?29:28),31,30,31,30,31,31,30,31,30,31][this.getMonth()];});add("getDayName",function(abbreviated){return abbreviated?Date.abbrDayNames[this.getDay()]:Date.dayNames[this.getDay()];});add("getMonthName",function(abbreviated){return abbreviated?Date.abbrMonthNames[this.getMonth()]:Date.monthNames[this.getMonth()];});add("getDayOfYear",function(){var tmpdtm=new Date("1/1/"+this.getFullYear());return Math.floor((this.getTime()-tmpdtm.getTime())/86400000);});add("getWeekOfYear",function(){return Math.ceil(this.getDayOfYear()/7);});add("setDayOfYear",function(day){this.setMonth(0);this.setDate(day);return this;});add("addYears",function(num){this.setFullYear(this.getFullYear()+num);return this;});add("addMonths",function(num){var tmpdtm=this.getDate();this.setMonth(this.getMonth()+num);if(tmpdtm>this.getDate())
this.addDays(-this.getDate());return this;});add("addDays",function(num){this.setTime(this.getTime()+(num*86400000));return this;});add("addHours",function(num){this.setHours(this.getHours()+num);return this;});add("addMinutes",function(num){this.setMinutes(this.getMinutes()+num);return this;});add("addSeconds",function(num){this.setSeconds(this.getSeconds()+num);return this;});add("zeroTime",function(){this.setMilliseconds(0);this.setSeconds(0);this.setMinutes(0);this.setHours(0);return this;});add("asString",function(format){var r=format||Date.format;return r.split('yyyy').join(this.getFullYear()).split('yy').join((this.getFullYear()+'').substring(2)).split('mmmm').join(this.getMonthName(false)).split('mmm').join(this.getMonthName(true)).split('mm').join(_zeroPad(this.getMonth()+1)).split('dd').join(_zeroPad(this.getDate())).split('hh').join(_zeroPad(this.getHours())).split('min').join(_zeroPad(this.getMinutes())).split('ss').join(_zeroPad(this.getSeconds()));});Date.fromString=function(s,format)
{var f=format||Date.format;var d=new Date('01/01/1977');var mLength=0;var iM=f.indexOf('mmmm');if(iM>-1){for(var i=0;i<Date.monthNames.length;i++){var mStr=s.substr(iM,Date.monthNames[i].length);if(Date.monthNames[i]==mStr){mLength=Date.monthNames[i].length-4;break;}}
d.setMonth(i);}else{iM=f.indexOf('mmm');if(iM>-1){var mStr=s.substr(iM,3);for(var i=0;i<Date.abbrMonthNames.length;i++){if(Date.abbrMonthNames[i]==mStr)break;}
d.setMonth(i);}else{d.setMonth(Number(s.substr(f.indexOf('mm'),2))-1);}}
var iY=f.indexOf('yyyy');if(iY>-1){if(iM<iY)
{iY+=mLength;}
d.setFullYear(Number(s.substr(iY,4)));}else{if(iM<iY)
{iY+=mLength;}
d.setFullYear(Number(Date.fullYearStart+s.substr(f.indexOf('yy'),2)));}
var iD=f.indexOf('dd');if(iM<iD)
{iD+=mLength;}
d.setDate(Number(s.substr(iD,2)));if(isNaN(d.getTime())){return false;}
return d;};var _zeroPad=function(num){var s='0'+num;return s.substring(s.length-2)};})();

/*
 * 	Easy Tooltip 1.0 - jQuery plugin
 *	written by Alen Grakalic	
 *	http://cssglobe.com/post/4380/easy-tooltip--jquery-plugin
 *
 *	Copyright (c) 2009 Alen Grakalic (http://cssglobe.com)
 *	Dual licensed under the MIT (MIT-LICENSE.txt)
 *	and GPL (GPL-LICENSE.txt) licenses.
 *
 */
(function($){$.fn.easyTooltip=function(options){var defaults={xOffset:10,yOffset:25,tooltipId:"easyTooltip",clickRemove:false,content:"",useElement:""};var options=$.extend(defaults,options);var content;this.each(function(){var title=$(this).attr("tip");$(this).hover(function(e){content=(options.content!="")?options.content:title;content=(options.useElement!="")?$("#"+options.useElement).html():content;$(this).attr("title","");if(content!=""&&content!=undefined){$("body").append("<div id='"+options.tooltipId+"'>"+content+"</div>");$("#"+options.tooltipId).css("position","absolute").css("top",(e.pageY-options.yOffset)+"px").css("left",(e.pageX+options.xOffset)+"px").css("display","none").fadeIn("fast")}},function(){$("#"+options.tooltipId).remove();$(this).attr("title",title)});$(this).mousemove(function(e){$("#"+options.tooltipId).css("top",(e.pageY-options.yOffset)+"px").css("left",(e.pageX+options.xOffset)+"px")});if(options.clickRemove){$(this).mousedown(function(e){$("#"+options.tooltipId).remove();$(this).attr("title",title)})}})}})(jQuery);

jQuery(function(){
    jQuery(".tips").easyTooltip();
});


/**
 * Spoofs placeholders in browsers that don't support them (eg Firefox 3)
 * 
 * Copyright 2011 Dan Bentley
 * Licensed under the Apache License 2.0
 *
 * Author: Dan Bentley [github.com/danbentley]
 */
(function($){if("placeholder"in document.createElement("input"))return;$(document).ready(function(){$(':input[placeholder]').each(function(){setupPlaceholder($(this));});$('form').submit(function(e){clearPlaceholdersBeforeSubmit($(this));});});function setupPlaceholder(input){var placeholderText=input.attr('placeholder');if(input.val()==='')input.val(placeholderText);input.bind({focus:function(e){if(input.val()===placeholderText)input.val('');},blur:function(e){if(input.val()==='')input.val(placeholderText);}});}
function clearPlaceholdersBeforeSubmit(form){form.find(':input[placeholder]').each(function(){var el=$(this);if(el.val()===el.attr('placeholder'))el.val('');});}})(jQuery);

if( jQuery.isFunction(jQuery.fn.sortable) ){
/* Modifided script from the simple-page-ordering plugin */
jQuery(document).ready(function($){$('table.widefat.wp-list-table tbody th, table.widefat tbody td').css('cursor','move');$("table.widefat.wp-list-table tbody").sortable({items:'tr:not(.inline-edit-row)',cursor:'move',axis:'y',containment:'table.widefat',placeholder:'product-cat-placeholder',scrollSensitivity:40,helper:function(e,ui){ui.children().each(function(){jQuery(this).width(jQuery(this).width());});return ui;},start:function(event,ui){if(!ui.item.hasClass('alternate'))ui.item.css('background-color','#ffffff');ui.item.children('td,th').css('border-bottom-width','0');ui.item.css('outline','1px solid #aaa');},stop:function(event,ui){ui.item.removeAttr('style');ui.item.children('td,th').css('border-bottom-width','1px');},update:function(event,ui){var termid=ui.item.find('.check-column input').val();var termparent=ui.item.find('.parent').html();var prevtermid=ui.item.prev().find('.check-column input').val();var nexttermid=ui.item.next().find('.check-column input').val();var prevtermparent=undefined;if(prevtermid!=undefined){var prevtermparent=ui.item.prev().find('.parent').html();if(prevtermparent!=termparent)prevtermid=undefined;}
var nexttermparent=undefined;if(nexttermid!=undefined){nexttermparent=ui.item.next().find('.parent').html();if(nexttermparent!=termparent)nexttermid=undefined;}
if((prevtermid==undefined&&nexttermid==undefined)||(nexttermid==undefined&&nexttermparent==prevtermid)||(nexttermid!=undefined&&prevtermparent==termid)){$("table.widefat tbody").sortable('cancel');return;}
ui.item.find('.check-column input').hide().after('<img alt="processing" src="images/wpspin_light.gif" class="waiting" style="margin-left: 6px;" />');$.post(ajaxurl,{action:'woocommerce-categories-ordering',id:termid,nextid:nexttermid},function(response){if(response=='children')window.location.reload();else{ui.item.find('.check-column input').show().siblings('img').remove();}});$('table.widefat tbody tr').each(function(){var i=jQuery('table.widefat tbody tr').index(this);if(i%2==0)jQuery(this).addClass('alternate');else jQuery(this).removeClass('alternate');});}});});
}

/**
 * Copyright (c) 2008 Kelvin Luck (http://www.kelvinluck.com/)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) 
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 * .
 * $Id: jquery.datePicker.js 102 2010-09-13 14:00:54Z kelvin.luck $
 **/
(function($){$.fn.extend({renderCalendar:function(s)
{var dc=function(a)
{return document.createElement(a);};s=$.extend({},$.fn.datePicker.defaults,s);if(s.showHeader!=$.dpConst.SHOW_HEADER_NONE){var headRow=$(dc('tr'));for(var i=Date.firstDayOfWeek;i<Date.firstDayOfWeek+7;i++){var weekday=i%7;var day=Date.dayNames[weekday];headRow.append(jQuery(dc('th')).attr({'scope':'col','abbr':day,'title':day,'class':(weekday==0||weekday==6?'weekend':'weekday')}).html(s.showHeader==$.dpConst.SHOW_HEADER_SHORT?day.substr(0,1):day));}};var calendarTable=$(dc('table')).attr({'cellspacing':2}).addClass('jCalendar').append((s.showHeader!=$.dpConst.SHOW_HEADER_NONE?$(dc('thead')).append(headRow):dc('thead')));var tbody=$(dc('tbody'));var today=(new Date()).zeroTime();today.setHours(12);var month=s.month==undefined?today.getMonth():s.month;var year=s.year||today.getFullYear();var currentDate=(new Date(year,month,1,12,0,0));var firstDayOffset=Date.firstDayOfWeek-currentDate.getDay()+1;if(firstDayOffset>1)firstDayOffset-=7;var weeksToDraw=Math.ceil(((-1*firstDayOffset+1)+currentDate.getDaysInMonth())/7);currentDate.addDays(firstDayOffset-1);var doHover=function(firstDayInBounds)
{return function()
{if(s.hoverClass){var $this=$(this);if(!s.selectWeek){$this.addClass(s.hoverClass);}else if(firstDayInBounds&&!$this.is('.disabled')){$this.parent().addClass('activeWeekHover');}}}};var unHover=function()
{if(s.hoverClass){var $this=$(this);$this.removeClass(s.hoverClass);$this.parent().removeClass('activeWeekHover');}};var w=0;while(w++<weeksToDraw){var r=jQuery(dc('tr'));var firstDayInBounds=s.dpController?currentDate>s.dpController.startDate:false;for(var i=0;i<7;i++){var thisMonth=currentDate.getMonth()==month;var d=$(dc('td')).text(currentDate.getDate()+'').addClass((thisMonth?'current-month ':'other-month ')+
(currentDate.isWeekend()?'weekend ':'weekday ')+
(thisMonth&&currentDate.getTime()==today.getTime()?'today ':'')).data('datePickerDate',currentDate.asString()).hover(doHover(firstDayInBounds),unHover);r.append(d);if(s.renderCallback){s.renderCallback(d,currentDate,month,year);}
currentDate=new Date(currentDate.getFullYear(),currentDate.getMonth(),currentDate.getDate()+1,12,0,0);}
tbody.append(r);}
calendarTable.append(tbody);return this.each(function()
{$(this).empty().append(calendarTable);});},datePicker:function(s)
{if(!$.event._dpCache)$.event._dpCache=[];s=$.extend({},$.fn.datePicker.defaults,s);return this.each(function()
{var $this=$(this);var alreadyExists=true;if(!this._dpId){this._dpId=$.event.guid++;$.event._dpCache[this._dpId]=new DatePicker(this);alreadyExists=false;}
if(s.inline){s.createButton=false;s.displayClose=false;s.closeOnSelect=false;$this.empty();}
var controller=$.event._dpCache[this._dpId];controller.init(s);if(!alreadyExists&&s.createButton){controller.button=$('<a href="#" class="dp-choose-date" title="'+$.dpText.TEXT_CHOOSE_DATE+'">'+$.dpText.TEXT_CHOOSE_DATE+'</a>').bind('click',function()
{$this.dpDisplay(this);this.blur();return false;});$this.after(controller.button);}
if(!alreadyExists&&$this.is(':text')){$this.bind('dateSelected',function(e,selectedDate,$td)
{this.value=selectedDate.asString();}).bind('change',function()
{if(this.value==''){controller.clearSelected();}else{var d=Date.fromString(this.value);if(d){controller.setSelected(d,true,true);}}});if(s.clickInput){$this.bind('click',function()
{$this.trigger('change');$this.dpDisplay();});}
var d=Date.fromString(this.value);if(this.value!=''&&d){controller.setSelected(d,true,true);}}
$this.addClass('dp-applied');})},dpSetDisabled:function(s)
{return _w.call(this,'setDisabled',s);},dpSetStartDate:function(d)
{return _w.call(this,'setStartDate',d);},dpSetEndDate:function(d)
{return _w.call(this,'setEndDate',d);},dpGetSelected:function()
{var c=_getController(this[0]);if(c){return c.getSelected();}
return null;},dpSetSelected:function(d,v,m,e)
{if(v==undefined)v=true;if(m==undefined)m=true;if(e==undefined)e=true;return _w.call(this,'setSelected',Date.fromString(d),v,m,e);},dpSetDisplayedMonth:function(m,y)
{return _w.call(this,'setDisplayedMonth',Number(m),Number(y),true);},dpDisplay:function(e)
{return _w.call(this,'display',e);},dpSetRenderCallback:function(a)
{return _w.call(this,'setRenderCallback',a);},dpSetPosition:function(v,h)
{return _w.call(this,'setPosition',v,h);},dpSetOffset:function(v,h)
{return _w.call(this,'setOffset',v,h);},dpClose:function()
{return _w.call(this,'_closeCalendar',false,this[0]);},dpRerenderCalendar:function()
{return _w.call(this,'_rerenderCalendar');},_dpDestroy:function()
{}});var _w=function(f,a1,a2,a3,a4)
{return this.each(function()
{var c=_getController(this);if(c){c[f](a1,a2,a3,a4);}});};function DatePicker(ele)
{this.ele=ele;this.displayedMonth=null;this.displayedYear=null;this.startDate=null;this.endDate=null;this.showYearNavigation=null;this.closeOnSelect=null;this.displayClose=null;this.rememberViewedMonth=null;this.selectMultiple=null;this.numSelectable=null;this.numSelected=null;this.verticalPosition=null;this.horizontalPosition=null;this.verticalOffset=null;this.horizontalOffset=null;this.button=null;this.renderCallback=[];this.selectedDates={};this.inline=null;this.context='#dp-popup';this.settings={};};$.extend(DatePicker.prototype,{init:function(s)
{this.setStartDate(s.startDate);this.setEndDate(s.endDate);this.setDisplayedMonth(Number(s.month),Number(s.year));this.setRenderCallback(s.renderCallback);this.showYearNavigation=s.showYearNavigation;this.closeOnSelect=s.closeOnSelect;this.displayClose=s.displayClose;this.rememberViewedMonth=s.rememberViewedMonth;this.selectMultiple=s.selectMultiple;this.numSelectable=s.selectMultiple?s.numSelectable:1;this.numSelected=0;this.verticalPosition=s.verticalPosition;this.horizontalPosition=s.horizontalPosition;this.hoverClass=s.hoverClass;this.setOffset(s.verticalOffset,s.horizontalOffset);this.inline=s.inline;this.settings=s;if(this.inline){this.context=this.ele;this.display();}},setStartDate:function(d)
{if(d){this.startDate=Date.fromString(d);}
if(!this.startDate){this.startDate=(new Date()).zeroTime();}
this.setDisplayedMonth(this.displayedMonth,this.displayedYear);},setEndDate:function(d)
{if(d){this.endDate=Date.fromString(d);}
if(!this.endDate){this.endDate=(new Date('12/31/2999'));}
if(this.endDate.getTime()<this.startDate.getTime()){this.endDate=this.startDate;}
this.setDisplayedMonth(this.displayedMonth,this.displayedYear);},setPosition:function(v,h)
{this.verticalPosition=v;this.horizontalPosition=h;},setOffset:function(v,h)
{this.verticalOffset=parseInt(v)||0;this.horizontalOffset=parseInt(h)||0;},setDisabled:function(s)
{$e=$(this.ele);$e[s?'addClass':'removeClass']('dp-disabled');if(this.button){$but=$(this.button);$but[s?'addClass':'removeClass']('dp-disabled');$but.attr('title',s?'':$.dpText.TEXT_CHOOSE_DATE);}
if($e.is(':text')){$e.attr('disabled',s?'disabled':'');}},setDisplayedMonth:function(m,y,rerender)
{if(this.startDate==undefined||this.endDate==undefined){return;}
var s=new Date(this.startDate.getTime());s.setDate(1);var e=new Date(this.endDate.getTime());e.setDate(1);var t;if((!m&&!y)||(isNaN(m)&&isNaN(y))){t=new Date().zeroTime();t.setDate(1);}else if(isNaN(m)){t=new Date(y,this.displayedMonth,1);}else if(isNaN(y)){t=new Date(this.displayedYear,m,1);}else{t=new Date(y,m,1)}
if(t.getTime()<s.getTime()){t=s;}else if(t.getTime()>e.getTime()){t=e;}
var oldMonth=this.displayedMonth;var oldYear=this.displayedYear;this.displayedMonth=t.getMonth();this.displayedYear=t.getFullYear();if(rerender&&(this.displayedMonth!=oldMonth||this.displayedYear!=oldYear))
{this._rerenderCalendar();$(this.ele).trigger('dpMonthChanged',[this.displayedMonth,this.displayedYear]);}},setSelected:function(d,v,moveToMonth,dispatchEvents)
{if(d<this.startDate||d.zeroTime()>this.endDate.zeroTime()){return;}
var s=this.settings;if(s.selectWeek)
{d=d.addDays(-(d.getDay()-Date.firstDayOfWeek+7)%7);if(d<this.startDate)
{return;}}
if(v==this.isSelected(d))
{return;}
if(this.selectMultiple==false){this.clearSelected();}else if(v&&this.numSelected==this.numSelectable){return;}
if(moveToMonth&&(this.displayedMonth!=d.getMonth()||this.displayedYear!=d.getFullYear())){this.setDisplayedMonth(d.getMonth(),d.getFullYear(),true);}
this.selectedDates[d.asString()]=v;this.numSelected+=v?1:-1;var selectorString='td.'+(d.getMonth()==this.displayedMonth?'current-month':'other-month');var $td;$(selectorString,this.context).each(function()
{if($(this).data('datePickerDate')==d.asString()){$td=$(this);if(s.selectWeek)
{$td.parent()[v?'addClass':'removeClass']('selectedWeek');}
$td[v?'addClass':'removeClass']('selected');}});$('td',this.context).not('.selected')[this.selectMultiple&&this.numSelected==this.numSelectable?'addClass':'removeClass']('unselectable');if(dispatchEvents)
{var s=this.isSelected(d);$e=$(this.ele);var dClone=Date.fromString(d.asString());$e.trigger('dateSelected',[dClone,$td,s]);$e.trigger('change');}},isSelected:function(d)
{return this.selectedDates[d.asString()];},getSelected:function()
{var r=[];for(var s in this.selectedDates){if(this.selectedDates[s]==true){r.push(Date.fromString(s));}}
return r;},clearSelected:function()
{this.selectedDates={};this.numSelected=0;$('td.selected',this.context).removeClass('selected').parent().removeClass('selectedWeek');},display:function(eleAlignTo)
{if($(this.ele).is('.dp-disabled'))return;eleAlignTo=eleAlignTo||this.ele;var c=this;var $ele=$(eleAlignTo);var eleOffset=$ele.offset();var $createIn;var attrs;var attrsCalendarHolder;var cssRules;if(c.inline){$createIn=$(this.ele);attrs={'id':'calendar-'+this.ele._dpId,'class':'dp-popup dp-popup-inline'};$('.dp-popup',$createIn).remove();cssRules={};}else{$createIn=$('body');attrs={'id':'dp-popup','class':'dp-popup'};cssRules={'top':eleOffset.top+c.verticalOffset,'left':eleOffset.left+c.horizontalOffset};var _checkMouse=function(e)
{var el=e.target;var cal=$('#dp-popup')[0];while(true){if(el==cal){return true;}else if(el==document){c._closeCalendar();return false;}else{el=$(el).parent()[0];}}};this._checkMouse=_checkMouse;c._closeCalendar(true);$(document).bind('keydown.datepicker',function(event)
{if(event.keyCode==27){c._closeCalendar();}});}
if(!c.rememberViewedMonth)
{var selectedDate=this.getSelected()[0];if(selectedDate){selectedDate=new Date(selectedDate);this.setDisplayedMonth(selectedDate.getMonth(),selectedDate.getFullYear(),false);}}
$createIn.append($('<div></div>').attr(attrs).css(cssRules).append($('<h2></h2>'),$('<div class="dp-nav-prev"></div>').append($('<a class="dp-nav-prev-year" href="#" title="'+$.dpText.TEXT_PREV_YEAR+'">&lt;&lt;</a>').bind('click',function()
{return c._displayNewMonth.call(c,this,0,-1);}),$('<a class="dp-nav-prev-month" href="#" title="'+$.dpText.TEXT_PREV_MONTH+'">&lt;</a>').bind('click',function()
{return c._displayNewMonth.call(c,this,-1,0);})),$('<div class="dp-nav-next"></div>').append($('<a class="dp-nav-next-year" href="#" title="'+$.dpText.TEXT_NEXT_YEAR+'">&gt;&gt;</a>').bind('click',function()
{return c._displayNewMonth.call(c,this,0,1);}),$('<a class="dp-nav-next-month" href="#" title="'+$.dpText.TEXT_NEXT_MONTH+'">&gt;</a>').bind('click',function()
{return c._displayNewMonth.call(c,this,1,0);})),$('<div class="dp-calendar"></div>')).bgIframe());var $pop=this.inline?$('.dp-popup',this.context):$('#dp-popup');if(this.showYearNavigation==false){$('.dp-nav-prev-year, .dp-nav-next-year',c.context).css('display','none');}
if(this.displayClose){$pop.append($('<a href="#" id="dp-close">'+$.dpText.TEXT_CLOSE+'</a>').bind('click',function()
{c._closeCalendar();return false;}));}
c._renderCalendar();$(this.ele).trigger('dpDisplayed',$pop);if(!c.inline){if(this.verticalPosition==$.dpConst.POS_BOTTOM){$pop.css('top',eleOffset.top+$ele.height()-$pop.height()+c.verticalOffset);}
if(this.horizontalPosition==$.dpConst.POS_RIGHT){$pop.css('left',eleOffset.left+$ele.width()-$pop.width()+c.horizontalOffset);}
$(document).bind('mousedown.datepicker',this._checkMouse);}},setRenderCallback:function(a)
{if(a==null)return;if(a&&typeof(a)=='function'){a=[a];}
this.renderCallback=this.renderCallback.concat(a);},cellRender:function($td,thisDate,month,year){var c=this.dpController;var d=new Date(thisDate.getTime());$td.bind('click',function()
{var $this=$(this);if(!$this.is('.disabled')){c.setSelected(d,!$this.is('.selected')||!c.selectMultiple,false,true);if(c.closeOnSelect){if(c.settings.autoFocusNextInput){var ele=c.ele;var found=false;$(':input',ele.form).each(function()
{if(found){$(this).focus();return false;}
if(this==ele){found=true;}});}else{c.ele.focus();}
c._closeCalendar();}}});if(c.isSelected(d)){$td.addClass('selected');if(c.settings.selectWeek)
{$td.parent().addClass('selectedWeek');}}else if(c.selectMultiple&&c.numSelected==c.numSelectable){$td.addClass('unselectable');}},_applyRenderCallbacks:function()
{var c=this;$('td',this.context).each(function()
{for(var i=0;i<c.renderCallback.length;i++){$td=$(this);c.renderCallback[i].apply(this,[$td,Date.fromString($td.data('datePickerDate')),c.displayedMonth,c.displayedYear]);}});return;},_displayNewMonth:function(ele,m,y)
{if(!$(ele).is('.disabled')){this.setDisplayedMonth(this.displayedMonth+m,this.displayedYear+y,true);}
ele.blur();return false;},_rerenderCalendar:function()
{this._clearCalendar();this._renderCalendar();},_renderCalendar:function()
{$('h2',this.context).html((new Date(this.displayedYear,this.displayedMonth,1)).asString($.dpText.HEADER_FORMAT));$('.dp-calendar',this.context).renderCalendar($.extend({},this.settings,{month:this.displayedMonth,year:this.displayedYear,renderCallback:this.cellRender,dpController:this,hoverClass:this.hoverClass}));if(this.displayedYear==this.startDate.getFullYear()&&this.displayedMonth==this.startDate.getMonth()){$('.dp-nav-prev-year',this.context).addClass('disabled');$('.dp-nav-prev-month',this.context).addClass('disabled');$('.dp-calendar td.other-month',this.context).each(function()
{var $this=$(this);if(Number($this.text())>20){$this.addClass('disabled');}});var d=this.startDate.getDate();$('.dp-calendar td.current-month',this.context).each(function()
{var $this=$(this);if(Number($this.text())<d){$this.addClass('disabled');}});}else{$('.dp-nav-prev-year',this.context).removeClass('disabled');$('.dp-nav-prev-month',this.context).removeClass('disabled');var d=this.startDate.getDate();if(d>20){var st=this.startDate.getTime();var sd=new Date(st);sd.addMonths(1);if(this.displayedYear==sd.getFullYear()&&this.displayedMonth==sd.getMonth()){$('.dp-calendar td.other-month',this.context).each(function()
{var $this=$(this);if(Date.fromString($this.data('datePickerDate')).getTime()<st){$this.addClass('disabled');}});}}}
if(this.displayedYear==this.endDate.getFullYear()&&this.displayedMonth==this.endDate.getMonth()){$('.dp-nav-next-year',this.context).addClass('disabled');$('.dp-nav-next-month',this.context).addClass('disabled');$('.dp-calendar td.other-month',this.context).each(function()
{var $this=$(this);if(Number($this.text())<14){$this.addClass('disabled');}});var d=this.endDate.getDate();$('.dp-calendar td.current-month',this.context).each(function()
{var $this=$(this);if(Number($this.text())>d){$this.addClass('disabled');}});}else{$('.dp-nav-next-year',this.context).removeClass('disabled');$('.dp-nav-next-month',this.context).removeClass('disabled');var d=this.endDate.getDate();if(d<13){var ed=new Date(this.endDate.getTime());ed.addMonths(-1);if(this.displayedYear==ed.getFullYear()&&this.displayedMonth==ed.getMonth()){$('.dp-calendar td.other-month',this.context).each(function()
{var $this=$(this);var cellDay=Number($this.text());if(cellDay<13&&cellDay>d){$this.addClass('disabled');}});}}}
this._applyRenderCallbacks();},_closeCalendar:function(programatic,ele)
{if(!ele||ele==this.ele)
{$(document).unbind('mousedown.datepicker');$(document).unbind('keydown.datepicker');this._clearCalendar();$('#dp-popup a').unbind();$('#dp-popup').empty().remove();if(!programatic){$(this.ele).trigger('dpClosed',[this.getSelected()]);}}},_clearCalendar:function()
{$('.dp-calendar td',this.context).unbind();$('.dp-calendar',this.context).empty();}});$.dpConst={SHOW_HEADER_NONE:0,SHOW_HEADER_SHORT:1,SHOW_HEADER_LONG:2,POS_TOP:0,POS_BOTTOM:1,POS_LEFT:0,POS_RIGHT:1,DP_INTERNAL_FOCUS:'dpInternalFocusTrigger'};$.dpText={TEXT_PREV_YEAR:'Previous year',TEXT_PREV_MONTH:'Previous month',TEXT_NEXT_YEAR:'Next year',TEXT_NEXT_MONTH:'Next month',TEXT_CLOSE:'Close',TEXT_CHOOSE_DATE:'Choose date',HEADER_FORMAT:'mmmm yyyy'};$.dpVersion='$Id: jquery.datePicker.js 102 2010-09-13 14:00:54Z kelvin.luck $';$.fn.datePicker.defaults={month:undefined,year:undefined,showHeader:$.dpConst.SHOW_HEADER_SHORT,startDate:undefined,endDate:undefined,inline:false,renderCallback:null,createButton:true,showYearNavigation:true,closeOnSelect:true,displayClose:false,selectMultiple:false,numSelectable:Number.MAX_VALUE,clickInput:false,rememberViewedMonth:true,selectWeek:false,verticalPosition:$.dpConst.POS_TOP,horizontalPosition:$.dpConst.POS_LEFT,verticalOffset:0,horizontalOffset:0,hoverClass:'dp-hover',autoFocusNextInput:false};function _getController(ele)
{if(ele._dpId)return $.event._dpCache[ele._dpId];return false;};if($.fn.bgIframe==undefined){$.fn.bgIframe=function(){return this;};};$(window).bind('unload',function(){var els=$.event._dpCache||[];for(var i in els){$(els[i].ele)._dpDestroy();}});})(jQuery);