"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[694],{

/***/ "../../packages/js/components/src/phone-number-input/stories/phone-number-input.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Examples: () => (/* binding */ Examples),
  "default": () => (/* binding */ phone_number_input_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js + 1 modules
var objectWithoutProperties = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js
var es_date_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js
var es_function_name = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.trim-start.js
var es_string_trim_start = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.trim-start.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js
var es_regexp_exec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js
var es_string_replace = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js
var es_array_slice = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/downshift@6.1.12_react@17.0.2/node_modules/downshift/dist/downshift.esm.js + 1 modules
var downshift_esm = __webpack_require__("../../node_modules/.pnpm/downshift@6.1.12_react@17.0.2/node_modules/downshift/dist/downshift.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js
var es_object_get_own_property_descriptor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js
var es_array_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js
var web_dom_collections_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js
var es_object_get_own_property_descriptors = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js
var es_object_define_properties = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js
var es_object_define_property = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reduce.js
var es_array_reduce = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reduce.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/phone-number-input/data.ts










function ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function _objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}





// Do not edit this file directly.
// Generated by /bin/packages/js/components/phone-number-input/build-data.js

/* eslint-disable */

var parse = function parse(data) {
  return data.reduce(function (acc, item) {
    var _item$;
    return _objectSpread(_objectSpread({}, acc), {}, (0,defineProperty/* default */.A)({}, item[0], {
      alpha2: item[0],
      code: item[1].toString(),
      priority: item[2] || 0,
      start: (_item$ = item[3]) === null || _item$ === void 0 ? void 0 : _item$.map(String),
      lengths: item[4]
    }));
  }, {});
};
var data = [["AF", 93,, [7], [9]], ["AL", 355,, [6], [9]], ["DZ", 213,, [5, 6, 7], [9]], ["AS", 1, 5, [684, 684733, 684258], [10]], ["AD", 376,, [3, 4, 6], [6]], ["AO", 244,, [9], [9]], ["AI", 1, 6, [264, 2642, 2644, 2645, 2647], [10]], ["AG", 1, 7, [268, 2687], [10]], ["AR", 54,, [1, 2, 3], [8, 9, 10, 11, 12]], ["AM", 374,, [3, 4, 5, 7, 9], [8]], ["AW", 297,, [5, 6, 7, 9], [7]], ["AC", 247,,,], ["AU", 61,, [4], [9]], ["AT", 43,, [6], [10, 11, 12, 13, 14]], ["AZ", 994,, [4, 5, 6, 7], [9]], ["BS", 1, 8, [242], [10]], ["BH", 973,, [3], [8]], ["BD", 880,, [1], [8, 9, 10]], ["BB", 1, 9, [246], [10]], ["BY", 375,, [25, 29, 33, 44], [9]], ["BE", 32,, [4, 3], [9, 8]], ["BZ", 501,, [6], [7]], ["BJ", 229,, [4, 6, 9], [8]], ["BM", 1, 10, [441, 4413, 4415, 4417], [10]], ["BT", 975,, [17], [8]], ["BO", 591,, [6, 7], [8]], ["BA", 387,, [6], [8]], ["BW", 267,, [71, 72, 73, 74, 75, 76, 77, 78, 79], [8]], ["BR", 55,, [119, 129, 139, 149, 159, 169, 179, 189, 199, 219, 229, 249, 279, 289, 319, 329, 339, 349, 359, 379, 389, 419, 429, 439, 449, 459, 469, 479, 489, 499, 519, 539, 549, 559, 619, 629, 639, 649, 659, 669, 679, 689, 699, 719, 739, 749, 759, 779, 799, 819, 829, 839, 849, 859, 869, 879, 889, 899, 919, 929, 939, 949, 959, 969, 979, 989, 999], [10, 11]], ["IO", 246,,,], ["VG", 1, 11, [284], [10]], ["BN", 673,, [7, 8], [7]], ["BG", 359,, [87, 88, 89, 98, 99, 43], [8, 9]], ["BF", 226,, [6, 7], [8]], ["BI", 257,, [7, 29], [8]], ["KH", 855,, [1, 6, 7, 8, 9], [8, 9]], ["CM", 237,, [6], [9]], ["CA", 1, 1, [204, 226, 236, 249, 250, 263, 289, 306, 343, 354, 365, 367, 368, 382, 387, 403, 416, 418, 428, 431, 437, 438, 450, 584, 468, 474, 506, 514, 519, 548, 579, 581, 587, 604, 613, 639, 647, 672, 683, 705, 709, 742, 753, 778, 780, 782, 807, 819, 825, 867, 873, 902, 905, 600], [10]], ["CV", 238,, [5, 9], [7]], ["BQ", 599, 1, [3, 4, 7]], ["KY", 1, 12, [345], [10]], ["CF", 236,, [7], [8]], ["TD", 235,, [6, 7, 9], [8]], ["CL", 56,, [9], [9]], ["CN", 86,, [13, 14, 15, 17, 18, 19, 16], [11]], ["CX", 61, 2, [89164]], ["CC", 61, 1, [89162]], ["CO", 57,, [3], [10]], ["KM", 269,, [3, 76], [7]], ["CD", 243,, [8, 9], [9]], ["CG", 242,, [0], [9]], ["CK", 682,, [5, 7], [5]], ["CR", 506,, [5, 6, 7, 8], [8]], ["CI", 225,, [0, 4, 5, 6, 7, 8], [10]], ["HR", 385,, [9], [8, 9]], ["CU", 53,, [5], [8]], ["CW", 599,, [5, 6], [7]], ["CY", 357,, [9], [8]], ["CZ", 420,, [6, 7], [9]], ["DK", 45,, [2, 30, 31, 40, 41, 42, 50, 51, 52, 53, 60, 61, 71, 81, 91, 92, 93, 342, 344, 345, 346, 347, 348, 349, 356, 357, 359, 362, 365, 366, 389, 398, 431, 441, 462, 466, 468, 472, 474, 476, 478, 485, 486, 488, 489, 493, 494, 495, 496, 498, 499, 542, 543, 545, 551, 552, 556, 571, 572, 573, 574, 577, 579, 584, 586, 587, 589, 597, 598, 627, 629, 641, 649, 658, 662, 663, 664, 665, 667, 692, 693, 694, 697, 771, 772, 782, 783, 785, 786, 788, 789, 826, 827, 829], [8]], ["DJ", 253,, [77], [8]], ["DM", 1, 13, [767], [10]], ["DO", 1, 2, [809, 829, 849], [10]], ["EC", 593,, [9], [9]], ["EG", 20,, [1], [10, 8]], ["SV", 503,, [7], [8]], ["GQ", 240,, [222, 551], [9]], ["ER", 291,, [1, 7, 8], [7]], ["EE", 372,, [5, 81, 82, 83], [7, 8]], ["SZ", 268,, [76, 77, 78, 79], [8]], ["ET", 251,, [9], [9]], ["FK", 500,, [5, 6], [5]], ["FO", 298,,, [6]], ["FJ", 679,, [2, 7, 8, 9], [7]], ["FI", 358,, [4, 5], [9, 10]], ["FR", 33,, [6, 7], [9]], ["GF", 594,, [694], [9]], ["PF", 689,, [8], [8]], ["GA", 241,, [2, 3, 4, 5, 6, 7], [7]], ["GM", 220,, [7, 9], [7]], ["GE", 995,, [5, 7], [9]], ["DE", 49,, [15, 16, 17], [10, 11]], ["GH", 233,, [2, 5], [9]], ["GI", 350,, [5], [8]], ["GR", 30,, [6], [10]], ["GL", 299,, [2, 4, 5], [6]], ["GD", 1, 14, [473], [10]], ["GP", 590,, [690], [9]], ["GU", 1, 15, [671], [10]], ["GT", 502,, [3, 4, 5], [8]], ["GG", 44, 1, [1481, 7781, 7839, 7911]], ["GN", 224,, [6], [9]], ["GW", 245,, [5, 6, 7], [7]], ["GY", 592,, [6], [7]], ["HT", 509,, [3, 4], [8]], ["HN", 504,, [3, 7, 8, 9], [8]], ["HK", 852,, [4, 5, 6, 70, 71, 72, 73, 81, 82, 83, 84, 85, 86, 87, 88, 89, 9], [8]], ["HU", 36,, [20, 30, 31, 50, 70], [9]], ["IS", 354,, [6, 7, 8], [7]], ["IN", 91,, [6, 7, 8, 9], [10]], ["ID", 62,, [8], [9, 10, 11, 12]], ["IR", 98,, [9], [10]], ["IQ", 964,, [7], [10]], ["IE", 353,, [82, 83, 84, 85, 86, 87, 88, 89], [9]], ["IM", 44, 2, [1624, 74576, 7524, 7924, 7624]], ["IL", 972,, [5], [9]], ["IT", 39,, [3], [9, 10]], ["JM", 1, 4, [876, 658], [10]], ["JP", 81,, [70, 80, 90], [10]], ["JE", 44, 3, [1534, 7509, 7700, 7797, 7829, 7937]], ["JO", 962,, [7], [9]], ["KZ", 7, 1, [33, 7, 70, 74, 77], [10]], ["KE", 254,, [7, 1], [9]], ["KI", 686,, [9, 30], [5]], ["XK", 383,,,], ["KW", 965,, [5, 6, 9], [8]], ["KG", 996,, [20, 22, 31258, 312973, 5, 600, 7, 88, 912, 99], [9]], ["LA", 856,, [20], [10]], ["LV", 371,, [2], [8]], ["LB", 961,, [3, 7, 8], [7, 8]], ["LS", 266,, [5, 6], [8]], ["LR", 231,, [4, 5, 6, 7], [7, 8]], ["LY", 218,, [9], [9]], ["LI", 423,, [7], [7]], ["LT", 370,, [6], [8]], ["LU", 352,, [6], [9]], ["MO", 853,, [6], [8]], ["MG", 261,, [3], [9]], ["MW", 265,, [77, 88, 99], [9]], ["MY", 60,, [1, 6], [9, 10, 8]], ["MV", 960,, [7, 9], [7]], ["ML", 223,, [6, 7], [8]], ["MT", 356,, [7, 9], [8]], ["MH", 692,,, [7]], ["MQ", 596,, [696], [9]], ["MR", 222,,, [8]], ["MU", 230,, [5], [8]], ["YT", 262, 1, [269, 639], [9]], ["MX", 52,, [""], [10, 11]], ["FM", 691,,, [7]], ["MD", 373,, [6, 7], [8]], ["MC", 377,, [4, 6], [8, 9]], ["MN", 976,, [5, 8, 9], [8]], ["ME", 382,, [6], [8]], ["MS", 1, 16, [664], [10]], ["MA", 212,, [6, 7], [9]], ["MZ", 258,, [8], [9]], ["MM", 95,, [9], [8, 9, 10]], ["NA", 264,, [60, 81, 82, 85], [9]], ["NR", 674,, [555], [7]], ["NP", 977,, [97, 98], [10]], ["NL", 31,, [6], [9]], ["NC", 687,, [7, 8, 9], [6]], ["NZ", 64,, [2], [8, 9, 10]], ["NI", 505,, [8], [8]], ["NE", 227,, [9], [8]], ["NG", 234,, [70, 80, 81, 90, 91], [10]], ["NU", 683,,, [4]], ["NF", 672,, [5, 8], [5]], ["KP", 850,,,], ["MK", 389,, [7], [8]], ["MP", 1, 17, [670], [10]], ["NO", 47,, [4, 9], [8]], ["OM", 968,, [9], [8]], ["PK", 92,, [3], [10]], ["PW", 680,,, [7]], ["PS", 970,, [5], [9]], ["PA", 507,, [6], [8]], ["PG", 675,, [7], [8]], ["PY", 595,, [9], [9]], ["PE", 51,, [9], [9]], ["PH", 63,, [9], [10]], ["PL", 48,, [4, 5, 6, 7, 8], [9]], ["PT", 351,, [9], [9]], ["PR", 1, 3, [787, 939], [10]], ["QA", 974,, [3, 5, 6, 7], [8]], ["RE", 262,, [692, 693], [9]], ["RO", 40,, [7], [9]], ["RU", 7,, [9, 495, 498, 499, 835], [10]], ["RW", 250,, [7], [9]], ["BL", 590, 1,,], ["SH", 290,,, [4]], ["KN", 1, 18, [869], [10]], ["LC", 1, 19, [758], [10]], ["MF", 590, 2,,], ["PM", 508,, [55, 41], [6]], ["VC", 1, 20, [784], [10]], ["WS", 685,, [7], [7]], ["SM", 378,, [3, 6], [10]], ["ST", 239,, [98, 99], [7]], ["SA", 966,, [5], [9]], ["SN", 221,, [7], [9]], ["RS", 381,, [6], [8, 9]], ["SC", 248,, [2], [7]], ["SL", 232,, [21, 25, 30, 33, 34, 40, 44, 50, 55, 76, 77, 78, 79, 88], [8]], ["SG", 65,, [8, 9], [8]], ["SX", 1, 21, [721], [10]], ["SK", 421,, [9], [9]], ["SI", 386,, [3, 4, 5, 6, 7], [8]], ["SB", 677,, [7, 8], [7]], ["SO", 252,, [61, 62, 63, 65, 66, 68, 69, 71, 90], [9]], ["ZA", 27,, [1, 2, 3, 4, 5, 6, 7, 8], [9]], ["KR", 82,, [1], [9, 10]], ["SS", 211,, [9], [9]], ["ES", 34,, [6, 7], [9]], ["LK", 94,, [7], [9]], ["SD", 249,, [9], [9]], ["SR", 597,, [6, 7, 8], [7]], ["SJ", 47, 1, [79], [8]], ["SE", 46,, [7], [9]], ["CH", 41,, [74, 75, 76, 77, 78, 79], [9]], ["SY", 963,, [9], [9]], ["TW", 886,, [9], [9]], ["TJ", 992,, [9], [9]], ["TZ", 255,, [7, 6], [9]], ["TH", 66,, [6, 8, 9], [9]], ["TL", 670,, [7], [8]], ["TG", 228,, [9], [8]], ["TK", 690,,, [4]], ["TO", 676,,, [5]], ["TT", 1, 22, [868], [10]], ["TN", 216,, [2, 4, 5, 9], [8]], ["TR", 90,, [5], [10]], ["TM", 993,, [6], [8]], ["TC", 1, 23, [649, 6492, 6493, 6494], [10]], ["TV", 688,,, [5]], ["VI", 1, 24, [340], [10]], ["UG", 256,, [7], [9]], ["UA", 380,, [39, 50, 63, 66, 67, 68, 73, 9], [9]], ["AE", 971,, [5], [9]], ["GB", 44,, [7], [10]], ["US", 1,, [201, 202, 203, 205, 206, 207, 208, 209, 210, 212, 213, 214, 215, 216, 217, 218, 219, 220, 223, 224, 225, 227, 228, 229, 231, 234, 239, 240, 248, 251, 252, 253, 254, 256, 260, 262, 267, 269, 270, 272, 274, 276, 278, 281, 283, 301, 302, 303, 304, 305, 307, 308, 309, 310, 312, 313, 314, 315, 316, 317, 318, 319, 320, 321, 323, 325, 327, 330, 331, 332, 334, 336, 337, 339, 341, 346, 347, 351, 352, 360, 361, 364, 369, 380, 385, 386, 401, 402, 404, 405, 406, 407, 408, 409, 410, 412, 413, 414, 415, 417, 419, 423, 424, 425, 430, 432, 434, 435, 440, 441, 442, 443, 445, 447, 458, 463, 464, 469, 470, 475, 478, 479, 480, 484, 501, 502, 503, 504, 505, 507, 508, 509, 510, 512, 513, 515, 516, 517, 518, 520, 530, 531, 534, 539, 540, 541, 551, 557, 559, 561, 562, 563, 564, 567, 570, 571, 572, 573, 574, 575, 580, 582, 585, 586, 601, 602, 603, 605, 606, 607, 608, 609, 610, 612, 614, 615, 616, 617, 618, 619, 620, 623, 626, 627, 628, 629, 630, 631, 636, 640, 641, 646, 650, 651, 656, 657, 659, 660, 661, 662, 667, 669, 678, 679, 680, 681, 682, 689, 701, 702, 703, 704, 706, 707, 708, 712, 713, 714, 715, 716, 717, 718, 719, 720, 724, 725, 726, 727, 730, 731, 732, 734, 737, 740, 743, 747, 752, 754, 757, 760, 762, 763, 764, 765, 769, 770, 771, 772, 773, 774, 775, 779, 781, 785, 786, 787, 801, 802, 803, 804, 805, 806, 808, 810, 812, 813, 814, 815, 816, 817, 818, 820, 828, 830, 831, 832, 835, 838, 840, 843, 845, 847, 848, 850, 854, 856, 857, 858, 859, 860, 862, 863, 864, 865, 870, 872, 878, 901, 903, 904, 906, 907, 908, 909, 910, 912, 913, 914, 915, 916, 917, 918, 919, 920, 925, 927, 928, 929, 930, 931, 934, 935, 936, 937, 938, 939, 940, 941, 945, 947, 949, 951, 952, 954, 956, 957, 959, 970, 971, 972, 973, 975, 978, 979, 980, 984, 985, 986, 989, 888, 800, 833, 844, 855, 866, 877, 279, 340, 983, 448, 943, 363, 326, 839, 826, 948], [10]], ["UY", 598,, [9], [8]], ["UZ", 998,, [9, 88, 33], [9]], ["VU", 678,, [5, 7], [7]], ["VA", 39, 1, ["06698"]], ["VE", 58,, [4], [10]], ["VN", 84,, [8, 9, 3, 7, 5], [9]], ["WF", 681,,, [6]], ["EH", 212, 1, [5288, 5289]], ["YE", 967,, [7], [9]], ["ZM", 260,, [9, 7], [9]], ["ZW", 263,, [71, 73, 77, 78], [9]], ["AX", 358, 1, [18], [6, 7, 8]]];
/* harmony default export */ const phone_number_input_data = (parse(data));
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.from.js
var es_array_from = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.from.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js
var es_string_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.description.js
var es_symbol_description = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.description.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.iterator.js
var es_symbol_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js
var es_array_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js
var web_dom_collections_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js
var es_array_is_array = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js
var es_array_join = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js
var es_array_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js
var es_string_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.sort.js
var es_array_sort = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.sort.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.values.js
var es_object_values = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.values.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/phone-number-input/utils.ts



















var _window$wcSettings;
function _createForOfIteratorHelper(o, allowArrayLike) {
  var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"];
  if (!it) {
    if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") {
      if (it) o = it;
      var i = 0;
      var F = function F() {};
      return {
        s: F,
        n: function n() {
          if (i >= o.length) return {
            done: true
          };
          return {
            done: false,
            value: o[i++]
          };
        },
        e: function e(_e) {
          throw _e;
        },
        f: F
      };
    }
    throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
  }
  var normalCompletion = true,
    didErr = false,
    err;
  return {
    s: function s() {
      it = it.call(o);
    },
    n: function n() {
      var step = it.next();
      normalCompletion = step.done;
      return step;
    },
    e: function e(_e2) {
      didErr = true;
      err = _e2;
    },
    f: function f() {
      try {
        if (!normalCompletion && it["return"] != null) it["return"]();
      } finally {
        if (didErr) throw err;
      }
    }
  };
}
function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return _arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
}
function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;
  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];
  return arr2;
}
function utils_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function utils_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? utils_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : utils_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}












