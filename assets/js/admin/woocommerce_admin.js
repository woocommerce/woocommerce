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