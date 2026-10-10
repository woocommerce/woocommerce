(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4921],{

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/hook.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ useText)
});

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/styles.js
var text_styles_namespaceObject = {};
__webpack_require__.r(text_styles_namespaceObject);
__webpack_require__.d(text_styles_namespaceObject, {
  Text: () => (Text),
  block: () => (block),
  destructive: () => (destructive),
  highlighterText: () => (highlighterText),
  muted: () => (muted),
  positive: () => (positive),
  upperCase: () => (upperCase)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js
var emotion_react_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/truncate/styles.js
function _EMOTION_STRINGIFIED_CSS_ERROR__() {
  return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop).";
}

const Truncate =  true ? {
  name: "hdknak",
  styles: "display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
} : 0;

//# sourceMappingURL=styles.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/values.js
var values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/values.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/truncate/utils.js

const TRUNCATE_ELLIPSIS = "\u2026";
const TRUNCATE_TYPE = {
  auto: "auto",
  head: "head",
  middle: "middle",
  tail: "tail",
  none: "none"
};
const TRUNCATE_DEFAULT_PROPS = {
  ellipsis: TRUNCATE_ELLIPSIS,
  ellipsizeMode: TRUNCATE_TYPE.auto,
  limit: 0,
  numberOfLines: 0
};
function truncateMiddle(word, headLength, tailLength, ellipsis) {
  if (typeof word !== "string") {
    return "";
  }
  const wordLength = word.length;
  const frontLength = ~~headLength;
  const backLength = ~~tailLength;
  const truncateStr = (0,values/* isValueDefined */.J5)(ellipsis) ? ellipsis : TRUNCATE_ELLIPSIS;
  if (frontLength === 0 && backLength === 0 || frontLength >= wordLength || backLength >= wordLength || frontLength + backLength >= wordLength) {
    return word;
  } else if (backLength === 0) {
    return word.slice(0, frontLength) + truncateStr;
  }
  return word.slice(0, frontLength) + truncateStr + word.slice(wordLength - backLength);
}
function truncateContent(words = "", props) {
  const mergedProps = {
    ...TRUNCATE_DEFAULT_PROPS,
    ...props
  };
  const {
    ellipsis,
    ellipsizeMode,
    limit
  } = mergedProps;
  if (ellipsizeMode === TRUNCATE_TYPE.none) {
    return words;
  }
  let truncateHead;
  let truncateTail;
  switch (ellipsizeMode) {
    case TRUNCATE_TYPE.head:
      truncateHead = 0;
      truncateTail = limit;
      break;
    case TRUNCATE_TYPE.middle:
      truncateHead = Math.floor(limit / 2);
      truncateTail = Math.floor(limit / 2);
      break;
    default:
      truncateHead = limit;
      truncateTail = 0;
  }
  const truncatedContent = ellipsizeMode !== TRUNCATE_TYPE.auto ? truncateMiddle(words, truncateHead, truncateTail, ellipsis) : words;
  return truncatedContent;
}

//# sourceMappingURL=utils.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js + 2 modules
var use_cx = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/truncate/hook.js






function useTruncate(props) {
  const {
    className,
    children,
    ellipsis = TRUNCATE_ELLIPSIS,
    ellipsizeMode = TRUNCATE_TYPE.auto,
    limit = 0,
    numberOfLines = 0,
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, "Truncate");
  const cx = (0,use_cx/* useCx */.l)();
  let childrenAsText;
  if (typeof children === "string") {
    childrenAsText = children;
  } else if (typeof children === "number") {
    childrenAsText = children.toString();
  }
  const truncatedContent = childrenAsText ? truncateContent(childrenAsText, {
    ellipsis,
    ellipsizeMode,
    limit,
    numberOfLines
  }) : children;
  const shouldTruncate = !!childrenAsText && ellipsizeMode === TRUNCATE_TYPE.auto;
  const classes = (0,react.useMemo)(() => {
    const truncateLines = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)(numberOfLines === 1 ? "word-break: break-all;" : "", " -webkit-box-orient:vertical;-webkit-line-clamp:", numberOfLines, ";display:-webkit-box;overflow:hidden;" + ( true ? "" : 0),  true ? "" : 0);
    return cx(shouldTruncate && !numberOfLines && Truncate, shouldTruncate && !!numberOfLines && truncateLines, className);
  }, [className, cx, numberOfLines, shouldTruncate]);
  return {
    ...otherProps,
    className: classes,
    children: truncatedContent
  };
}

