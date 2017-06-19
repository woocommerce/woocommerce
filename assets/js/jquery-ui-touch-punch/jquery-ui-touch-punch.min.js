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
!function(o){function t(o,t){if(!(o.originalEvent.touches.length>1)){o.preventDefault();var e=o.originalEvent.changedTouches[0],u=document.createEvent("MouseEvents");u.initMouseEvent(t,!0,!0,window,1,e.screenX,e.screenY,e.clientX,e.clientY,!1,!1,!1,!1,0,null),o.target.dispatchEvent(u)}}if(o.support.touch="ontouchend"in document,o.support.touch){var e,u=o.ui.mouse.prototype,n=u._mouseInit,c=u._mouseDestroy;u._touchStart=function(o){var u=this;!e&&u._mouseCapture(o.originalEvent.changedTouches[0])&&(e=!0,u._touchMoved=!1,t(o,"mouseover"),t(o,"mousemove"),t(o,"mousedown"))},u._touchMove=function(o){e&&(this._touchMoved=!0,t(o,"mousemove"))},u._touchEnd=function(o){e&&(t(o,"mouseup"),t(o,"mouseout"),this._touchMoved||t(o,"click"),e=!1)},u._mouseInit=function(){var t=this;t.element.bind({touchstart:o.proxy(t,"_touchStart"),touchmove:o.proxy(t,"_touchMove"),touchend:o.proxy(t,"_touchEnd")}),n.call(t)},u._mouseDestroy=function(){var t=this;t.element.unbind({touchstart:o.proxy(t,"_touchStart"),touchmove:o.proxy(t,"_touchMove"),touchend:o.proxy(t,"_touchEnd")}),c.call(t)}}}(jQuery);