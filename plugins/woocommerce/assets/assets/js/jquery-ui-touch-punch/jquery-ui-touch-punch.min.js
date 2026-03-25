/*!
 * jQuery UI Touch Punch 0.2.3
 *
 * Copyright 2011–2014, Dave Furfero
 * Dual licensed under the MIT or GPL Version 2 licenses.
 *
 * Depends:
 *  jquery.ui.widget.js
 *  jquery.ui.mouse.js
 */
!function(t){if(t.support.touch="ontouchend"in document,t.support.touch){var o,e=t.ui.mouse.prototype,u=e._mouseInit,n=e._mouseDestroy;e._touchStart=function(t){!o&&this._mouseCapture(t.originalEvent.changedTouches[0])&&(o=!0,this._touchMoved=!1,c(t,"mouseover"),c(t,"mousemove"),c(t,"mousedown"))},e._touchMove=function(t){o&&(this._touchMoved=!0,c(t,"mousemove"))},e._touchEnd=function(t){o&&(c(t,"mouseup"),c(t,"mouseout"),this._touchMoved||c(t,"click"),o=!1)},e._mouseInit=function(){this.element.on({touchstart:t.proxy(this,"_touchStart"),touchmove:t.proxy(this,"_touchMove"),touchend:t.proxy(this,"_touchEnd")}),u.call(this)},e._mouseDestroy=function(){this.element.off({touchstart:t.proxy(this,"_touchStart"),touchmove:t.proxy(this,"_touchMove"),touchend:t.proxy(this,"_touchEnd")}),n.call(this)}}function c(t,o){if(!(t.originalEvent.touches.length>1)){t.preventDefault();var e=t.originalEvent.changedTouches[0],u=document.createEvent("MouseEvents");u.initMouseEvent(o,!0,!0,window,1,e.screenX,e.screenY,e.clientX,e.clientY,!1,!1,!1,!1,0,null),t.target.dispatchEvent(u)}}}(jQuery);