//# sourceMappingURL=hook.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/memize@2.1.1/node_modules/memize/dist/index.js
var dist = __webpack_require__("../../node_modules/.pnpm/memize@2.1.1/node_modules/memize/dist/index.js");
;// ../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/index.mjs
var r={grad:.9,turn:360,rad:360/(2*Math.PI)},t=function(r){return"string"==typeof r?r.length>0:"number"==typeof r},n=function(r,t,n){return void 0===t&&(t=0),void 0===n&&(n=Math.pow(10,t)),Math.round(n*r)/n+0},e=function(r,t,n){return void 0===t&&(t=0),void 0===n&&(n=1),r>n?n:r>t?r:t},u=function(r){return(r=isFinite(r)?r%360:0)>0?r:r+360},a=function(r){return{r:e(r.r,0,255),g:e(r.g,0,255),b:e(r.b,0,255),a:e(r.a)}},o=function(r){return{r:n(r.r),g:n(r.g),b:n(r.b),a:n(r.a,3)}},i=/^#([0-9a-f]{3,8})$/i,s=function(r){var t=r.toString(16);return t.length<2?"0"+t:t},h=function(r){var t=r.r,n=r.g,e=r.b,u=r.a,a=Math.max(t,n,e),o=a-Math.min(t,n,e),i=o?a===t?(n-e)/o:a===n?2+(e-t)/o:4+(t-n)/o:0;return{h:60*(i<0?i+6:i),s:a?o/a*100:0,v:a/255*100,a:u}},b=function(r){var t=r.h,n=r.s,e=r.v,u=r.a;t=t/360*6,n/=100,e/=100;var a=Math.floor(t),o=e*(1-n),i=e*(1-(t-a)*n),s=e*(1-(1-t+a)*n),h=a%6;return{r:255*[e,i,o,o,s,e][h],g:255*[s,e,e,i,o,o][h],b:255*[o,o,s,e,e,i][h],a:u}},g=function(r){return{h:u(r.h),s:e(r.s,0,100),l:e(r.l,0,100),a:e(r.a)}},d=function(r){return{h:n(r.h),s:n(r.s),l:n(r.l),a:n(r.a,3)}},f=function(r){return b((n=(t=r).s,{h:t.h,s:(n*=((e=t.l)<50?e:100-e)/100)>0?2*n/(e+n)*100:0,v:e+n,a:t.a}));var t,n,e},c=function(r){return{h:(t=h(r)).h,s:(u=(200-(n=t.s))*(e=t.v)/100)>0&&u<200?n*e/100/(u<=100?u:200-u)*100:0,l:u/2,a:t.a};var t,n,e,u},l=/^hsla?\(\s*([+-]?\d*\.?\d+)(deg|rad|grad|turn)?\s*,\s*([+-]?\d*\.?\d+)%\s*,\s*([+-]?\d*\.?\d+)%\s*(?:,\s*([+-]?\d*\.?\d+)(%)?\s*)?\)$/i,p=/^hsla?\(\s*([+-]?\d*\.?\d+)(deg|rad|grad|turn)?\s+([+-]?\d*\.?\d+)%\s+([+-]?\d*\.?\d+)%\s*(?:\/\s*([+-]?\d*\.?\d+)(%)?\s*)?\)$/i,v=/^rgba?\(\s*([+-]?\d*\.?\d+)(%)?\s*,\s*([+-]?\d*\.?\d+)(%)?\s*,\s*([+-]?\d*\.?\d+)(%)?\s*(?:,\s*([+-]?\d*\.?\d+)(%)?\s*)?\)$/i,m=/^rgba?\(\s*([+-]?\d*\.?\d+)(%)?\s+([+-]?\d*\.?\d+)(%)?\s+([+-]?\d*\.?\d+)(%)?\s*(?:\/\s*([+-]?\d*\.?\d+)(%)?\s*)?\)$/i,y={string:[[function(r){var t=i.exec(r);return t?(r=t[1]).length<=4?{r:parseInt(r[0]+r[0],16),g:parseInt(r[1]+r[1],16),b:parseInt(r[2]+r[2],16),a:4===r.length?n(parseInt(r[3]+r[3],16)/255,2):1}:6===r.length||8===r.length?{r:parseInt(r.substr(0,2),16),g:parseInt(r.substr(2,2),16),b:parseInt(r.substr(4,2),16),a:8===r.length?n(parseInt(r.substr(6,2),16)/255,2):1}:null:null},"hex"],[function(r){var t=v.exec(r)||m.exec(r);return t?t[2]!==t[4]||t[4]!==t[6]?null:a({r:Number(t[1])/(t[2]?100/255:1),g:Number(t[3])/(t[4]?100/255:1),b:Number(t[5])/(t[6]?100/255:1),a:void 0===t[7]?1:Number(t[7])/(t[8]?100:1)}):null},"rgb"],[function(t){var n=l.exec(t)||p.exec(t);if(!n)return null;var e,u,a=g({h:(e=n[1],u=n[2],void 0===u&&(u="deg"),Number(e)*(r[u]||1)),s:Number(n[3]),l:Number(n[4]),a:void 0===n[5]?1:Number(n[5])/(n[6]?100:1)});return f(a)},"hsl"]],object:[[function(r){var n=r.r,e=r.g,u=r.b,o=r.a,i=void 0===o?1:o;return t(n)&&t(e)&&t(u)?a({r:Number(n),g:Number(e),b:Number(u),a:Number(i)}):null},"rgb"],[function(r){var n=r.h,e=r.s,u=r.l,a=r.a,o=void 0===a?1:a;if(!t(n)||!t(e)||!t(u))return null;var i=g({h:Number(n),s:Number(e),l:Number(u),a:Number(o)});return f(i)},"hsl"],[function(r){var n=r.h,a=r.s,o=r.v,i=r.a,s=void 0===i?1:i;if(!t(n)||!t(a)||!t(o))return null;var h=function(r){return{h:u(r.h),s:e(r.s,0,100),v:e(r.v,0,100),a:e(r.a)}}({h:Number(n),s:Number(a),v:Number(o),a:Number(s)});return b(h)},"hsv"]]},N=function(r,t){for(var n=0;n<t.length;n++){var e=t[n][0](r);if(e)return[e,t[n][1]]}return[null,void 0]},x=function(r){return"string"==typeof r?N(r.trim(),y.string):"object"==typeof r&&null!==r?N(r,y.object):[null,void 0]},I=function(r){return x(r)[1]},M=function(r,t){var n=c(r);return{h:n.h,s:e(n.s+100*t,0,100),l:n.l,a:n.a}},H=function(r){return(299*r.r+587*r.g+114*r.b)/1e3/255},$=function(r,t){var n=c(r);return{h:n.h,s:n.s,l:e(n.l+100*t,0,100),a:n.a}},j=function(){function r(r){this.parsed=x(r)[0],this.rgba=this.parsed||{r:0,g:0,b:0,a:1}}return r.prototype.isValid=function(){return null!==this.parsed},r.prototype.brightness=function(){return n(H(this.rgba),2)},r.prototype.isDark=function(){return H(this.rgba)<.5},r.prototype.isLight=function(){return H(this.rgba)>=.5},r.prototype.toHex=function(){return r=o(this.rgba),t=r.r,e=r.g,u=r.b,i=(a=r.a)<1?s(n(255*a)):"","#"+s(t)+s(e)+s(u)+i;var r,t,e,u,a,i},r.prototype.toRgb=function(){return o(this.rgba)},r.prototype.toRgbString=function(){return r=o(this.rgba),t=r.r,n=r.g,e=r.b,(u=r.a)<1?"rgba("+t+", "+n+", "+e+", "+u+")":"rgb("+t+", "+n+", "+e+")";var r,t,n,e,u},r.prototype.toHsl=function(){return d(c(this.rgba))},r.prototype.toHslString=function(){return r=d(c(this.rgba)),t=r.h,n=r.s,e=r.l,(u=r.a)<1?"hsla("+t+", "+n+"%, "+e+"%, "+u+")":"hsl("+t+", "+n+"%, "+e+"%)";var r,t,n,e,u},r.prototype.toHsv=function(){return r=h(this.rgba),{h:n(r.h),s:n(r.s),v:n(r.v),a:n(r.a,3)};var r},r.prototype.invert=function(){return w({r:255-(r=this.rgba).r,g:255-r.g,b:255-r.b,a:r.a});var r},r.prototype.saturate=function(r){return void 0===r&&(r=.1),w(M(this.rgba,r))},r.prototype.desaturate=function(r){return void 0===r&&(r=.1),w(M(this.rgba,-r))},r.prototype.grayscale=function(){return w(M(this.rgba,-1))},r.prototype.lighten=function(r){return void 0===r&&(r=.1),w($(this.rgba,r))},r.prototype.darken=function(r){return void 0===r&&(r=.1),w($(this.rgba,-r))},r.prototype.rotate=function(r){return void 0===r&&(r=15),this.hue(this.hue()+r)},r.prototype.alpha=function(r){return"number"==typeof r?w({r:(t=this.rgba).r,g:t.g,b:t.b,a:r}):n(this.rgba.a,3);var t},r.prototype.hue=function(r){var t=c(this.rgba);return"number"==typeof r?w({h:r,s:t.s,l:t.l,a:t.a}):n(t.h)},r.prototype.isEqual=function(r){return this.toHex()===w(r).toHex()},r}(),w=function(r){return r instanceof j?r:new j(r)},S=[],k=function(r){r.forEach(function(r){S.indexOf(r)<0&&(r(j,y),S.push(r))})},E=function(){return new j({r:255*Math.random(),g:255*Math.random(),b:255*Math.random()})};