/**
 * Internal dependencies
 */

var mapValues = function mapValues(object, iteratee) {
  var result = {};
  for (var key in object) {
    result[key] = iteratee(object[key]);
  }
  return result;
};

/**
 * Removes any non-digit character.
 */
var sanitizeNumber = function sanitizeNumber(number) {
  return number.replace(/\D/g, '');
};

/**
 * Removes any non-digit character, except space and hyphen.
 */
var sanitizeInput = function sanitizeInput(number) {
  return number.replace(/[^\d -]/g, '');
};

/**
 * Converts a valid phone number to E.164 format.
 */
var numberToE164 = function numberToE164(number) {
  return "+".concat(sanitizeNumber(number));
};

/**
 * Guesses the country code from a phone number.
 * If no match is found, it will fallback to US.
 *
 * @param number       Phone number including country code.
 * @param countryCodes List of country codes.
 * @return Country code in ISO 3166-1 alpha-2 format. e.g. US
 */
var guessCountryKey = function guessCountryKey(number, countryCodes) {
  number = sanitizeNumber(number);
  // Match each digit against countryCodes until a match is found
  for (var i = number.length; i > 0; i--) {
    var match = countryCodes[number.substring(0, i)];
    if (match) return match[0];
  }
  return 'US';
};
var entityTable = {
  atilde: 'ã',
  ccedil: 'ç',
  eacute: 'é',
  iacute: 'í'
};

