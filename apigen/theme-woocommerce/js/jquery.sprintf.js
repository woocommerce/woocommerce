/*!
 * sprintf and vsprintf for jQuery
 * somewhat based on http://jan.moesen.nu/code/javascript/sprintf-and-printf-in-javascript/
 * Copyright (c) 2008 Sabin Iacob (m0n5t3r) <iacobs@m0n5t3r.info>
 * @license http://www.gnu.org/licenses/gpl.html
 * @project jquery.sprintf
 */
(function(d){var a={b:function(e){return parseInt(e,10).toString(2)},c:function(e){return String.fromCharCode(parseInt(e,10))},d:function(e){return parseInt(e,10)},u:function(e){return Math.abs(e)},f:function(f,e){e=parseInt(e,10);f=parseFloat(f);if(isNaN(e&&f)){return NaN}return e&&f.toFixed(e)||f},o:function(e){return parseInt(e,10).toString(8)},s:function(e){return e},x:function(e){return(""+parseInt(e,10).toString(16)).toLowerCase()},X:function(e){return(""+parseInt(e,10).toString(16)).toUpperCase()}};var c=/%(?:(\d+)?(?:\.(\d+))?|\(([^)]+)\))([%bcdufosxX])/g;var b=function(f){if(f.length==1&&typeof f[0]=="object"){f=f[0];return function(i,h,k,j,g,m,l){return a[g](f[j])}}else{var e=0;return function(i,h,k,j,g,m,l){if(g=="%"){return"%"}return a[g](f[e++],k)}}};d.extend({sprintf:function(f){var e=Array.apply(null,arguments).slice(1);return f.replace(c,b(e))},vsprintf:function(f,e){return f.replace(c,b(e))}})})(jQuery);