;// ../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/plugins/names.mjs
/* harmony default export */ function names(e,f){var a={white:"#ffffff",bisque:"#ffe4c4",blue:"#0000ff",cadetblue:"#5f9ea0",chartreuse:"#7fff00",chocolate:"#d2691e",coral:"#ff7f50",antiquewhite:"#faebd7",aqua:"#00ffff",azure:"#f0ffff",whitesmoke:"#f5f5f5",papayawhip:"#ffefd5",plum:"#dda0dd",blanchedalmond:"#ffebcd",black:"#000000",gold:"#ffd700",goldenrod:"#daa520",gainsboro:"#dcdcdc",cornsilk:"#fff8dc",cornflowerblue:"#6495ed",burlywood:"#deb887",aquamarine:"#7fffd4",beige:"#f5f5dc",crimson:"#dc143c",cyan:"#00ffff",darkblue:"#00008b",darkcyan:"#008b8b",darkgoldenrod:"#b8860b",darkkhaki:"#bdb76b",darkgray:"#a9a9a9",darkgreen:"#006400",darkgrey:"#a9a9a9",peachpuff:"#ffdab9",darkmagenta:"#8b008b",darkred:"#8b0000",darkorchid:"#9932cc",darkorange:"#ff8c00",darkslateblue:"#483d8b",gray:"#808080",darkslategray:"#2f4f4f",darkslategrey:"#2f4f4f",deeppink:"#ff1493",deepskyblue:"#00bfff",wheat:"#f5deb3",firebrick:"#b22222",floralwhite:"#fffaf0",ghostwhite:"#f8f8ff",darkviolet:"#9400d3",magenta:"#ff00ff",green:"#008000",dodgerblue:"#1e90ff",grey:"#808080",honeydew:"#f0fff0",hotpink:"#ff69b4",blueviolet:"#8a2be2",forestgreen:"#228b22",lawngreen:"#7cfc00",indianred:"#cd5c5c",indigo:"#4b0082",fuchsia:"#ff00ff",brown:"#a52a2a",maroon:"#800000",mediumblue:"#0000cd",lightcoral:"#f08080",darkturquoise:"#00ced1",lightcyan:"#e0ffff",ivory:"#fffff0",lightyellow:"#ffffe0",lightsalmon:"#ffa07a",lightseagreen:"#20b2aa",linen:"#faf0e6",mediumaquamarine:"#66cdaa",lemonchiffon:"#fffacd",lime:"#00ff00",khaki:"#f0e68c",mediumseagreen:"#3cb371",limegreen:"#32cd32",mediumspringgreen:"#00fa9a",lightskyblue:"#87cefa",lightblue:"#add8e6",midnightblue:"#191970",lightpink:"#ffb6c1",mistyrose:"#ffe4e1",moccasin:"#ffe4b5",mintcream:"#f5fffa",lightslategray:"#778899",lightslategrey:"#778899",navajowhite:"#ffdead",navy:"#000080",mediumvioletred:"#c71585",powderblue:"#b0e0e6",palegoldenrod:"#eee8aa",oldlace:"#fdf5e6",paleturquoise:"#afeeee",mediumturquoise:"#48d1cc",mediumorchid:"#ba55d3",rebeccapurple:"#663399",lightsteelblue:"#b0c4de",mediumslateblue:"#7b68ee",thistle:"#d8bfd8",tan:"#d2b48c",orchid:"#da70d6",mediumpurple:"#9370db",purple:"#800080",pink:"#ffc0cb",skyblue:"#87ceeb",springgreen:"#00ff7f",palegreen:"#98fb98",red:"#ff0000",yellow:"#ffff00",slateblue:"#6a5acd",lavenderblush:"#fff0f5",peru:"#cd853f",palevioletred:"#db7093",violet:"#ee82ee",teal:"#008080",slategray:"#708090",slategrey:"#708090",aliceblue:"#f0f8ff",darkseagreen:"#8fbc8f",darkolivegreen:"#556b2f",greenyellow:"#adff2f",seagreen:"#2e8b57",seashell:"#fff5ee",tomato:"#ff6347",silver:"#c0c0c0",sienna:"#a0522d",lavender:"#e6e6fa",lightgreen:"#90ee90",orange:"#ffa500",orangered:"#ff4500",steelblue:"#4682b4",royalblue:"#4169e1",turquoise:"#40e0d0",yellowgreen:"#9acd32",salmon:"#fa8072",saddlebrown:"#8b4513",sandybrown:"#f4a460",rosybrown:"#bc8f8f",darksalmon:"#e9967a",lightgoldenrodyellow:"#fafad2",snow:"#fffafa",lightgrey:"#d3d3d3",lightgray:"#d3d3d3",dimgray:"#696969",dimgrey:"#696969",olivedrab:"#6b8e23",olive:"#808000"},r={};for(var d in a)r[a[d]]=d;var l={};e.prototype.toName=function(f){if(!(this.rgba.a||this.rgba.r||this.rgba.g||this.rgba.b))return"transparent";var d,i,n=r[this.toHex()];if(n)return n;if(null==f?void 0:f.closest){var o=this.toRgb(),t=1/0,b="black";if(!l.length)for(var c in a)l[c]=new e(a[c]).toRgb();for(var g in a){var u=(d=o,i=l[g],Math.pow(d.r-i.r,2)+Math.pow(d.g-i.g,2)+Math.pow(d.b-i.b,2));u<t&&(t=u,b=g)}return b}};f.string.push([function(f){var r=f.toLowerCase(),d="transparent"===r?"#0000":a[r];return d?new e(d).toRgb():null},"name"])}

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/colors.js