/**
 * Replaces HTML entities from a predefined table.
 */
var decodeHtmlEntities = function decodeHtmlEntities(str) {
  return str.replace(/&(\S+?);/g, function (match, p1) {
    return entityTable[p1] || match;
  });
};
var countryNames = mapValues(utils_objectSpread({
  AC: 'Ascension Island',
  XK: 'Kosovo'
}, ((_window$wcSettings = window.wcSettings) === null || _window$wcSettings === void 0 ? void 0 : _window$wcSettings.countries) || []), function (name) {
  return decodeHtmlEntities(name);
});

/**
 * Converts a country code to a flag twemoji URL from `s.w.org`.
 *
 * @param alpha2 Country code in ISO 3166-1 alpha-2 format. e.g. US
 * @return Country flag emoji URL.
 */
var countryToFlag = function countryToFlag(alpha2) {
  var name = alpha2.split('').map(function (_char) {
    return (0x1f1e5 + _char.charCodeAt(0) % 32).toString(16);
  }).join('-');
  return "https://s.w.org/images/core/emoji/14.0.0/72x72/".concat(name, ".png");
};
var pushOrAdd = function pushOrAdd(acc, key, value) {
  if (acc[key]) {
    if (!acc[key].includes(value)) acc[key].push(value);
  } else {
    acc[key] = [value];
  }
};