let colorComputationNode;
k([names]);
function rgba(hexValue = "", alpha = 1) {
  return colord(hexValue).alpha(alpha).toRgbString();
}
function getColorComputationNode() {
  if (typeof document === "undefined") {
    return;
  }
  if (!colorComputationNode) {
    const el = document.createElement("div");
    el.setAttribute("data-g2-color-computation-node", "");
    document.body.appendChild(el);
    colorComputationNode = el;
  }
  return colorComputationNode;
}
function isColor(value) {
  if (typeof value !== "string") {
    return false;
  }
  const test = w(value);
  return test.isValid();
}
function _getComputedBackgroundColor(backgroundColor) {
  if (typeof backgroundColor !== "string") {
    return "";
  }
  if (isColor(backgroundColor)) {
    return backgroundColor;
  }
  if (!backgroundColor.includes("var(")) {
    return "";
  }
  if (typeof document === "undefined") {
    return "";
  }
  const el = getColorComputationNode();
  if (!el) {
    return "";
  }
  el.style.background = backgroundColor;
  const computedColor = window?.getComputedStyle(el).background;
  el.style.background = "";
  return computedColor || "";
}
const getComputedBackgroundColor = (0,dist/* default */.A)(_getComputedBackgroundColor);
function getOptimalTextColor(backgroundColor) {
  const background = getComputedBackgroundColor(backgroundColor);
  return w(background).isLight() ? "#000000" : "#ffffff";
}
function getOptimalTextShade(backgroundColor) {
  const result = getOptimalTextColor(backgroundColor);
  return result === "#000000" ? "dark" : "light";
}

//# sourceMappingURL=colors.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/colors-values.js
var colors_values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/colors-values.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js
var config_values = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/styles.js
function styles_EMOTION_STRINGIFIED_CSS_ERROR_() {
  return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop).";
}


const Text = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("color:", colors_values/* COLORS */.l.theme.foreground, ";line-height:", config_values/* default */.A.fontLineHeightBase, ";margin:0;text-wrap:balance;text-wrap:pretty;" + ( true ? "" : 0),  true ? "" : 0);
const block =  true ? {
  name: "4zleql",
  styles: "display:block"
} : 0;
const positive = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("color:", colors_values/* COLORS */.l.alert.green, ";" + ( true ? "" : 0),  true ? "" : 0);
const destructive = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("color:", colors_values/* COLORS */.l.alert.red, ";" + ( true ? "" : 0),  true ? "" : 0);
const muted = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("color:", colors_values/* COLORS */.l.gray[700], ";" + ( true ? "" : 0),  true ? "" : 0);
const highlighterText = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)("mark{background:", colors_values/* COLORS */.l.alert.yellow, ";border-radius:", config_values/* default */.A.radiusSmall, ";box-shadow:0 0 0 1px rgba( 0, 0, 0, 0.05 ) inset,0 -1px 0 rgba( 0, 0, 0, 0.1 ) inset;}" + ( true ? "" : 0),  true ? "" : 0);
const upperCase =  true ? {
  name: "50zrmy",
  styles: "text-transform:uppercase"
} : 0;

//# sourceMappingURL=styles.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/highlight-words-core@1.2.3/node_modules/highlight-words-core/dist/index.js
var highlight_words_core_dist = __webpack_require__("../../node_modules/.pnpm/highlight-words-core@1.2.3/node_modules/highlight-words-core/dist/index.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/utils.js



const lowercaseProps = (object) => {
  const mapped = {};
  for (const key in object) {
    mapped[key.toLowerCase()] = object[key];
  }
  return mapped;
};
const memoizedLowercaseProps = (0,dist/* default */.A)(lowercaseProps);
function createHighlighterText({
  activeClassName = "",
  activeIndex = -1,
  activeStyle,
  autoEscape,
  caseSensitive = false,
  children,
  findChunks,
  highlightClassName = "",
  highlightStyle = {},
  highlightTag = "mark",
  sanitize,
  searchWords = [],
  unhighlightClassName = "",
  unhighlightStyle
}) {
  if (!children) {
    return null;
  }
  if (typeof children !== "string") {
    return children;
  }
  const textToHighlight = children;
  const chunks = (0,highlight_words_core_dist.findAll)({
    autoEscape,
    caseSensitive,
    findChunks,
    sanitize,
    searchWords,
    textToHighlight
  });
  const HighlightTag = highlightTag;
  let highlightIndex = -1;
  let highlightClassNames = "";
  let highlightStyles;
  const textContent = chunks.map((chunk, index) => {
    const text = textToHighlight.substr(chunk.start, chunk.end - chunk.start);
    if (chunk.highlight) {
      highlightIndex++;
      let highlightClass;
      if (typeof highlightClassName === "object") {
        if (!caseSensitive) {
          highlightClassName = memoizedLowercaseProps(highlightClassName);
          highlightClass = highlightClassName[text.toLowerCase()];
        } else {
          highlightClass = highlightClassName[text];
        }
      } else {
        highlightClass = highlightClassName;
      }
      const isActive = highlightIndex === +activeIndex;
      highlightClassNames = `${highlightClass} ${isActive ? activeClassName : ""}`;
      highlightStyles = isActive === true && activeStyle !== null ? Object.assign({}, highlightStyle, activeStyle) : highlightStyle;
      const props = {
        children: text,
        className: highlightClassNames,
        key: index,
        style: highlightStyles
      };
      if (typeof HighlightTag !== "string") {
        props.highlightIndex = highlightIndex;
      }
      return (0,react.createElement)(HighlightTag, props);
    }
    return (0,react.createElement)("span", {
      children: text,
      className: unhighlightClassName,
      key: index,
      style: unhighlightStyle
    });
  });
  return textContent;
}

//# sourceMappingURL=utils.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/font-size.js
var font_size = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/font-size.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/space.js
var space = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/space.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/get-line-height.js


function getLineHeight(adjustLineHeightForInnerControls, lineHeight) {
  if (lineHeight) {
    return lineHeight;
  }
  if (!adjustLineHeightForInnerControls) {
    return;
  }
  let value = `calc(${config_values/* default */.A.controlHeight} + ${(0,space/* space */.x)(2)})`;
  switch (adjustLineHeightForInnerControls) {
    case "large":
      value = `calc(${config_values/* default */.A.controlHeightLarge} + ${(0,space/* space */.x)(2)})`;
      break;
    case "small":
      value = `calc(${config_values/* default */.A.controlHeightSmall} + ${(0,space/* space */.x)(2)})`;
      break;
    case "xSmall":
      value = `calc(${config_values/* default */.A.controlHeightXSmall} + ${(0,space/* space */.x)(2)})`;
      break;
    default:
      break;
  }
  return value;
}

//# sourceMappingURL=get-line-height.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text/hook.js
function hook_EMOTION_STRINGIFIED_CSS_ERROR_() {
  return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop).";
}