/**
 * Parses the data from `data.ts` into a more usable format.
 */
var parseData = function parseData(data) {
  return {
    countries: mapValues(data, function (country) {
      var _countryNames$country;
      return utils_objectSpread(utils_objectSpread({}, country), {}, {
        name: (_countryNames$country = countryNames[country.alpha2]) !== null && _countryNames$country !== void 0 ? _countryNames$country : country.alpha2,
        flag: countryToFlag(country.alpha2)
      });
    }),
    countryCodes: Object.values(data).sort(function (a, b) {
      return a.priority > b.priority ? 1 : -1;
    }).reduce(function (acc, _ref) {
      var code = _ref.code,
        alpha2 = _ref.alpha2,
        start = _ref.start;
      pushOrAdd(acc, code, alpha2);
      if (start) {
        var _iterator = _createForOfIteratorHelper(start),
          _step;
        try {
          for (_iterator.s(); !(_step = _iterator.n()).done;) {
            var str = _step.value;
            for (var i = 1; i <= str.length; i++) {
              pushOrAdd(acc, code + str.substring(0, i), alpha2);
            }
          }
        } catch (err) {
          _iterator.e(err);
        } finally {
          _iterator.f();
        }
      }
      return acc;
    }, {})
  };
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/phone-number-input/defaults.tsx



/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

var Flag = function Flag(_ref) {
  var alpha2 = _ref.alpha2,
    src = _ref.src;
  return (0,react.createElement)("img", {
    alt: "".concat(alpha2, " flag"),
    src: src,
    className: "wcpay-component-phone-number-input__flag"
  });
};
var defaultSelectedRender = function defaultSelectedRender(_ref2) {
  var alpha2 = _ref2.alpha2,
    code = _ref2.code,
    flag = _ref2.flag;
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(Flag, {
    alpha2: alpha2,
    src: flag
  }), " +".concat(code));
};
var defaultItemRender = function defaultItemRender(_ref3) {
  var alpha2 = _ref3.alpha2,
    name = _ref3.name,
    code = _ref3.code,
    flag = _ref3.flag;
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(Flag, {
    alpha2: alpha2,
    src: flag
  }), "".concat(name, " +").concat(code));
};
var defaultArrowRender = function defaultArrowRender() {
  return (0,react.createElement)(icon/* default */.A, {
    icon: chevron_down/* default */.A,
    size: 18
  });
};
try {
    // @ts-ignore
    defaultSelectedRender.displayName = "defaultSelectedRender";
    // @ts-ignore
    defaultSelectedRender.__docgenInfo = { "description": "", "displayName": "defaultSelectedRender", "props": { "name": { "defaultValue": null, "description": "", "name": "name", "required": true, "type": { "name": "string" } }, "flag": { "defaultValue": null, "description": "", "name": "flag", "required": true, "type": { "name": "string" } }, "alpha2": { "defaultValue": null, "description": "", "name": "alpha2", "required": true, "type": { "name": "string" } }, "code": { "defaultValue": null, "description": "", "name": "code", "required": true, "type": { "name": "string" } }, "priority": { "defaultValue": null, "description": "", "name": "priority", "required": true, "type": { "name": "number" } }, "start": { "defaultValue": null, "description": "", "name": "start", "required": false, "type": { "name": "string[]" } }, "lengths": { "defaultValue": null, "description": "", "name": "lengths", "required": false, "type": { "name": "number[]" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/phone-number-input/defaults.tsx#defaultSelectedRender"] = { docgenInfo: defaultSelectedRender.__docgenInfo, name: "defaultSelectedRender", path: "../../packages/js/components/src/phone-number-input/defaults.tsx#defaultSelectedRender" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    defaultItemRender.displayName = "defaultItemRender";
    // @ts-ignore
    defaultItemRender.__docgenInfo = { "description": "", "displayName": "defaultItemRender", "props": { "name": { "defaultValue": null, "description": "", "name": "name", "required": true, "type": { "name": "string" } }, "flag": { "defaultValue": null, "description": "", "name": "flag", "required": true, "type": { "name": "string" } }, "alpha2": { "defaultValue": null, "description": "", "name": "alpha2", "required": true, "type": { "name": "string" } }, "code": { "defaultValue": null, "description": "", "name": "code", "required": true, "type": { "name": "string" } }, "priority": { "defaultValue": null, "description": "", "name": "priority", "required": true, "type": { "name": "number" } }, "start": { "defaultValue": null, "description": "", "name": "start", "required": false, "type": { "name": "string[]" } }, "lengths": { "defaultValue": null, "description": "", "name": "lengths", "required": false, "type": { "name": "number[]" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/phone-number-input/defaults.tsx#defaultItemRender"] = { docgenInfo: defaultItemRender.__docgenInfo, name: "defaultItemRender", path: "../../packages/js/components/src/phone-number-input/defaults.tsx#defaultItemRender" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/phone-number-input/index.tsx










/**
 * External dependencies
 */




/**
 * Internal dependencies
 */



var _parseData = parseData(phone_number_input_data),
  countries = _parseData.countries,
  countryCodes = _parseData.countryCodes;

/**
 * An international phone number input with a country code select and a phone textfield which supports numbers, spaces and hyphens. And returns the full number as it is, in E.164 format, and the selected country alpha2.
 */
var PhoneNumberInput = function PhoneNumberInput(_ref) {
  var value = _ref.value,
    onChange = _ref.onChange,
    id = _ref.id,
    className = _ref.className,
    _ref$selectedRender = _ref.selectedRender,
    selectedRender = _ref$selectedRender === void 0 ? defaultSelectedRender : _ref$selectedRender,
    _ref$itemRender = _ref.itemRender,
    itemRender = _ref$itemRender === void 0 ? defaultItemRender : _ref$itemRender,
    _ref$arrowRender = _ref.arrowRender,
    arrowRender = _ref$arrowRender === void 0 ? defaultArrowRender : _ref$arrowRender;
  var menuRef = (0,react.useRef)(null);
  var inputRef = (0,react.useRef)(null);
  var _useState = (0,react.useState)(0),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    menuWidth = _useState2[0],
    setMenuWidth = _useState2[1];
  var _useState3 = (0,react.useState)(guessCountryKey(value, countryCodes)),
    _useState4 = (0,slicedToArray/* default */.A)(_useState3, 2),
    countryKey = _useState4[0],
    setCountryKey = _useState4[1];
  (0,react.useLayoutEffect)(function () {
    if (menuRef.current) {
      setMenuWidth(menuRef.current.offsetWidth);
    }
  }, [menuRef, countryKey]);
  var phoneNumber = sanitizeInput(value).replace(countries[countryKey].code, '').trimStart();
  var handleChange = function handleChange(code, number) {
    // Return value, phone number in E.164 format, and country alpha2 code.
    number = "+".concat(countries[code].code, " ").concat(number);
    onChange(number, numberToE164(number), code);
  };
  var handleSelect = function handleSelect(code) {
    setCountryKey(code);
    handleChange(code, phoneNumber);
  };
  var handleInput = function handleInput(event) {
    handleChange(countryKey, sanitizeInput(event.target.value));
  };
  var handleKeyDown = function handleKeyDown(event) {
    var _inputRef$current;
    var pos = ((_inputRef$current = inputRef.current) === null || _inputRef$current === void 0 ? void 0 : _inputRef$current.selectionStart) || 0;
    var newValue = phoneNumber.slice(0, pos) + event.key + phoneNumber.slice(pos);
    if (/[- ]{2,}/.test(newValue)) {
      event.preventDefault();
    }
  };
  var _useSelect = (0,downshift_esm/* useSelect */.WM)({
      id: id,
      items: Object.keys(countries),
      initialSelectedItem: countryKey,
      itemToString: function itemToString(item) {
        return countries[item || ''].name;
      },
      onSelectedItemChange: function onSelectedItemChange(_ref2) {
        var selectedItem = _ref2.selectedItem;
        if (selectedItem) handleSelect(selectedItem);
      },
      stateReducer: function stateReducer(state, _ref3) {
        var changes = _ref3.changes;
        if (state.isOpen === true && changes.isOpen === false) {
          var _inputRef$current2;
          (_inputRef$current2 = inputRef.current) === null || _inputRef$current2 === void 0 || _inputRef$current2.focus();
        }
        return changes;
      }
    }),
    isOpen = _useSelect.isOpen,
    getToggleButtonProps = _useSelect.getToggleButtonProps,
    getMenuProps = _useSelect.getMenuProps,
    highlightedIndex = _useSelect.highlightedIndex,
    getItemProps = _useSelect.getItemProps;
  return (0,react.createElement)("div", {
    className: classnames_default()(className, 'wcpay-component-phone-number-input')
  }, (0,react.createElement)("button", getToggleButtonProps({
    ref: menuRef,
    type: 'button',
    className: classnames_default()('wcpay-component-phone-number-input__button')
  }), selectedRender(countries[countryKey]), (0,react.createElement)("span", {
    className: classnames_default()('wcpay-component-phone-number-input__button-arrow', {
      invert: isOpen
    })
  }, arrowRender())), (0,react.createElement)("input", {
    id: id,
    ref: inputRef,
    type: "text",
    value: phoneNumber,
    onKeyDown: handleKeyDown,
    onChange: handleInput,
    className: "wcpay-component-phone-number-input__input",
    style: {
      paddingLeft: "".concat(menuWidth, "px")
    }
  }), (0,react.createElement)("ul", getMenuProps({
    'aria-hidden': !isOpen,
    className: 'wcpay-component-phone-number-input__menu'
  }), isOpen && Object.keys(countries).map(function (key, index) {
    return (
      // eslint-disable-next-line react/jsx-key
      (0,react.createElement)("li", getItemProps({
        key: key,
        index: index,
        item: key,
        className: classnames_default()('wcpay-component-phone-number-input__menu-item', {
          highlighted: highlightedIndex === index
        })
      }), itemRender(countries[key]))
    );
  })));
};
/* harmony default export */ const phone_number_input = (PhoneNumberInput);
try {
    // @ts-ignore
    phonenumberinput.displayName = "phonenumberinput";
    // @ts-ignore
    phonenumberinput.__docgenInfo = { "description": "An international phone number input with a country code select and a phone textfield which supports numbers, spaces and hyphens. And returns the full number as it is, in E.164 format, and the selected country alpha2.", "displayName": "phonenumberinput", "props": { "value": { "defaultValue": null, "description": "Phone number with spaces and hyphens.", "name": "value", "required": true, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "Callback function when the value changes.\n@param value Phone number with spaces and hyphens. e.g. `+1 234-567-8901`\n@param e164 Phone number in E.164 format. e.g. `+12345678901`\n@param country Country alpha2 code. e.g. `US`", "name": "onChange", "required": true, "type": { "name": "(value: string, e164: string, country: string) => void" } }, "id": { "defaultValue": { value: "undefined" }, "description": "ID for the input element, to bind a `<label>`.", "name": "id", "required": false, "type": { "name": "string" } }, "className": { "defaultValue": { value: "undefined" }, "description": "Additional class name applied to parent `<div>`.", "name": "className", "required": false, "type": { "name": "string" } }, "selectedRender": { "defaultValue": { value: "( { alpha2, code, flag }: Country ) => (\n\t<>\n\t\t<Flag alpha2={ alpha2 } src={ flag } />\n\t\t{ ` +${ code }` }\n\t</>\n)" }, "description": "Render function for the selected country.\nDisplays the country flag and code by default.", "name": "selectedRender", "required": false, "type": { "name": "((country: { name: string; flag: string; alpha2: string; code: string; priority: number; start?: string[]; lengths?: number[]; }) => ReactNode) | undefined" } }, "itemRender": { "defaultValue": { value: "( { alpha2, name, code, flag }: Country ) => (\n\t<>\n\t\t<Flag alpha2={ alpha2 } src={ flag } />\n\t\t{ `${ name } +${ code }` }\n\t</>\n)" }, "description": "Render function for each country in the dropdown.\nDisplays the country flag, name, and code by default.", "name": "itemRender", "required": false, "type": { "name": "((country: { name: string; flag: string; alpha2: string; code: string; priority: number; start?: string[]; lengths?: number[]; }) => ReactNode) | undefined" } }, "arrowRender": { "defaultValue": { value: "() => (\n\t<Icon icon={ chevronDown } size={ 18 } />\n)" }, "description": "Render function for the dropdown arrow.\nDisplays a chevron down icon by default.", "name": "arrowRender", "required": false, "type": { "name": "(() => ReactNode)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/phone-number-input/index.tsx#phonenumberinput"] = { docgenInfo: phonenumberinput.__docgenInfo, name: "phonenumberinput", path: "../../packages/js/components/src/phone-number-input/index.tsx#phonenumberinput" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.some.js
var es_array_some = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.some.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.starts-with.js
var es_string_starts_with = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.starts-with.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/phone-number-input/validation.ts








/**
 * Internal dependencies
 */


var validation_parseData = parseData(phone_number_input_data),
  validation_countries = validation_parseData.countries,
  validation_countryCodes = validation_parseData.countryCodes;

/**
 *	Mobile phone number validation based on `data.ts` rules.
 *  If no country is provided, it will try to guess it from the number or fallback to US.
 *
 * @param number        Phone number to validate in E.164 format. e.g. +12345678901
 * @param countryAlpha2 Country code in ISO 3166-1 alpha-2 format. e.g. US
 * @return boolean
 */
var validatePhoneNumber = function validatePhoneNumber(number, countryAlpha2) {
  // Sanitize number.
  number = '+' + number.replace(/\D/g, '');

  // Return early If format is not E.164.
  if (!/^\+[1-9]\d{1,14}$/.test(number)) {
    return false;
  }

  // If country is not provided, try to guess it from the number or fallback to US.
  if (!countryAlpha2) {
    countryAlpha2 = guessCountryKey(number, validation_countryCodes);
  }
  var country = validation_countries[countryAlpha2];

  // Remove `+` and country code.
  number = number.slice(country.code.length + 1);

  // If country as `lengths` defined check if number matches.
  if (country.lengths && !country.lengths.includes(number.length)) {
    return false;
  }

  // If country has `start` defined check if number starts with one of them.
  if (country.start && !country.start.some(function (prefix) {
    return number.startsWith(prefix);
  })) {
    return false;
  }
  return true;
};
;// CONCATENATED MODULE: ../../packages/js/components/src/phone-number-input/stories/phone-number-input.story.tsx



var _excluded = ["children", "onChange"];






/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


/* harmony default export */ const phone_number_input_story = ({
  title: 'WooCommerce Admin/components/PhoneNumberInput',
  component: phone_number_input
});
var PNI = function PNI(_ref) {
  var children = _ref.children,
    onChange = _ref.onChange,
    rest = (0,objectWithoutProperties/* default */.A)(_ref, _excluded);
  var _useState = (0,react.useState)(''),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    phone = _useState2[0],
    setPhone = _useState2[1];
  var _useState3 = (0,react.useState)(''),
    _useState4 = (0,slicedToArray/* default */.A)(_useState3, 2),
    output = _useState4[0],
    setOutput = _useState4[1];
  var handleChange = function handleChange(value, i164, country) {
    setPhone(value);
    setOutput(JSON.stringify({
      value: value,
      i164: i164,
      country: country
    }, null, 2));
    onChange === null || onChange === void 0 || onChange(value, i164, country);
  };
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)(phone_number_input, (0,esm_extends/* default */.A)({}, rest, {
    value: phone,
    onChange: handleChange
  })), children, (0,react.createElement)("pre", null, output));
};
var Examples = function Examples() {
  var _useState5 = (0,react.useState)(false),
    _useState6 = (0,slicedToArray/* default */.A)(_useState5, 2),
    valid = _useState6[0],
    setValid = _useState6[1];
  var handleValidation = function handleValidation(_, i164, country) {
    setValid(validatePhoneNumber(i164, country));
  };
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)("h2", null, "Basic"), (0,react.createElement)(PNI, null), (0,react.createElement)("h2", null, "Labeled"), (0,react.createElement)("label", {
    htmlFor: "pniID"
  }, "Phone number"), (0,react.createElement)("br", null), (0,react.createElement)(PNI, {
    id: "pniID"
  }), (0,react.createElement)("h2", null, "Validation"), (0,react.createElement)(PNI, {
    onChange: handleValidation
  }, (0,react.createElement)("pre", null, "valid: ", valid.toString())), (0,react.createElement)("h2", null, "Custom renders"), (0,react.createElement)(PNI, {
    arrowRender: function arrowRender() {
      return '🔻';
    },
    itemRender: function itemRender(_ref2) {
      var name = _ref2.name,
        code = _ref2.code;
      return "+".concat(code, ":").concat(name);
    },
    selectedRender: function selectedRender(_ref3) {
      var alpha2 = _ref3.alpha2,
        code = _ref3.code;
      return "+".concat(code, ":").concat(alpha2);
    }
  }));
};
Examples.parameters = {
  ...Examples.parameters,
  docs: {
    ...Examples.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [valid, setValid] = useState(false);\n  const handleValidation = (_, i164, country) => {\n    setValid(validatePhoneNumber(i164, country));\n  };\n  return <>\n            <h2>Basic</h2>\n            <PNI />\n            <h2>Labeled</h2>\n            <label htmlFor=\"pniID\">Phone number</label>\n            <br />\n            <PNI id=\"pniID\" />\n            <h2>Validation</h2>\n            <PNI onChange={handleValidation}>\n                <pre>valid: {valid.toString()}</pre>\n            </PNI>\n            <h2>Custom renders</h2>\n            <PNI arrowRender={() => '\uD83D\uDD3B'} itemRender={({\n      name,\n      code\n    }) => `+${code}:${name}`} selectedRender={({\n      alpha2,\n      code\n    }) => `+${code}:${alpha2}`} />\n        </>;\n}",
      ...Examples.parameters?.docs?.source
    }
  }
};

/***/ })

}]);