var _ref =  true ? {
  name: "50zrmy",
  styles: "text-transform:uppercase"
} : 0;
function useText(props) {
  const {
    adjustLineHeightForInnerControls,
    align,
    children,
    className,
    color,
    ellipsizeMode,
    isDestructive = false,
    display,
    highlightEscape = false,
    highlightCaseSensitive = false,
    highlightWords,
    highlightSanitize,
    isBlock = false,
    letterSpacing,
    lineHeight: lineHeightProp,
    optimizeReadabilityFor,
    size,
    truncate = false,
    upperCase = false,
    variant,
    weight = config_values/* default */.A.fontWeight,
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, "Text");
  let content = children;
  const isHighlighter = Array.isArray(highlightWords);
  const isCaption = size === "caption";
  if (isHighlighter) {
    if (typeof children !== "string") {
      throw new TypeError("`children` of `Text` must only be `string` types when `highlightWords` is defined");
    }
    content = createHighlighterText({
      autoEscape: highlightEscape,
      children,
      caseSensitive: highlightCaseSensitive,
      searchWords: highlightWords,
      sanitize: highlightSanitize
    });
  }
  const cx = (0,use_cx/* useCx */.l)();
  const classes = (0,react.useMemo)(() => {
    const sx = {};
    const lineHeight = getLineHeight(adjustLineHeightForInnerControls, lineHeightProp);
    sx.Base = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)({
      color,
      display,
      fontSize: (0,font_size/* getFontSize */.ny)(size),
      fontWeight: weight,
      lineHeight,
      letterSpacing,
      textAlign: align
    },  true ? "" : 0,  true ? "" : 0);
    sx.upperCase = _ref;
    sx.optimalTextColor = null;
    if (optimizeReadabilityFor) {
      const isOptimalTextColorDark = getOptimalTextShade(optimizeReadabilityFor) === "dark";
      sx.optimalTextColor = isOptimalTextColorDark ? /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)({
        color: colors_values/* COLORS */.l.gray[900]
      },  true ? "" : 0,  true ? "" : 0) : /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)({
        color: colors_values/* COLORS */.l.white
      },  true ? "" : 0,  true ? "" : 0);
    }
    return cx(Text, sx.Base, sx.optimalTextColor, isDestructive && destructive, !!isHighlighter && highlighterText, isBlock && block, isCaption && muted, variant && text_styles_namespaceObject[variant], upperCase && sx.upperCase, className);
  }, [adjustLineHeightForInnerControls, align, className, color, cx, display, isBlock, isCaption, isDestructive, isHighlighter, letterSpacing, lineHeightProp, optimizeReadabilityFor, size, upperCase, variant, weight]);
  let finalEllipsizeMode;
  if (truncate === true) {
    finalEllipsizeMode = "auto";
  }
  if (truncate === false) {
    finalEllipsizeMode = "none";
  }
  const finalComponentProps = {
    ...otherProps,
    className: classes,
    children,
    ellipsizeMode: ellipsizeMode || finalEllipsizeMode
  };
  const truncateProps = useTruncate(finalComponentProps);
  if (!truncate && Array.isArray(children)) {
    content = react.Children.map(children, (child) => {
      if (typeof child !== "object" || child === null || !("props" in child)) {
        return child;
      }
      const isLink = (0,context_connect/* hasConnectNamespace */.SZ)(child, ["Link"]);
      if (isLink) {
        return (0,react.cloneElement)(child, {
          size: child.props.size || "inherit"
        });
      }
      return child;
    });
  }
  return {
    ...truncateProps,
    children: truncate ? truncateProps.children : content
  };
}

//# sourceMappingURL=hook.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ config_values_default)
/* harmony export */ });
/* harmony import */ var _space__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/space.js");
/* harmony import */ var _colors_values__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/colors-values.js");


const CONTROL_HEIGHT = "36px";
const CONTROL_PROPS = {
  // These values should be shared with TextControl.
  controlPaddingX: 12,
  controlPaddingXSmall: 8,
  controlPaddingXLarge: 12 * 1.3334,
  // TODO: Deprecate
  controlBoxShadowFocus: `0 0 0 0.5px ${_colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .l.theme.accent}`,
  controlHeight: CONTROL_HEIGHT,
  controlHeightXSmall: `calc( ${CONTROL_HEIGHT} * 0.6 )`,
  controlHeightSmall: `calc( ${CONTROL_HEIGHT} * 0.8 )`,
  controlHeightLarge: `calc( ${CONTROL_HEIGHT} * 1.2 )`,
  controlHeightXLarge: `calc( ${CONTROL_HEIGHT} * 1.4 )`
};
var config_values_default = Object.assign({}, CONTROL_PROPS, {
  colorDivider: "rgba(0, 0, 0, 0.1)",
  colorScrollbarThumb: "rgba(0, 0, 0, 0.2)",
  colorScrollbarThumbHover: "rgba(0, 0, 0, 0.5)",
  colorScrollbarTrack: "rgba(0, 0, 0, 0.04)",
  elevationIntensity: 1,
  radiusXSmall: "1px",
  radiusSmall: "2px",
  radiusMedium: "4px",
  radiusLarge: "8px",
  radiusFull: "9999px",
  radiusRound: "50%",
  borderWidth: "1px",
  borderWidthFocus: "1.5px",
  borderWidthTab: "4px",
  spinnerSize: 16,
  fontSize: "13px",
  fontSizeH1: "calc(2.44 * 13px)",
  fontSizeH2: "calc(1.95 * 13px)",
  fontSizeH3: "calc(1.56 * 13px)",
  fontSizeH4: "calc(1.25 * 13px)",
  fontSizeH5: "13px",
  fontSizeH6: "calc(0.8 * 13px)",
  fontSizeInputMobile: "16px",
  fontSizeMobile: "15px",
  fontSizeSmall: "calc(0.92 * 13px)",
  fontSizeXSmall: "calc(0.75 * 13px)",
  fontLineHeightBase: "1.4",
  fontWeight: "normal",
  fontWeightHeading: "600",
  gridBase: "4px",
  cardPaddingXSmall: `${(0,_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(2)}`,
  cardPaddingSmall: `${(0,_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(4)}`,
  cardPaddingMedium: `${(0,_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(4)} ${(0,_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(6)}`,
  cardPaddingLarge: `${(0,_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(6)} ${(0,_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(8)}`,
  elevationXSmall: `0 1px 1px rgba(0, 0, 0, 0.03), 0 1px 2px rgba(0, 0, 0, 0.02), 0 3px 3px rgba(0, 0, 0, 0.02), 0 4px 4px rgba(0, 0, 0, 0.01)`,
  elevationSmall: `0 1px 2px rgba(0, 0, 0, 0.05), 0 2px 3px rgba(0, 0, 0, 0.04), 0 6px 6px rgba(0, 0, 0, 0.03), 0 8px 8px rgba(0, 0, 0, 0.02)`,
  elevationMedium: `0 2px 3px rgba(0, 0, 0, 0.05), 0 4px 5px rgba(0, 0, 0, 0.04), 0 12px 12px rgba(0, 0, 0, 0.03), 0 16px 16px rgba(0, 0, 0, 0.02)`,
  elevationLarge: `0 5px 15px rgba(0, 0, 0, 0.08), 0 15px 27px rgba(0, 0, 0, 0.07), 0 30px 36px rgba(0, 0, 0, 0.04), 0 50px 43px rgba(0, 0, 0, 0.02)`,
  surfaceBackgroundColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .l.white,
  surfaceBackgroundSubtleColor: "#F3F3F3",
  surfaceBackgroundTintColor: "#F5F5F5",
  surfaceBorderColor: "rgba(0, 0, 0, 0.1)",
  surfaceBorderBoldColor: "rgba(0, 0, 0, 0.15)",
  surfaceBorderSubtleColor: "rgba(0, 0, 0, 0.05)",
  surfaceBackgroundTertiaryColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .l.white,
  surfaceColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .l.white,
  transitionDuration: "200ms",
  transitionDurationFast: "160ms",
  transitionDurationFaster: "120ms",
  transitionDurationFastest: "100ms",
  transitionTimingFunction: "cubic-bezier(0.08, 0.52, 0.52, 1)",
  transitionTimingFunctionControl: "cubic-bezier(0.12, 0.8, 0.32, 1)"
});

//# sourceMappingURL=config-values.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/font-size.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   fM: () => (/* binding */ getHeadingFontSize),
/* harmony export */   ny: () => (/* binding */ getFontSize)
/* harmony export */ });
/* unused harmony exports BASE_FONT_SIZE, HEADING_FONT_SIZES, PRESET_FONT_SIZES */
/* harmony import */ var _config_values__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/config-values.js");

const BASE_FONT_SIZE = 13;
const PRESET_FONT_SIZES = {
  body: BASE_FONT_SIZE,
  caption: 10,
  footnote: 11,
  largeTitle: 28,
  subheadline: 12,
  title: 20
};
const HEADING_FONT_SIZES = [1, 2, 3, 4, 5, 6].flatMap((n) => [n, n.toString()]);
function getFontSize(size = BASE_FONT_SIZE) {
  if (size in PRESET_FONT_SIZES) {
    return getFontSize(PRESET_FONT_SIZES[size]);
  }
  if (typeof size !== "number") {
    const parsed = parseFloat(size);
    if (Number.isNaN(parsed)) {
      return size;
    }
    size = parsed;
  }
  const ratio = `(${size} / ${BASE_FONT_SIZE})`;
  return `calc(${ratio} * ${_config_values__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.fontSize})`;
}
function getHeadingFontSize(size = 3) {
  if (!HEADING_FONT_SIZES.includes(size)) {
    return getFontSize(size);
  }
  const headingSize = `fontSizeH${size}`;
  return _config_values__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A[headingSize];
}

//# sourceMappingURL=font-size.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/highlight-words-core@1.2.3/node_modules/highlight-words-core/dist/index.js":
/***/ ((module) => {

module.exports =
/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __nested_webpack_require_187__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId])
/******/ 			return installedModules[moduleId].exports;
/******/
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			exports: {},
/******/ 			id: moduleId,
/******/ 			loaded: false
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __nested_webpack_require_187__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__nested_webpack_require_187__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__nested_webpack_require_187__.c = installedModules;
/******/
/******/ 	// __webpack_public_path__
/******/ 	__nested_webpack_require_187__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __nested_webpack_require_187__(0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __nested_webpack_require_1468__) {

	module.exports = __nested_webpack_require_1468__(1);


/***/ }),
/* 1 */
/***/ (function(module, exports, __nested_webpack_require_1587__) {

	'use strict';
	
	Object.defineProperty(exports, "__esModule", {
	  value: true
	});
	
	var _utils = __nested_webpack_require_1587__(2);
	
	Object.defineProperty(exports, 'combineChunks', {
	  enumerable: true,
	  get: function get() {
	    return _utils.combineChunks;
	  }
	});
	Object.defineProperty(exports, 'fillInChunks', {
	  enumerable: true,
	  get: function get() {
	    return _utils.fillInChunks;
	  }
	});
	Object.defineProperty(exports, 'findAll', {
	  enumerable: true,
	  get: function get() {
	    return _utils.findAll;
	  }
	});
	Object.defineProperty(exports, 'findChunks', {
	  enumerable: true,
	  get: function get() {
	    return _utils.findChunks;
	  }
	});

/***/ }),
/* 2 */
/***/ (function(module, exports) {

	'use strict';
	
	Object.defineProperty(exports, "__esModule", {
	  value: true
	});
	
	
	/**
	 * Creates an array of chunk objects representing both higlightable and non highlightable pieces of text that match each search word.
	 * @return Array of "chunks" (where a Chunk is { start:number, end:number, highlight:boolean })
	 */
	var findAll = exports.findAll = function findAll(_ref) {
	  var autoEscape = _ref.autoEscape,
	      _ref$caseSensitive = _ref.caseSensitive,
	      caseSensitive = _ref$caseSensitive === undefined ? false : _ref$caseSensitive,
	      _ref$findChunks = _ref.findChunks,
	      findChunks = _ref$findChunks === undefined ? defaultFindChunks : _ref$findChunks,
	      sanitize = _ref.sanitize,
	      searchWords = _ref.searchWords,
	      textToHighlight = _ref.textToHighlight;
	  return fillInChunks({
	    chunksToHighlight: combineChunks({
	      chunks: findChunks({
	        autoEscape: autoEscape,
	        caseSensitive: caseSensitive,
	        sanitize: sanitize,
	        searchWords: searchWords,
	        textToHighlight: textToHighlight
	      })
	    }),
	    totalLength: textToHighlight ? textToHighlight.length : 0
	  });
	};
	
	/**
	 * Takes an array of {start:number, end:number} objects and combines chunks that overlap into single chunks.
	 * @return {start:number, end:number}[]
	 */
	
	
	var combineChunks = exports.combineChunks = function combineChunks(_ref2) {
	  var chunks = _ref2.chunks;
	
	  chunks = chunks.sort(function (first, second) {
	    return first.start - second.start;
	  }).reduce(function (processedChunks, nextChunk) {
	    // First chunk just goes straight in the array...
	    if (processedChunks.length === 0) {
	      return [nextChunk];
	    } else {
	      // ... subsequent chunks get checked to see if they overlap...
	      var prevChunk = processedChunks.pop();
	      if (nextChunk.start < prevChunk.end) {
	        // It may be the case that prevChunk completely surrounds nextChunk, so take the
	        // largest of the end indexes.
	        var endIndex = Math.max(prevChunk.end, nextChunk.end);
	        processedChunks.push({ highlight: false, start: prevChunk.start, end: endIndex });
	      } else {
	        processedChunks.push(prevChunk, nextChunk);
	      }
	      return processedChunks;
	    }
	  }, []);
	
	  return chunks;
	};
	
	/**
	 * Examine text for any matches.
	 * If we find matches, add them to the returned array as a "chunk" object ({start:number, end:number}).
	 * @return {start:number, end:number}[]
	 */
	var defaultFindChunks = function defaultFindChunks(_ref3) {
	  var autoEscape = _ref3.autoEscape,
	      caseSensitive = _ref3.caseSensitive,
	      _ref3$sanitize = _ref3.sanitize,
	      sanitize = _ref3$sanitize === undefined ? defaultSanitize : _ref3$sanitize,
	      searchWords = _ref3.searchWords,
	      textToHighlight = _ref3.textToHighlight;
	
	  textToHighlight = sanitize(textToHighlight);
	
	  return searchWords.filter(function (searchWord) {
	    return searchWord;
	  }) // Remove empty words
	  .reduce(function (chunks, searchWord) {
	    searchWord = sanitize(searchWord);
	
	    if (autoEscape) {
	      searchWord = escapeRegExpFn(searchWord);
	    }
	
	    var regex = new RegExp(searchWord, caseSensitive ? 'g' : 'gi');
	
	    var match = void 0;
	    while (match = regex.exec(textToHighlight)) {
	      var _start = match.index;
	      var _end = regex.lastIndex;
	      // We do not return zero-length matches
	      if (_end > _start) {
	        chunks.push({ highlight: false, start: _start, end: _end });
	      }
	
	      // Prevent browsers like Firefox from getting stuck in an infinite loop
	      // See http://www.regexguru.com/2008/04/watch-out-for-zero-length-matches/
	      if (match.index === regex.lastIndex) {
	        regex.lastIndex++;
	      }
	    }
	
	    return chunks;
	  }, []);
	};
	// Allow the findChunks to be overridden in findAll,
	// but for backwards compatibility we export as the old name
	exports.findChunks = defaultFindChunks;
	
	/**
	 * Given a set of chunks to highlight, create an additional set of chunks
	 * to represent the bits of text between the highlighted text.
	 * @param chunksToHighlight {start:number, end:number}[]
	 * @param totalLength number
	 * @return {start:number, end:number, highlight:boolean}[]
	 */
	
	var fillInChunks = exports.fillInChunks = function fillInChunks(_ref4) {
	  var chunksToHighlight = _ref4.chunksToHighlight,
	      totalLength = _ref4.totalLength;
	
	  var allChunks = [];
	  var append = function append(start, end, highlight) {
	    if (end - start > 0) {
	      allChunks.push({
	        start: start,
	        end: end,
	        highlight: highlight
	      });
	    }
	  };
	
	  if (chunksToHighlight.length === 0) {
	    append(0, totalLength, false);
	  } else {
	    var lastIndex = 0;
	    chunksToHighlight.forEach(function (chunk) {
	      append(lastIndex, chunk.start, false);
	      append(chunk.start, chunk.end, true);
	      lastIndex = chunk.end;
	    });
	    append(lastIndex, totalLength, false);
	  }
	  return allChunks;
	};
	
	function defaultSanitize(string) {
	  return string;
	}
	
	function escapeRegExpFn(string) {
	  return string.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&');
	}

/***/ })
/******/ ]);
//# sourceMappingURL=index.js.map

/***/ })

}]);