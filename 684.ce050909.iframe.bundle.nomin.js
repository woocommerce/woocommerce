(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[684],{

/***/ "../../node_modules/.pnpm/emoji-flags@1.3.0/node_modules/emoji-flags/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var data = __webpack_require__("../../node_modules/.pnpm/emoji-flags@1.3.0/node_modules/emoji-flags/data.json");
var find = __webpack_require__("../../node_modules/.pnpm/lodash.find@3.2.1/node_modules/lodash.find/index.js");

var methods = {
  countryCode: function(countryCode) {
    if (!countryCode) {
      throw new Error('Expected 1 country code as the first argument');
    }

    return find(data, function(country) {
      return country.code === countryCode.toUpperCase();
    });
  },

  get data() {
    return data;
  }
};

['emoji', 'code', 'name', 'unicode'].forEach(function(prop) {
  Object.defineProperty(methods, prop + 's', {
    get: function() {
      return data.map(function(country) {
        return country[prop];
      });
    }
  });
});

// TODO: figure out if this is a good idea
data.forEach(function(prop, index) {
  methods[prop.code] = data[index];
});

module.exports = methods;


/***/ }),

/***/ "../../node_modules/.pnpm/lodash._basecallback@3.3.1/node_modules/lodash._basecallback/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

/**
 * lodash 3.3.1 (Custom Build) <https://lodash.com/>
 * Build: `lodash modern modularize exports="npm" -o ./`
 * Copyright 2012-2015 The Dojo Foundation <http://dojofoundation.org/>
 * Based on Underscore.js 1.8.3 <http://underscorejs.org/LICENSE>
 * Copyright 2009-2015 Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
 * Available under MIT license <https://lodash.com/license>
 */
var baseIsEqual = __webpack_require__("../../node_modules/.pnpm/lodash._baseisequal@3.0.7/node_modules/lodash._baseisequal/index.js"),
    bindCallback = __webpack_require__("../../node_modules/.pnpm/lodash._bindcallback@3.0.1/node_modules/lodash._bindcallback/index.js"),
    isArray = __webpack_require__("../../node_modules/.pnpm/lodash.isarray@3.0.4/node_modules/lodash.isarray/index.js"),
    pairs = __webpack_require__("../../node_modules/.pnpm/lodash.pairs@3.0.1/node_modules/lodash.pairs/index.js");

/** Used to match property names within property paths. */
var reIsDeepProp = /\.|\[(?:[^[\]]*|(["'])(?:(?!\1)[^\n\\]|\\.)*?\1)\]/,
    reIsPlainProp = /^\w*$/,
    rePropName = /[^.[\]]+|\[(?:(-?\d+(?:\.\d+)?)|(["'])((?:(?!\2)[^\n\\]|\\.)*?)\2)\]/g;

/** Used to match backslashes in property paths. */
var reEscapeChar = /\\(\\)?/g;

/**
 * Converts `value` to a string if it's not one. An empty string is returned
 * for `null` or `undefined` values.
 *
 * @private
 * @param {*} value The value to process.
 * @returns {string} Returns the string.
 */
function baseToString(value) {
  return value == null ? '' : (value + '');
}

/**
 * The base implementation of `_.callback` which supports specifying the
 * number of arguments to provide to `func`.
 *
 * @private
 * @param {*} [func=_.identity] The value to convert to a callback.
 * @param {*} [thisArg] The `this` binding of `func`.
 * @param {number} [argCount] The number of arguments to provide to `func`.
 * @returns {Function} Returns the callback.
 */
function baseCallback(func, thisArg, argCount) {
  var type = typeof func;
  if (type == 'function') {
    return thisArg === undefined
      ? func
      : bindCallback(func, thisArg, argCount);
  }
  if (func == null) {
    return identity;
  }
  if (type == 'object') {
    return baseMatches(func);
  }
  return thisArg === undefined
    ? property(func)
    : baseMatchesProperty(func, thisArg);
}

/**
 * The base implementation of `get` without support for string paths
 * and default values.
 *
 * @private
 * @param {Object} object The object to query.
 * @param {Array} path The path of the property to get.
 * @param {string} [pathKey] The key representation of path.
 * @returns {*} Returns the resolved value.
 */
function baseGet(object, path, pathKey) {
  if (object == null) {
    return;
  }
  if (pathKey !== undefined && pathKey in toObject(object)) {
    path = [pathKey];
  }
  var index = 0,
      length = path.length;

  while (object != null && index < length) {
    object = object[path[index++]];
  }
  return (index && index == length) ? object : undefined;
}

/**
 * The base implementation of `_.isMatch` without support for callback
 * shorthands and `this` binding.
 *
 * @private
 * @param {Object} object The object to inspect.
 * @param {Array} matchData The propery names, values, and compare flags to match.
 * @param {Function} [customizer] The function to customize comparing objects.
 * @returns {boolean} Returns `true` if `object` is a match, else `false`.
 */
function baseIsMatch(object, matchData, customizer) {
  var index = matchData.length,
      length = index,
      noCustomizer = !customizer;

  if (object == null) {
    return !length;
  }
  object = toObject(object);
  while (index--) {
    var data = matchData[index];
    if ((noCustomizer && data[2])
          ? data[1] !== object[data[0]]
          : !(data[0] in object)
        ) {
      return false;
    }
  }
  while (++index < length) {
    data = matchData[index];
    var key = data[0],
        objValue = object[key],
        srcValue = data[1];

    if (noCustomizer && data[2]) {
      if (objValue === undefined && !(key in object)) {
        return false;
      }
    } else {
      var result = customizer ? customizer(objValue, srcValue, key) : undefined;
      if (!(result === undefined ? baseIsEqual(srcValue, objValue, customizer, true) : result)) {
        return false;
      }
    }
  }
  return true;
}

/**
 * The base implementation of `_.matches` which does not clone `source`.
 *
 * @private
 * @param {Object} source The object of property values to match.
 * @returns {Function} Returns the new function.
 */
function baseMatches(source) {
  var matchData = getMatchData(source);
  if (matchData.length == 1 && matchData[0][2]) {
    var key = matchData[0][0],
        value = matchData[0][1];

    return function(object) {
      if (object == null) {
        return false;
      }
      return object[key] === value && (value !== undefined || (key in toObject(object)));
    };
  }
  return function(object) {
    return baseIsMatch(object, matchData);
  };
}

/**
 * The base implementation of `_.matchesProperty` which does not clone `srcValue`.
 *
 * @private
 * @param {string} path The path of the property to get.
 * @param {*} srcValue The value to compare.
 * @returns {Function} Returns the new function.
 */
function baseMatchesProperty(path, srcValue) {
  var isArr = isArray(path),
      isCommon = isKey(path) && isStrictComparable(srcValue),
      pathKey = (path + '');

  path = toPath(path);
  return function(object) {
    if (object == null) {
      return false;
    }
    var key = pathKey;
    object = toObject(object);
    if ((isArr || !isCommon) && !(key in object)) {
      object = path.length == 1 ? object : baseGet(object, baseSlice(path, 0, -1));
      if (object == null) {
        return false;
      }
      key = last(path);
      object = toObject(object);
    }
    return object[key] === srcValue
      ? (srcValue !== undefined || (key in object))
      : baseIsEqual(srcValue, object[key], undefined, true);
  };
}

/**
 * The base implementation of `_.property` without support for deep paths.
 *
 * @private
 * @param {string} key The key of the property to get.
 * @returns {Function} Returns the new function.
 */
function baseProperty(key) {
  return function(object) {
    return object == null ? undefined : object[key];
  };
}

/**
 * A specialized version of `baseProperty` which supports deep paths.
 *
 * @private
 * @param {Array|string} path The path of the property to get.
 * @returns {Function} Returns the new function.
 */
function basePropertyDeep(path) {
  var pathKey = (path + '');
  path = toPath(path);
  return function(object) {
    return baseGet(object, path, pathKey);
  };
}

/**
 * The base implementation of `_.slice` without an iteratee call guard.
 *
 * @private
 * @param {Array} array The array to slice.
 * @param {number} [start=0] The start position.
 * @param {number} [end=array.length] The end position.
 * @returns {Array} Returns the slice of `array`.
 */
function baseSlice(array, start, end) {
  var index = -1,
      length = array.length;

  start = start == null ? 0 : (+start || 0);
  if (start < 0) {
    start = -start > length ? 0 : (length + start);
  }
  end = (end === undefined || end > length) ? length : (+end || 0);
  if (end < 0) {
    end += length;
  }
  length = start > end ? 0 : ((end - start) >>> 0);
  start >>>= 0;

  var result = Array(length);
  while (++index < length) {
    result[index] = array[index + start];
  }
  return result;
}

/**
 * Gets the propery names, values, and compare flags of `object`.
 *
 * @private
 * @param {Object} object The object to query.
 * @returns {Array} Returns the match data of `object`.
 */
function getMatchData(object) {
  var result = pairs(object),
      length = result.length;

  while (length--) {
    result[length][2] = isStrictComparable(result[length][1]);
  }
  return result;
}

/**
 * Checks if `value` is a property name and not a property path.
 *
 * @private
 * @param {*} value The value to check.
 * @param {Object} [object] The object to query keys on.
 * @returns {boolean} Returns `true` if `value` is a property name, else `false`.
 */
function isKey(value, object) {
  var type = typeof value;
  if ((type == 'string' && reIsPlainProp.test(value)) || type == 'number') {
    return true;
  }
  if (isArray(value)) {
    return false;
  }
  var result = !reIsDeepProp.test(value);
  return result || (object != null && value in toObject(object));
}

/**
 * Checks if `value` is suitable for strict equality comparisons, i.e. `===`.
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` if suitable for strict
 *  equality comparisons, else `false`.
 */
function isStrictComparable(value) {
  return value === value && !isObject(value);
}

/**
 * Converts `value` to an object if it's not one.
 *
 * @private
 * @param {*} value The value to process.
 * @returns {Object} Returns the object.
 */
function toObject(value) {
  return isObject(value) ? value : Object(value);
}

/**
 * Converts `value` to property path array if it's not one.
 *
 * @private
 * @param {*} value The value to process.
 * @returns {Array} Returns the property path array.
 */
function toPath(value) {
  if (isArray(value)) {
    return value;
  }
  var result = [];
  baseToString(value).replace(rePropName, function(match, number, quote, string) {
    result.push(quote ? string.replace(reEscapeChar, '$1') : (number || match));
  });
  return result;
}

/**
 * Gets the last element of `array`.
 *
 * @static
 * @memberOf _
 * @category Array
 * @param {Array} array The array to query.
 * @returns {*} Returns the last element of `array`.
 * @example
 *
 * _.last([1, 2, 3]);
 * // => 3
 */
function last(array) {
  var length = array ? array.length : 0;
  return length ? array[length - 1] : undefined;
}

/**
 * Checks if `value` is the [language type](https://es5.github.io/#x8) of `Object`.
 * (e.g. arrays, functions, objects, regexes, `new Number(0)`, and `new String('')`)
 *
 * @static
 * @memberOf _
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an object, else `false`.
 * @example
 *
 * _.isObject({});
 * // => true
 *
 * _.isObject([1, 2, 3]);
 * // => true
 *
 * _.isObject(1);
 * // => false
 */
function isObject(value) {
  // Avoid a V8 JIT bug in Chrome 19-20.
  // See https://code.google.com/p/v8/issues/detail?id=2291 for more details.
  var type = typeof value;
  return !!value && (type == 'object' || type == 'function');
}

/**
 * This method returns the first argument provided to it.
 *
 * @static
 * @memberOf _
 * @category Utility
 * @param {*} value Any value.
 * @returns {*} Returns `value`.
 * @example
 *
 * var object = { 'user': 'fred' };
 *
 * _.identity(object) === object;
 * // => true
 */
function identity(value) {
  return value;
}

/**
 * Creates a function that returns the property value at `path` on a
 * given object.
 *
 * @static
 * @memberOf _
 * @category Utility
 * @param {Array|string} path The path of the property to get.
 * @returns {Function} Returns the new function.
 * @example
 *
 * var objects = [
 *   { 'a': { 'b': { 'c': 2 } } },
 *   { 'a': { 'b': { 'c': 1 } } }
 * ];
 *
 * _.map(objects, _.property('a.b.c'));
 * // => [2, 1]
 *
 * _.pluck(_.sortBy(objects, _.property(['a', 'b', 'c'])), 'a.b.c');
 * // => [1, 2]
 */
function property(path) {
  return isKey(path) ? baseProperty(path) : basePropertyDeep(path);
}

module.exports = baseCallback;


/***/ }),

/***/ "../../node_modules/.pnpm/lodash._baseeach@3.0.4/node_modules/lodash._baseeach/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

/**
 * lodash 3.0.4 (Custom Build) <https://lodash.com/>
 * Build: `lodash modern modularize exports="npm" -o ./`
 * Copyright 2012-2015 The Dojo Foundation <http://dojofoundation.org/>
 * Based on Underscore.js 1.8.3 <http://underscorejs.org/LICENSE>
 * Copyright 2009-2015 Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
 * Available under MIT license <https://lodash.com/license>
 */
var keys = __webpack_require__("../../node_modules/.pnpm/lodash.keys@3.1.2/node_modules/lodash.keys/index.js");

/**
 * Used as the [maximum length](https://people.mozilla.org/~jorendorff/es6-draft.html#sec-number.max_safe_integer)
 * of an array-like value.
 */
var MAX_SAFE_INTEGER = 9007199254740991;

/**
 * The base implementation of `_.forEach` without support for callback
 * shorthands and `this` binding.
 *
 * @private
 * @param {Array|Object|string} collection The collection to iterate over.
 * @param {Function} iteratee The function invoked per iteration.
 * @returns {Array|Object|string} Returns `collection`.
 */
var baseEach = createBaseEach(baseForOwn);

/**
 * The base implementation of `baseForIn` and `baseForOwn` which iterates
 * over `object` properties returned by `keysFunc` invoking `iteratee` for
 * each property. Iteratee functions may exit iteration early by explicitly
 * returning `false`.
 *
 * @private
 * @param {Object} object The object to iterate over.
 * @param {Function} iteratee The function invoked per iteration.
 * @param {Function} keysFunc The function to get the keys of `object`.
 * @returns {Object} Returns `object`.
 */
var baseFor = createBaseFor();

/**
 * The base implementation of `_.forOwn` without support for callback
 * shorthands and `this` binding.
 *
 * @private
 * @param {Object} object The object to iterate over.
 * @param {Function} iteratee The function invoked per iteration.
 * @returns {Object} Returns `object`.
 */
function baseForOwn(object, iteratee) {
  return baseFor(object, iteratee, keys);
}

/**
 * The base implementation of `_.property` without support for deep paths.
 *
 * @private
 * @param {string} key The key of the property to get.
 * @returns {Function} Returns the new function.
 */
function baseProperty(key) {
  return function(object) {
    return object == null ? undefined : object[key];
  };
}

/**
 * Creates a `baseEach` or `baseEachRight` function.
 *
 * @private
 * @param {Function} eachFunc The function to iterate over a collection.
 * @param {boolean} [fromRight] Specify iterating from right to left.
 * @returns {Function} Returns the new base function.
 */
function createBaseEach(eachFunc, fromRight) {
  return function(collection, iteratee) {
    var length = collection ? getLength(collection) : 0;
    if (!isLength(length)) {
      return eachFunc(collection, iteratee);
    }
    var index = fromRight ? length : -1,
        iterable = toObject(collection);

    while ((fromRight ? index-- : ++index < length)) {
      if (iteratee(iterable[index], index, iterable) === false) {
        break;
      }
    }
    return collection;
  };
}

/**
 * Creates a base function for `_.forIn` or `_.forInRight`.
 *
 * @private
 * @param {boolean} [fromRight] Specify iterating from right to left.
 * @returns {Function} Returns the new base function.
 */
function createBaseFor(fromRight) {
  return function(object, iteratee, keysFunc) {
    var iterable = toObject(object),
        props = keysFunc(object),
        length = props.length,
        index = fromRight ? length : -1;

    while ((fromRight ? index-- : ++index < length)) {
      var key = props[index];
      if (iteratee(iterable[key], key, iterable) === false) {
        break;
      }
    }
    return object;
  };
}

/**
 * Gets the "length" property value of `object`.
 *
 * **Note:** This function is used to avoid a [JIT bug](https://bugs.webkit.org/show_bug.cgi?id=142792)
 * that affects Safari on at least iOS 8.1-8.3 ARM64.
 *
 * @private
 * @param {Object} object The object to query.
 * @returns {*} Returns the "length" value.
 */
var getLength = baseProperty('length');

/**
 * Checks if `value` is a valid array-like length.
 *
 * **Note:** This function is based on [`ToLength`](https://people.mozilla.org/~jorendorff/es6-draft.html#sec-tolength).
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a valid length, else `false`.
 */
function isLength(value) {
  return typeof value == 'number' && value > -1 && value % 1 == 0 && value <= MAX_SAFE_INTEGER;
}

/**
 * Converts `value` to an object if it's not one.
 *
 * @private
 * @param {*} value The value to process.
 * @returns {Object} Returns the object.
 */
function toObject(value) {
  return isObject(value) ? value : Object(value);
}

/**
 * Checks if `value` is the [language type](https://es5.github.io/#x8) of `Object`.
 * (e.g. arrays, functions, objects, regexes, `new Number(0)`, and `new String('')`)
 *
 * @static
 * @memberOf _
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an object, else `false`.
 * @example
 *
 * _.isObject({});
 * // => true
 *
 * _.isObject([1, 2, 3]);
 * // => true
 *
 * _.isObject(1);
 * // => false
 */
function isObject(value) {
  // Avoid a V8 JIT bug in Chrome 19-20.
  // See https://code.google.com/p/v8/issues/detail?id=2291 for more details.
  var type = typeof value;
  return !!value && (type == 'object' || type == 'function');
}

module.exports = baseEach;


/***/ }),

/***/ "../../node_modules/.pnpm/lodash._basefind@3.0.0/node_modules/lodash._basefind/index.js":
/***/ ((module) => {

/**
 * lodash 3.0.0 (Custom Build) <https://lodash.com/>
 * Build: `lodash modern modularize exports="npm" -o ./`
 * Copyright 2012-2015 The Dojo Foundation <http://dojofoundation.org/>
 * Based on Underscore.js 1.7.0 <http://underscorejs.org/LICENSE>
 * Copyright 2009-2015 Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
 * Available under MIT license <https://lodash.com/license>
 */

/**
 * The base implementation of `_.find`, `_.findLast`, `_.findKey`, and `_.findLastKey`,
 * without support for callback shorthands and `this` binding, which iterates
 * over `collection` using the provided `eachFunc`.
 *
 * @private
 * @param {Array|Object|string} collection The collection to search.
 * @param {Function} predicate The function invoked per iteration.
 * @param {Function} eachFunc The function to iterate over `collection`.
 * @param {boolean} [retKey] Specify returning the key of the found element
 *  instead of the element itself.
 * @returns {*} Returns the found element or its key, else `undefined`.
 */
function baseFind(collection, predicate, eachFunc, retKey) {
  var result;
  eachFunc(collection, function(value, key, collection) {
    if (predicate(value, key, collection)) {
      result = retKey ? key : value;
      return false;
    }
  });
  return result;
}

module.exports = baseFind;


/***/ }),

/***/ "../../node_modules/.pnpm/lodash._basefindindex@3.6.0/node_modules/lodash._basefindindex/index.js":
/***/ ((module) => {

/**
 * lodash 3.6.0 (Custom Build) <https://lodash.com/>
 * Build: `lodash modern modularize exports="npm" -o ./`
 * Copyright 2012-2015 The Dojo Foundation <http://dojofoundation.org/>
 * Based on Underscore.js 1.8.2 <http://underscorejs.org/LICENSE>
 * Copyright 2009-2015 Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
 * Available under MIT license <https://lodash.com/license>
 */

/**
 * The base implementation of `_.findIndex` and `_.findLastIndex` without
 * support for callback shorthands and `this` binding.
 *
 * @private
 * @param {Array} array The array to search.
 * @param {Function} predicate The function invoked per iteration.
 * @param {boolean} [fromRight] Specify iterating from right to left.
 * @returns {number} Returns the index of the matched value, else `-1`.
 */
function baseFindIndex(array, predicate, fromRight) {
  var length = array.length,
      index = fromRight ? length : -1;

  while ((fromRight ? index-- : ++index < length)) {
    if (predicate(array[index], index, array)) {
      return index;
    }
  }
  return -1;
}

module.exports = baseFindIndex;


/***/ }),

/***/ "../../node_modules/.pnpm/lodash._baseisequal@3.0.7/node_modules/lodash._baseisequal/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

/**
 * lodash 3.0.7 (Custom Build) <https://lodash.com/>
 * Build: `lodash modern modularize exports="npm" -o ./`
 * Copyright 2012-2015 The Dojo Foundation <http://dojofoundation.org/>
 * Based on Underscore.js 1.8.3 <http://underscorejs.org/LICENSE>
 * Copyright 2009-2015 Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
 * Available under MIT license <https://lodash.com/license>
 */
var isArray = __webpack_require__("../../node_modules/.pnpm/lodash.isarray@3.0.4/node_modules/lodash.isarray/index.js"),
    isTypedArray = __webpack_require__("../../node_modules/.pnpm/lodash.istypedarray@3.0.6/node_modules/lodash.istypedarray/index.js"),
    keys = __webpack_require__("../../node_modules/.pnpm/lodash.keys@3.1.2/node_modules/lodash.keys/index.js");

/** `Object#toString` result references. */
var argsTag = '[object Arguments]',
    arrayTag = '[object Array]',
    boolTag = '[object Boolean]',
    dateTag = '[object Date]',
    errorTag = '[object Error]',
    numberTag = '[object Number]',
    objectTag = '[object Object]',
    regexpTag = '[object RegExp]',
    stringTag = '[object String]';

/**
 * Checks if `value` is object-like.
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is object-like, else `false`.
 */
function isObjectLike(value) {
  return !!value && typeof value == 'object';
}

/** Used for native method references. */
var objectProto = Object.prototype;

/** Used to check objects for own properties. */
var hasOwnProperty = objectProto.hasOwnProperty;

/**
 * Used to resolve the [`toStringTag`](https://people.mozilla.org/~jorendorff/es6-draft.html#sec-object.prototype.tostring)
 * of values.
 */
var objToString = objectProto.toString;

/**
 * A specialized version of `_.some` for arrays without support for callback
 * shorthands and `this` binding.
 *
 * @private
 * @param {Array} array The array to iterate over.
 * @param {Function} predicate The function invoked per iteration.
 * @returns {boolean} Returns `true` if any element passes the predicate check,
 *  else `false`.
 */
function arraySome(array, predicate) {
  var index = -1,
      length = array.length;

  while (++index < length) {
    if (predicate(array[index], index, array)) {
      return true;
    }
  }
  return false;
}

/**
 * The base implementation of `_.isEqual` without support for `this` binding
 * `customizer` functions.
 *
 * @private
 * @param {*} value The value to compare.
 * @param {*} other The other value to compare.
 * @param {Function} [customizer] The function to customize comparing values.
 * @param {boolean} [isLoose] Specify performing partial comparisons.
 * @param {Array} [stackA] Tracks traversed `value` objects.
 * @param {Array} [stackB] Tracks traversed `other` objects.
 * @returns {boolean} Returns `true` if the values are equivalent, else `false`.
 */
function baseIsEqual(value, other, customizer, isLoose, stackA, stackB) {
  if (value === other) {
    return true;
  }
  if (value == null || other == null || (!isObject(value) && !isObjectLike(other))) {
    return value !== value && other !== other;
  }
  return baseIsEqualDeep(value, other, baseIsEqual, customizer, isLoose, stackA, stackB);
}

/**
 * A specialized version of `baseIsEqual` for arrays and objects which performs
 * deep comparisons and tracks traversed objects enabling objects with circular
 * references to be compared.
 *
 * @private
 * @param {Object} object The object to compare.
 * @param {Object} other The other object to compare.
 * @param {Function} equalFunc The function to determine equivalents of values.
 * @param {Function} [customizer] The function to customize comparing objects.
 * @param {boolean} [isLoose] Specify performing partial comparisons.
 * @param {Array} [stackA=[]] Tracks traversed `value` objects.
 * @param {Array} [stackB=[]] Tracks traversed `other` objects.
 * @returns {boolean} Returns `true` if the objects are equivalent, else `false`.
 */
function baseIsEqualDeep(object, other, equalFunc, customizer, isLoose, stackA, stackB) {
  var objIsArr = isArray(object),
      othIsArr = isArray(other),
      objTag = arrayTag,
      othTag = arrayTag;

  if (!objIsArr) {
    objTag = objToString.call(object);
    if (objTag == argsTag) {
      objTag = objectTag;
    } else if (objTag != objectTag) {
      objIsArr = isTypedArray(object);
    }
  }
  if (!othIsArr) {
    othTag = objToString.call(other);
    if (othTag == argsTag) {
      othTag = objectTag;
    } else if (othTag != objectTag) {
      othIsArr = isTypedArray(other);
    }
  }
  var objIsObj = objTag == objectTag,
      othIsObj = othTag == objectTag,
      isSameTag = objTag == othTag;

  if (isSameTag && !(objIsArr || objIsObj)) {
    return equalByTag(object, other, objTag);
  }
  if (!isLoose) {
    var objIsWrapped = objIsObj && hasOwnProperty.call(object, '__wrapped__'),
        othIsWrapped = othIsObj && hasOwnProperty.call(other, '__wrapped__');

    if (objIsWrapped || othIsWrapped) {
      return equalFunc(objIsWrapped ? object.value() : object, othIsWrapped ? other.value() : other, customizer, isLoose, stackA, stackB);
    }
  }
  if (!isSameTag) {
    return false;
  }
  // Assume cyclic values are equal.
  // For more information on detecting circular references see https://es5.github.io/#JO.
  stackA || (stackA = []);
  stackB || (stackB = []);

  var length = stackA.length;
  while (length--) {
    if (stackA[length] == object) {
      return stackB[length] == other;
    }
  }
  // Add `object` and `other` to the stack of traversed objects.
  stackA.push(object);
  stackB.push(other);

  var result = (objIsArr ? equalArrays : equalObjects)(object, other, equalFunc, customizer, isLoose, stackA, stackB);

  stackA.pop();
  stackB.pop();

  return result;
}

/**
 * A specialized version of `baseIsEqualDeep` for arrays with support for
 * partial deep comparisons.
 *
 * @private
 * @param {Array} array The array to compare.
 * @param {Array} other The other array to compare.
 * @param {Function} equalFunc The function to determine equivalents of values.
 * @param {Function} [customizer] The function to customize comparing arrays.
 * @param {boolean} [isLoose] Specify performing partial comparisons.
 * @param {Array} [stackA] Tracks traversed `value` objects.
 * @param {Array} [stackB] Tracks traversed `other` objects.
 * @returns {boolean} Returns `true` if the arrays are equivalent, else `false`.
 */
function equalArrays(array, other, equalFunc, customizer, isLoose, stackA, stackB) {
  var index = -1,
      arrLength = array.length,
      othLength = other.length;

  if (arrLength != othLength && !(isLoose && othLength > arrLength)) {
    return false;
  }
  // Ignore non-index properties.
  while (++index < arrLength) {
    var arrValue = array[index],
        othValue = other[index],
        result = customizer ? customizer(isLoose ? othValue : arrValue, isLoose ? arrValue : othValue, index) : undefined;

    if (result !== undefined) {
      if (result) {
        continue;
      }
      return false;
    }
    // Recursively compare arrays (susceptible to call stack limits).
    if (isLoose) {
      if (!arraySome(other, function(othValue) {
            return arrValue === othValue || equalFunc(arrValue, othValue, customizer, isLoose, stackA, stackB);
          })) {
        return false;
      }
    } else if (!(arrValue === othValue || equalFunc(arrValue, othValue, customizer, isLoose, stackA, stackB))) {
      return false;
    }
  }
  return true;
}

/**
 * A specialized version of `baseIsEqualDeep` for comparing objects of
 * the same `toStringTag`.
 *
 * **Note:** This function only supports comparing values with tags of
 * `Boolean`, `Date`, `Error`, `Number`, `RegExp`, or `String`.
 *
 * @private
 * @param {Object} value The object to compare.
 * @param {Object} other The other object to compare.
 * @param {string} tag The `toStringTag` of the objects to compare.
 * @returns {boolean} Returns `true` if the objects are equivalent, else `false`.
 */
function equalByTag(object, other, tag) {
  switch (tag) {
    case boolTag:
    case dateTag:
      // Coerce dates and booleans to numbers, dates to milliseconds and booleans
      // to `1` or `0` treating invalid dates coerced to `NaN` as not equal.
      return +object == +other;

    case errorTag:
      return object.name == other.name && object.message == other.message;

    case numberTag:
      // Treat `NaN` vs. `NaN` as equal.
      return (object != +object)
        ? other != +other
        : object == +other;

    case regexpTag:
    case stringTag:
      // Coerce regexes to strings and treat strings primitives and string
      // objects as equal. See https://es5.github.io/#x15.10.6.4 for more details.
      return object == (other + '');
  }
  return false;
}

/**
 * A specialized version of `baseIsEqualDeep` for objects with support for
 * partial deep comparisons.
 *
 * @private
 * @param {Object} object The object to compare.
 * @param {Object} other The other object to compare.
 * @param {Function} equalFunc The function to determine equivalents of values.
 * @param {Function} [customizer] The function to customize comparing values.
 * @param {boolean} [isLoose] Specify performing partial comparisons.
 * @param {Array} [stackA] Tracks traversed `value` objects.
 * @param {Array} [stackB] Tracks traversed `other` objects.
 * @returns {boolean} Returns `true` if the objects are equivalent, else `false`.
 */
function equalObjects(object, other, equalFunc, customizer, isLoose, stackA, stackB) {
  var objProps = keys(object),
      objLength = objProps.length,
      othProps = keys(other),
      othLength = othProps.length;

  if (objLength != othLength && !isLoose) {
    return false;
  }
  var index = objLength;
  while (index--) {
    var key = objProps[index];
    if (!(isLoose ? key in other : hasOwnProperty.call(other, key))) {
      return false;
    }
  }
  var skipCtor = isLoose;
  while (++index < objLength) {
    key = objProps[index];
    var objValue = object[key],
        othValue = other[key],
        result = customizer ? customizer(isLoose ? othValue : objValue, isLoose? objValue : othValue, key) : undefined;

    // Recursively compare objects (susceptible to call stack limits).
    if (!(result === undefined ? equalFunc(objValue, othValue, customizer, isLoose, stackA, stackB) : result)) {
      return false;
    }
    skipCtor || (skipCtor = key == 'constructor');
  }
  if (!skipCtor) {
    var objCtor = object.constructor,
        othCtor = other.constructor;

    // Non `Object` object instances with different constructors are not equal.
    if (objCtor != othCtor &&
        ('constructor' in object && 'constructor' in other) &&
        !(typeof objCtor == 'function' && objCtor instanceof objCtor &&
          typeof othCtor == 'function' && othCtor instanceof othCtor)) {
      return false;
    }
  }
  return true;
}

/**
 * Checks if `value` is the [language type](https://es5.github.io/#x8) of `Object`.
 * (e.g. arrays, functions, objects, regexes, `new Number(0)`, and `new String('')`)
 *
 * @static
 * @memberOf _
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an object, else `false`.
 * @example
 *
 * _.isObject({});
 * // => true
 *
 * _.isObject([1, 2, 3]);
 * // => true
 *
 * _.isObject(1);
 * // => false
 */
function isObject(value) {
  // Avoid a V8 JIT bug in Chrome 19-20.
  // See https://code.google.com/p/v8/issues/detail?id=2291 for more details.
  var type = typeof value;
  return !!value && (type == 'object' || type == 'function');
}

module.exports = baseIsEqual;


/***/ }),

/***/ "../../node_modules/.pnpm/lodash._bindcallback@3.0.1/node_modules/lodash._bindcallback/index.js":
/***/ ((module) => {

/**
 * lodash 3.0.1 (Custom Build) <https://lodash.com/>
 * Build: `lodash modern modularize exports="npm" -o ./`
 * Copyright 2012-2015 The Dojo Foundation <http://dojofoundation.org/>
 * Based on Underscore.js 1.8.3 <http://underscorejs.org/LICENSE>
 * Copyright 2009-2015 Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
 * Available under MIT license <https://lodash.com/license>
 */

/**
 * A specialized version of `baseCallback` which only supports `this` binding
 * and specifying the number of arguments to provide to `func`.
 *
 * @private
 * @param {Function} func The function to bind.
 * @param {*} thisArg The `this` binding of `func`.
 * @param {number} [argCount] The number of arguments to provide to `func`.
 * @returns {Function} Returns the callback.
 */
function bindCallback(func, thisArg, argCount) {
  if (typeof func != 'function') {
    return identity;
  }
  if (thisArg === undefined) {
    return func;
  }
  switch (argCount) {
    case 1: return function(value) {
      return func.call(thisArg, value);
    };
    case 3: return function(value, index, collection) {
      return func.call(thisArg, value, index, collection);
    };
    case 4: return function(accumulator, value, index, collection) {
      return func.call(thisArg, accumulator, value, index, collection);
    };
    case 5: return function(value, other, key, object, source) {
      return func.call(thisArg, value, other, key, object, source);
    };
  }
  return function() {
    return func.apply(thisArg, arguments);
  };
}

/**
 * This method returns the first argument provided to it.
 *
 * @static
 * @memberOf _
 * @category Utility
 * @param {*} value Any value.
 * @returns {*} Returns `value`.
 * @example
 *
 * var object = { 'user': 'fred' };
 *
 * _.identity(object) === object;
 * // => true
 */
function identity(value) {
  return value;
}

module.exports = bindCallback;


/***/ }),

/***/ "../../node_modules/.pnpm/lodash._getnative@3.9.1/node_modules/lodash._getnative/index.js":
/***/ ((module) => {

/**
 * lodash 3.9.1 (Custom Build) <https://lodash.com/>
 * Build: `lodash modern modularize exports="npm" -o ./`
 * Copyright 2012-2015 The Dojo Foundation <http://dojofoundation.org/>
 * Based on Underscore.js 1.8.3 <http://underscorejs.org/LICENSE>
 * Copyright 2009-2015 Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
 * Available under MIT license <https://lodash.com/license>
 */

/** `Object#toString` result references. */
var funcTag = '[object Function]';

/** Used to detect host constructors (Safari > 5). */
var reIsHostCtor = /^\[object .+?Constructor\]$/;

/**
 * Checks if `value` is object-like.
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is object-like, else `false`.
 */
function isObjectLike(value) {
  return !!value && typeof value == 'object';
}

/** Used for native method references. */
var objectProto = Object.prototype;

/** Used to resolve the decompiled source of functions. */
var fnToString = Function.prototype.toString;

/** Used to check objects for own properties. */
var hasOwnProperty = objectProto.hasOwnProperty;

/**
 * Used to resolve the [`toStringTag`](http://ecma-international.org/ecma-262/6.0/#sec-object.prototype.tostring)
 * of values.
 */
var objToString = objectProto.toString;

/** Used to detect if a method is native. */
var reIsNative = RegExp('^' +
  fnToString.call(hasOwnProperty).replace(/[\\^$.*+?()[\]{}|]/g, '\\$&')
  .replace(/hasOwnProperty|(function).*?(?=\\\()| for .+?(?=\\\])/g, '$1.*?') + '$'
);

/**
 * Gets the native function at `key` of `object`.
 *
 * @private
 * @param {Object} object The object to query.
 * @param {string} key The key of the method to get.
 * @returns {*} Returns the function if it's native, else `undefined`.
 */
function getNative(object, key) {
  var value = object == null ? undefined : object[key];
  return isNative(value) ? value : undefined;
}

/**
 * Checks if `value` is classified as a `Function` object.
 *
 * @static
 * @memberOf _
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is correctly classified, else `false`.
 * @example
 *
 * _.isFunction(_);
 * // => true
 *
 * _.isFunction(/abc/);
 * // => false
 */
function isFunction(value) {
  // The use of `Object#toString` avoids issues with the `typeof` operator
  // in older versions of Chrome and Safari which return 'function' for regexes
  // and Safari 8 equivalents which return 'object' for typed array constructors.
  return isObject(value) && objToString.call(value) == funcTag;
}

/**
 * Checks if `value` is the [language type](https://es5.github.io/#x8) of `Object`.
 * (e.g. arrays, functions, objects, regexes, `new Number(0)`, and `new String('')`)
 *
 * @static
 * @memberOf _
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an object, else `false`.
 * @example
 *
 * _.isObject({});
 * // => true
 *
 * _.isObject([1, 2, 3]);
 * // => true
 *
 * _.isObject(1);
 * // => false
 */
function isObject(value) {
  // Avoid a V8 JIT bug in Chrome 19-20.
  // See https://code.google.com/p/v8/issues/detail?id=2291 for more details.
  var type = typeof value;
  return !!value && (type == 'object' || type == 'function');
}

/**
 * Checks if `value` is a native function.
 *
 * @static
 * @memberOf _
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a native function, else `false`.
 * @example
 *
 * _.isNative(Array.prototype.push);
 * // => true
 *
 * _.isNative(_);
 * // => false
 */
function isNative(value) {
  if (value == null) {
    return false;
  }
  if (isFunction(value)) {
    return reIsNative.test(fnToString.call(value));
  }
  return isObjectLike(value) && reIsHostCtor.test(value);
}

module.exports = getNative;


/***/ }),

/***/ "../../node_modules/.pnpm/lodash.find@3.2.1/node_modules/lodash.find/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

/**
 * lodash 3.2.1 (Custom Build) <https://lodash.com/>
 * Build: `lodash modern modularize exports="npm" -o ./`
 * Copyright 2012-2015 The Dojo Foundation <http://dojofoundation.org/>
 * Based on Underscore.js 1.8.3 <http://underscorejs.org/LICENSE>
 * Copyright 2009-2015 Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
 * Available under MIT license <https://lodash.com/license>
 */
var baseCallback = __webpack_require__("../../node_modules/.pnpm/lodash._basecallback@3.3.1/node_modules/lodash._basecallback/index.js"),
    baseEach = __webpack_require__("../../node_modules/.pnpm/lodash._baseeach@3.0.4/node_modules/lodash._baseeach/index.js"),
    baseFind = __webpack_require__("../../node_modules/.pnpm/lodash._basefind@3.0.0/node_modules/lodash._basefind/index.js"),
    baseFindIndex = __webpack_require__("../../node_modules/.pnpm/lodash._basefindindex@3.6.0/node_modules/lodash._basefindindex/index.js"),
    isArray = __webpack_require__("../../node_modules/.pnpm/lodash.isarray@3.0.4/node_modules/lodash.isarray/index.js");

/**
 * Creates a `_.find` or `_.findLast` function.
 *
 * @private
 * @param {Function} eachFunc The function to iterate over a collection.
 * @param {boolean} [fromRight] Specify iterating from right to left.
 * @returns {Function} Returns the new find function.
 */
function createFind(eachFunc, fromRight) {
  return function(collection, predicate, thisArg) {
    predicate = baseCallback(predicate, thisArg, 3);
    if (isArray(collection)) {
      var index = baseFindIndex(collection, predicate, fromRight);
      return index > -1 ? collection[index] : undefined;
    }
    return baseFind(collection, predicate, eachFunc);
  };
}

/**
 * Iterates over elements of `collection`, returning the first element
 * `predicate` returns truthy for. The predicate is bound to `thisArg` and
 * invoked with three arguments: (value, index|key, collection).
 *
 * If a property name is provided for `predicate` the created `_.property`
 * style callback returns the property value of the given element.
 *
 * If a value is also provided for `thisArg` the created `_.matchesProperty`
 * style callback returns `true` for elements that have a matching property
 * value, else `false`.
 *
 * If an object is provided for `predicate` the created `_.matches` style
 * callback returns `true` for elements that have the properties of the given
 * object, else `false`.
 *
 * @static
 * @memberOf _
 * @alias detect
 * @category Collection
 * @param {Array|Object|string} collection The collection to search.
 * @param {Function|Object|string} [predicate=_.identity] The function invoked
 *  per iteration.
 * @param {*} [thisArg] The `this` binding of `predicate`.
 * @returns {*} Returns the matched element, else `undefined`.
 * @example
 *
 * var users = [
 *   { 'user': 'barney',  'age': 36, 'active': true },
 *   { 'user': 'fred',    'age': 40, 'active': false },
 *   { 'user': 'pebbles', 'age': 1,  'active': true }
 * ];
 *
 * _.result(_.find(users, function(chr) {
 *   return chr.age < 40;
 * }), 'user');
 * // => 'barney'
 *
 * // using the `_.matches` callback shorthand
 * _.result(_.find(users, { 'age': 1, 'active': true }), 'user');
 * // => 'pebbles'
 *
 * // using the `_.matchesProperty` callback shorthand
 * _.result(_.find(users, 'active', false), 'user');
 * // => 'fred'
 *
 * // using the `_.property` callback shorthand
 * _.result(_.find(users, 'active'), 'user');
 * // => 'barney'
 */
var find = createFind(baseEach);

module.exports = find;


/***/ }),

/***/ "../../node_modules/.pnpm/lodash.isarguments@3.1.0/node_modules/lodash.isarguments/index.js":
/***/ ((module) => {

/**
 * lodash (Custom Build) <https://lodash.com/>
 * Build: `lodash modularize exports="npm" -o ./`
 * Copyright jQuery Foundation and other contributors <https://jquery.org/>
 * Released under MIT license <https://lodash.com/license>
 * Based on Underscore.js 1.8.3 <http://underscorejs.org/LICENSE>
 * Copyright Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
 */

/** Used as references for various `Number` constants. */
var MAX_SAFE_INTEGER = 9007199254740991;

/** `Object#toString` result references. */
var argsTag = '[object Arguments]',
    funcTag = '[object Function]',
    genTag = '[object GeneratorFunction]';

/** Used for built-in method references. */
var objectProto = Object.prototype;

/** Used to check objects for own properties. */
var hasOwnProperty = objectProto.hasOwnProperty;

/**
 * Used to resolve the
 * [`toStringTag`](http://ecma-international.org/ecma-262/7.0/#sec-object.prototype.tostring)
 * of values.
 */
var objectToString = objectProto.toString;

/** Built-in value references. */
var propertyIsEnumerable = objectProto.propertyIsEnumerable;

/**
 * Checks if `value` is likely an `arguments` object.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an `arguments` object,
 *  else `false`.
 * @example
 *
 * _.isArguments(function() { return arguments; }());
 * // => true
 *
 * _.isArguments([1, 2, 3]);
 * // => false
 */
function isArguments(value) {
  // Safari 8.1 makes `arguments.callee` enumerable in strict mode.
  return isArrayLikeObject(value) && hasOwnProperty.call(value, 'callee') &&
    (!propertyIsEnumerable.call(value, 'callee') || objectToString.call(value) == argsTag);
}

/**
 * Checks if `value` is array-like. A value is considered array-like if it's
 * not a function and has a `value.length` that's an integer greater than or
 * equal to `0` and less than or equal to `Number.MAX_SAFE_INTEGER`.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is array-like, else `false`.
 * @example
 *
 * _.isArrayLike([1, 2, 3]);
 * // => true
 *
 * _.isArrayLike(document.body.children);
 * // => true
 *
 * _.isArrayLike('abc');
 * // => true
 *
 * _.isArrayLike(_.noop);
 * // => false
 */
function isArrayLike(value) {
  return value != null && isLength(value.length) && !isFunction(value);
}

/**
 * This method is like `_.isArrayLike` except that it also checks if `value`
 * is an object.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an array-like object,
 *  else `false`.
 * @example
 *
 * _.isArrayLikeObject([1, 2, 3]);
 * // => true
 *
 * _.isArrayLikeObject(document.body.children);
 * // => true
 *
 * _.isArrayLikeObject('abc');
 * // => false
 *
 * _.isArrayLikeObject(_.noop);
 * // => false
 */
function isArrayLikeObject(value) {
  return isObjectLike(value) && isArrayLike(value);
}

/**
 * Checks if `value` is classified as a `Function` object.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a function, else `false`.
 * @example
 *
 * _.isFunction(_);
 * // => true
 *
 * _.isFunction(/abc/);
 * // => false
 */
function isFunction(value) {
  // The use of `Object#toString` avoids issues with the `typeof` operator
  // in Safari 8-9 which returns 'object' for typed array and other constructors.
  var tag = isObject(value) ? objectToString.call(value) : '';
  return tag == funcTag || tag == genTag;
}

/**
 * Checks if `value` is a valid array-like length.
 *
 * **Note:** This method is loosely based on
 * [`ToLength`](http://ecma-international.org/ecma-262/7.0/#sec-tolength).
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a valid length, else `false`.
 * @example
 *
 * _.isLength(3);
 * // => true
 *
 * _.isLength(Number.MIN_VALUE);
 * // => false
 *
 * _.isLength(Infinity);
 * // => false
 *
 * _.isLength('3');
 * // => false
 */
function isLength(value) {
  return typeof value == 'number' &&
    value > -1 && value % 1 == 0 && value <= MAX_SAFE_INTEGER;
}

/**
 * Checks if `value` is the
 * [language type](http://www.ecma-international.org/ecma-262/7.0/#sec-ecmascript-language-types)
 * of `Object`. (e.g. arrays, functions, objects, regexes, `new Number(0)`, and `new String('')`)
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an object, else `false`.
 * @example
 *
 * _.isObject({});
 * // => true
 *
 * _.isObject([1, 2, 3]);
 * // => true
 *
 * _.isObject(_.noop);
 * // => true
 *
 * _.isObject(null);
 * // => false
 */
function isObject(value) {
  var type = typeof value;
  return !!value && (type == 'object' || type == 'function');
}

/**
 * Checks if `value` is object-like. A value is object-like if it's not `null`
 * and has a `typeof` result of "object".
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is object-like, else `false`.
 * @example
 *
 * _.isObjectLike({});
 * // => true
 *
 * _.isObjectLike([1, 2, 3]);
 * // => true
 *
 * _.isObjectLike(_.noop);
 * // => false
 *
 * _.isObjectLike(null);
 * // => false
 */
function isObjectLike(value) {
  return !!value && typeof value == 'object';
}

module.exports = isArguments;


/***/ }),

/***/ "../../node_modules/.pnpm/lodash.isarray@3.0.4/node_modules/lodash.isarray/index.js":
/***/ ((module) => {

/**
 * lodash 3.0.4 (Custom Build) <https://lodash.com/>
 * Build: `lodash modern modularize exports="npm" -o ./`
 * Copyright 2012-2015 The Dojo Foundation <http://dojofoundation.org/>
 * Based on Underscore.js 1.8.3 <http://underscorejs.org/LICENSE>
 * Copyright 2009-2015 Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
 * Available under MIT license <https://lodash.com/license>
 */

/** `Object#toString` result references. */
var arrayTag = '[object Array]',
    funcTag = '[object Function]';

/** Used to detect host constructors (Safari > 5). */
var reIsHostCtor = /^\[object .+?Constructor\]$/;

/**
 * Checks if `value` is object-like.
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is object-like, else `false`.
 */
function isObjectLike(value) {
  return !!value && typeof value == 'object';
}

/** Used for native method references. */
var objectProto = Object.prototype;

/** Used to resolve the decompiled source of functions. */
var fnToString = Function.prototype.toString;

/** Used to check objects for own properties. */
var hasOwnProperty = objectProto.hasOwnProperty;

/**
 * Used to resolve the [`toStringTag`](http://ecma-international.org/ecma-262/6.0/#sec-object.prototype.tostring)
 * of values.
 */
var objToString = objectProto.toString;

/** Used to detect if a method is native. */
var reIsNative = RegExp('^' +
  fnToString.call(hasOwnProperty).replace(/[\\^$.*+?()[\]{}|]/g, '\\$&')
  .replace(/hasOwnProperty|(function).*?(?=\\\()| for .+?(?=\\\])/g, '$1.*?') + '$'
);

/* Native method references for those with the same name as other `lodash` methods. */
var nativeIsArray = getNative(Array, 'isArray');

/**
 * Used as the [maximum length](http://ecma-international.org/ecma-262/6.0/#sec-number.max_safe_integer)
 * of an array-like value.
 */
var MAX_SAFE_INTEGER = 9007199254740991;

/**
 * Gets the native function at `key` of `object`.
 *
 * @private
 * @param {Object} object The object to query.
 * @param {string} key The key of the method to get.
 * @returns {*} Returns the function if it's native, else `undefined`.
 */
function getNative(object, key) {
  var value = object == null ? undefined : object[key];
  return isNative(value) ? value : undefined;
}

/**
 * Checks if `value` is a valid array-like length.
 *
 * **Note:** This function is based on [`ToLength`](http://ecma-international.org/ecma-262/6.0/#sec-tolength).
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a valid length, else `false`.
 */
function isLength(value) {
  return typeof value == 'number' && value > -1 && value % 1 == 0 && value <= MAX_SAFE_INTEGER;
}

/**
 * Checks if `value` is classified as an `Array` object.
 *
 * @static
 * @memberOf _
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is correctly classified, else `false`.
 * @example
 *
 * _.isArray([1, 2, 3]);
 * // => true
 *
 * _.isArray(function() { return arguments; }());
 * // => false
 */
var isArray = nativeIsArray || function(value) {
  return isObjectLike(value) && isLength(value.length) && objToString.call(value) == arrayTag;
};

/**
 * Checks if `value` is classified as a `Function` object.
 *
 * @static
 * @memberOf _
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is correctly classified, else `false`.
 * @example
 *
 * _.isFunction(_);
 * // => true
 *
 * _.isFunction(/abc/);
 * // => false
 */
function isFunction(value) {
  // The use of `Object#toString` avoids issues with the `typeof` operator
  // in older versions of Chrome and Safari which return 'function' for regexes
  // and Safari 8 equivalents which return 'object' for typed array constructors.
  return isObject(value) && objToString.call(value) == funcTag;
}

/**
 * Checks if `value` is the [language type](https://es5.github.io/#x8) of `Object`.
 * (e.g. arrays, functions, objects, regexes, `new Number(0)`, and `new String('')`)
 *
 * @static
 * @memberOf _
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an object, else `false`.
 * @example
 *
 * _.isObject({});
 * // => true
 *
 * _.isObject([1, 2, 3]);
 * // => true
 *
 * _.isObject(1);
 * // => false
 */
function isObject(value) {
  // Avoid a V8 JIT bug in Chrome 19-20.
  // See https://code.google.com/p/v8/issues/detail?id=2291 for more details.
  var type = typeof value;
  return !!value && (type == 'object' || type == 'function');
}

/**
 * Checks if `value` is a native function.
 *
 * @static
 * @memberOf _
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a native function, else `false`.
 * @example
 *
 * _.isNative(Array.prototype.push);
 * // => true
 *
 * _.isNative(_);
 * // => false
 */
function isNative(value) {
  if (value == null) {
    return false;
  }
  if (isFunction(value)) {
    return reIsNative.test(fnToString.call(value));
  }
  return isObjectLike(value) && reIsHostCtor.test(value);
}

module.exports = isArray;


/***/ }),

/***/ "../../node_modules/.pnpm/lodash.istypedarray@3.0.6/node_modules/lodash.istypedarray/index.js":
/***/ ((module) => {

/**
 * lodash 3.0.6 (Custom Build) <https://lodash.com/>
 * Build: `lodash modularize exports="npm" -o ./`
 * Copyright jQuery Foundation and other contributors <https://jquery.org/>
 * Released under MIT license <https://lodash.com/license>
 * Based on Underscore.js 1.8.3 <http://underscorejs.org/LICENSE>
 * Copyright Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
 */

/** Used as references for various `Number` constants. */
var MAX_SAFE_INTEGER = 9007199254740991;

/** `Object#toString` result references. */
var argsTag = '[object Arguments]',
    arrayTag = '[object Array]',
    boolTag = '[object Boolean]',
    dateTag = '[object Date]',
    errorTag = '[object Error]',
    funcTag = '[object Function]',
    mapTag = '[object Map]',
    numberTag = '[object Number]',
    objectTag = '[object Object]',
    regexpTag = '[object RegExp]',
    setTag = '[object Set]',
    stringTag = '[object String]',
    weakMapTag = '[object WeakMap]';

var arrayBufferTag = '[object ArrayBuffer]',
    dataViewTag = '[object DataView]',
    float32Tag = '[object Float32Array]',
    float64Tag = '[object Float64Array]',
    int8Tag = '[object Int8Array]',
    int16Tag = '[object Int16Array]',
    int32Tag = '[object Int32Array]',
    uint8Tag = '[object Uint8Array]',
    uint8ClampedTag = '[object Uint8ClampedArray]',
    uint16Tag = '[object Uint16Array]',
    uint32Tag = '[object Uint32Array]';

/** Used to identify `toStringTag` values of typed arrays. */
var typedArrayTags = {};
typedArrayTags[float32Tag] = typedArrayTags[float64Tag] =
typedArrayTags[int8Tag] = typedArrayTags[int16Tag] =
typedArrayTags[int32Tag] = typedArrayTags[uint8Tag] =
typedArrayTags[uint8ClampedTag] = typedArrayTags[uint16Tag] =
typedArrayTags[uint32Tag] = true;
typedArrayTags[argsTag] = typedArrayTags[arrayTag] =
typedArrayTags[arrayBufferTag] = typedArrayTags[boolTag] =
typedArrayTags[dataViewTag] = typedArrayTags[dateTag] =
typedArrayTags[errorTag] = typedArrayTags[funcTag] =
typedArrayTags[mapTag] = typedArrayTags[numberTag] =
typedArrayTags[objectTag] = typedArrayTags[regexpTag] =
typedArrayTags[setTag] = typedArrayTags[stringTag] =
typedArrayTags[weakMapTag] = false;

/** Used for built-in method references. */
var objectProto = Object.prototype;

/**
 * Used to resolve the [`toStringTag`](http://ecma-international.org/ecma-262/6.0/#sec-object.prototype.tostring)
 * of values.
 */
var objectToString = objectProto.toString;

/**
 * Checks if `value` is a valid array-like length.
 *
 * **Note:** This function is loosely based on
 * [`ToLength`](http://ecma-international.org/ecma-262/6.0/#sec-tolength).
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a valid length,
 *  else `false`.
 * @example
 *
 * _.isLength(3);
 * // => true
 *
 * _.isLength(Number.MIN_VALUE);
 * // => false
 *
 * _.isLength(Infinity);
 * // => false
 *
 * _.isLength('3');
 * // => false
 */
function isLength(value) {
  return typeof value == 'number' &&
    value > -1 && value % 1 == 0 && value <= MAX_SAFE_INTEGER;
}

/**
 * Checks if `value` is object-like. A value is object-like if it's not `null`
 * and has a `typeof` result of "object".
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is object-like, else `false`.
 * @example
 *
 * _.isObjectLike({});
 * // => true
 *
 * _.isObjectLike([1, 2, 3]);
 * // => true
 *
 * _.isObjectLike(_.noop);
 * // => false
 *
 * _.isObjectLike(null);
 * // => false
 */
function isObjectLike(value) {
  return !!value && typeof value == 'object';
}

/**
 * Checks if `value` is classified as a typed array.
 *
 * @static
 * @memberOf _
 * @since 3.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is correctly classified,
 *  else `false`.
 * @example
 *
 * _.isTypedArray(new Uint8Array);
 * // => true
 *
 * _.isTypedArray([]);
 * // => false
 */
function isTypedArray(value) {
  return isObjectLike(value) &&
    isLength(value.length) && !!typedArrayTags[objectToString.call(value)];
}

module.exports = isTypedArray;


/***/ }),

/***/ "../../node_modules/.pnpm/lodash.keys@3.1.2/node_modules/lodash.keys/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

/**
 * lodash 3.1.2 (Custom Build) <https://lodash.com/>
 * Build: `lodash modern modularize exports="npm" -o ./`
 * Copyright 2012-2015 The Dojo Foundation <http://dojofoundation.org/>
 * Based on Underscore.js 1.8.3 <http://underscorejs.org/LICENSE>
 * Copyright 2009-2015 Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
 * Available under MIT license <https://lodash.com/license>
 */
var getNative = __webpack_require__("../../node_modules/.pnpm/lodash._getnative@3.9.1/node_modules/lodash._getnative/index.js"),
    isArguments = __webpack_require__("../../node_modules/.pnpm/lodash.isarguments@3.1.0/node_modules/lodash.isarguments/index.js"),
    isArray = __webpack_require__("../../node_modules/.pnpm/lodash.isarray@3.0.4/node_modules/lodash.isarray/index.js");

/** Used to detect unsigned integer values. */
var reIsUint = /^\d+$/;

/** Used for native method references. */
var objectProto = Object.prototype;

/** Used to check objects for own properties. */
var hasOwnProperty = objectProto.hasOwnProperty;

/* Native method references for those with the same name as other `lodash` methods. */
var nativeKeys = getNative(Object, 'keys');

/**
 * Used as the [maximum length](http://ecma-international.org/ecma-262/6.0/#sec-number.max_safe_integer)
 * of an array-like value.
 */
var MAX_SAFE_INTEGER = 9007199254740991;

/**
 * The base implementation of `_.property` without support for deep paths.
 *
 * @private
 * @param {string} key The key of the property to get.
 * @returns {Function} Returns the new function.
 */
function baseProperty(key) {
  return function(object) {
    return object == null ? undefined : object[key];
  };
}

/**
 * Gets the "length" property value of `object`.
 *
 * **Note:** This function is used to avoid a [JIT bug](https://bugs.webkit.org/show_bug.cgi?id=142792)
 * that affects Safari on at least iOS 8.1-8.3 ARM64.
 *
 * @private
 * @param {Object} object The object to query.
 * @returns {*} Returns the "length" value.
 */
var getLength = baseProperty('length');

/**
 * Checks if `value` is array-like.
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is array-like, else `false`.
 */
function isArrayLike(value) {
  return value != null && isLength(getLength(value));
}

/**
 * Checks if `value` is a valid array-like index.
 *
 * @private
 * @param {*} value The value to check.
 * @param {number} [length=MAX_SAFE_INTEGER] The upper bounds of a valid index.
 * @returns {boolean} Returns `true` if `value` is a valid index, else `false`.
 */
function isIndex(value, length) {
  value = (typeof value == 'number' || reIsUint.test(value)) ? +value : -1;
  length = length == null ? MAX_SAFE_INTEGER : length;
  return value > -1 && value % 1 == 0 && value < length;
}

/**
 * Checks if `value` is a valid array-like length.
 *
 * **Note:** This function is based on [`ToLength`](http://ecma-international.org/ecma-262/6.0/#sec-tolength).
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a valid length, else `false`.
 */
function isLength(value) {
  return typeof value == 'number' && value > -1 && value % 1 == 0 && value <= MAX_SAFE_INTEGER;
}

/**
 * A fallback implementation of `Object.keys` which creates an array of the
 * own enumerable property names of `object`.
 *
 * @private
 * @param {Object} object The object to query.
 * @returns {Array} Returns the array of property names.
 */
function shimKeys(object) {
  var props = keysIn(object),
      propsLength = props.length,
      length = propsLength && object.length;

  var allowIndexes = !!length && isLength(length) &&
    (isArray(object) || isArguments(object));

  var index = -1,
      result = [];

  while (++index < propsLength) {
    var key = props[index];
    if ((allowIndexes && isIndex(key, length)) || hasOwnProperty.call(object, key)) {
      result.push(key);
    }
  }
  return result;
}

/**
 * Checks if `value` is the [language type](https://es5.github.io/#x8) of `Object`.
 * (e.g. arrays, functions, objects, regexes, `new Number(0)`, and `new String('')`)
 *
 * @static
 * @memberOf _
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an object, else `false`.
 * @example
 *
 * _.isObject({});
 * // => true
 *
 * _.isObject([1, 2, 3]);
 * // => true
 *
 * _.isObject(1);
 * // => false
 */
function isObject(value) {
  // Avoid a V8 JIT bug in Chrome 19-20.
  // See https://code.google.com/p/v8/issues/detail?id=2291 for more details.
  var type = typeof value;
  return !!value && (type == 'object' || type == 'function');
}

/**
 * Creates an array of the own enumerable property names of `object`.
 *
 * **Note:** Non-object values are coerced to objects. See the
 * [ES spec](http://ecma-international.org/ecma-262/6.0/#sec-object.keys)
 * for more details.
 *
 * @static
 * @memberOf _
 * @category Object
 * @param {Object} object The object to query.
 * @returns {Array} Returns the array of property names.
 * @example
 *
 * function Foo() {
 *   this.a = 1;
 *   this.b = 2;
 * }
 *
 * Foo.prototype.c = 3;
 *
 * _.keys(new Foo);
 * // => ['a', 'b'] (iteration order is not guaranteed)
 *
 * _.keys('hi');
 * // => ['0', '1']
 */
var keys = !nativeKeys ? shimKeys : function(object) {
  var Ctor = object == null ? undefined : object.constructor;
  if ((typeof Ctor == 'function' && Ctor.prototype === object) ||
      (typeof object != 'function' && isArrayLike(object))) {
    return shimKeys(object);
  }
  return isObject(object) ? nativeKeys(object) : [];
};

/**
 * Creates an array of the own and inherited enumerable property names of `object`.
 *
 * **Note:** Non-object values are coerced to objects.
 *
 * @static
 * @memberOf _
 * @category Object
 * @param {Object} object The object to query.
 * @returns {Array} Returns the array of property names.
 * @example
 *
 * function Foo() {
 *   this.a = 1;
 *   this.b = 2;
 * }
 *
 * Foo.prototype.c = 3;
 *
 * _.keysIn(new Foo);
 * // => ['a', 'b', 'c'] (iteration order is not guaranteed)
 */
function keysIn(object) {
  if (object == null) {
    return [];
  }
  if (!isObject(object)) {
    object = Object(object);
  }
  var length = object.length;
  length = (length && isLength(length) &&
    (isArray(object) || isArguments(object)) && length) || 0;

  var Ctor = object.constructor,
      index = -1,
      isProto = typeof Ctor == 'function' && Ctor.prototype === object,
      result = Array(length),
      skipIndexes = length > 0;

  while (++index < length) {
    result[index] = (index + '');
  }
  for (var key in object) {
    if (!(skipIndexes && isIndex(key, length)) &&
        !(key == 'constructor' && (isProto || !hasOwnProperty.call(object, key)))) {
      result.push(key);
    }
  }
  return result;
}

module.exports = keys;


/***/ }),

/***/ "../../node_modules/.pnpm/lodash.pairs@3.0.1/node_modules/lodash.pairs/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

/**
 * lodash 3.0.1 (Custom Build) <https://lodash.com/>
 * Build: `lodash modern modularize exports="npm" -o ./`
 * Copyright 2012-2015 The Dojo Foundation <http://dojofoundation.org/>
 * Based on Underscore.js 1.8.3 <http://underscorejs.org/LICENSE>
 * Copyright 2009-2015 Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
 * Available under MIT license <https://lodash.com/license>
 */
var keys = __webpack_require__("../../node_modules/.pnpm/lodash.keys@3.1.2/node_modules/lodash.keys/index.js");

/**
 * Converts `value` to an object if it's not one.
 *
 * @private
 * @param {*} value The value to process.
 * @returns {Object} Returns the object.
 */
function toObject(value) {
  return isObject(value) ? value : Object(value);
}

/**
 * Checks if `value` is the [language type](https://es5.github.io/#x8) of `Object`.
 * (e.g. arrays, functions, objects, regexes, `new Number(0)`, and `new String('')`)
 *
 * @static
 * @memberOf _
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an object, else `false`.
 * @example
 *
 * _.isObject({});
 * // => true
 *
 * _.isObject([1, 2, 3]);
 * // => true
 *
 * _.isObject(1);
 * // => false
 */
function isObject(value) {
  // Avoid a V8 JIT bug in Chrome 19-20.
  // See https://code.google.com/p/v8/issues/detail?id=2291 for more details.
  var type = typeof value;
  return !!value && (type == 'object' || type == 'function');
}

/**
 * Creates a two dimensional array of the key-value pairs for `object`,
 * e.g. `[[key1, value1], [key2, value2]]`.
 *
 * @static
 * @memberOf _
 * @category Object
 * @param {Object} object The object to query.
 * @returns {Array} Returns the new array of key-value pairs.
 * @example
 *
 * _.pairs({ 'barney': 36, 'fred': 40 });
 * // => [['barney', 36], ['fred', 40]] (iteration order is not guaranteed)
 */
function pairs(object) {
  object = toObject(object);

  var index = -1,
      props = keys(object),
      length = props.length,
      result = Array(length);

  while (++index < length) {
    var key = props[index];
    result[index] = [key, object[key]];
  }
  return result;
}

module.exports = pairs;


/***/ }),

/***/ "../../node_modules/.pnpm/emoji-flags@1.3.0/node_modules/emoji-flags/data.json":
/***/ ((module) => {

"use strict";
module.exports = /*#__PURE__*/JSON.parse('[{"code":"AD","emoji":"🇦🇩","unicode":"U+1F1E6 U+1F1E9","name":"Andorra","title":"flag for Andorra","dialCode":"+376"},{"code":"AE","emoji":"🇦🇪","unicode":"U+1F1E6 U+1F1EA","name":"United Arab Emirates","title":"flag for United Arab Emirates","dialCode":"+971"},{"code":"AF","emoji":"🇦🇫","unicode":"U+1F1E6 U+1F1EB","name":"Afghanistan","title":"flag for Afghanistan","dialCode":"+93"},{"code":"AG","emoji":"🇦🇬","unicode":"U+1F1E6 U+1F1EC","name":"Antigua and Barbuda","title":"flag for Antigua and Barbuda","dialCode":"+1268"},{"code":"AI","emoji":"🇦🇮","unicode":"U+1F1E6 U+1F1EE","name":"Anguilla","title":"flag for Anguilla","dialCode":"+1 264"},{"code":"AL","emoji":"🇦🇱","unicode":"U+1F1E6 U+1F1F1","name":"Albania","title":"flag for Albania","dialCode":"+355"},{"code":"AM","emoji":"🇦🇲","unicode":"U+1F1E6 U+1F1F2","name":"Armenia","title":"flag for Armenia","dialCode":"+374"},{"code":"AO","emoji":"🇦🇴","unicode":"U+1F1E6 U+1F1F4","name":"Angola","title":"flag for Angola","dialCode":"+244"},{"code":"AQ","emoji":"🇦🇶","unicode":"U+1F1E6 U+1F1F6","name":"Antarctica","title":"flag for Antarctica","dialCode":null},{"code":"AR","emoji":"🇦🇷","unicode":"U+1F1E6 U+1F1F7","name":"Argentina","title":"flag for Argentina","dialCode":"+54"},{"code":"AS","emoji":"🇦🇸","unicode":"U+1F1E6 U+1F1F8","name":"American Samoa","title":"flag for American Samoa","dialCode":"+1 684"},{"code":"AT","emoji":"🇦🇹","unicode":"U+1F1E6 U+1F1F9","name":"Austria","title":"flag for Austria","dialCode":"+43"},{"code":"AU","emoji":"🇦🇺","unicode":"U+1F1E6 U+1F1FA","name":"Australia","title":"flag for Australia","dialCode":"+61"},{"code":"AW","emoji":"🇦🇼","unicode":"U+1F1E6 U+1F1FC","name":"Aruba","title":"flag for Aruba","dialCode":"+297"},{"code":"AX","emoji":"🇦🇽","unicode":"U+1F1E6 U+1F1FD","name":"Åland Islands","title":"flag for Åland Islands","dialCode":""},{"code":"AZ","emoji":"🇦🇿","unicode":"U+1F1E6 U+1F1FF","name":"Azerbaijan","title":"flag for Azerbaijan","dialCode":"+994"},{"code":"BA","emoji":"🇧🇦","unicode":"U+1F1E7 U+1F1E6","name":"Bosnia and Herzegovina","title":"flag for Bosnia and Herzegovina","dialCode":"+387"},{"code":"BB","emoji":"🇧🇧","unicode":"U+1F1E7 U+1F1E7","name":"Barbados","title":"flag for Barbados","dialCode":"+1 246"},{"code":"BD","emoji":"🇧🇩","unicode":"U+1F1E7 U+1F1E9","name":"Bangladesh","title":"flag for Bangladesh","dialCode":"+880"},{"code":"BE","emoji":"🇧🇪","unicode":"U+1F1E7 U+1F1EA","name":"Belgium","title":"flag for Belgium","dialCode":"+32"},{"code":"BF","emoji":"🇧🇫","unicode":"U+1F1E7 U+1F1EB","name":"Burkina Faso","title":"flag for Burkina Faso","dialCode":"+226"},{"code":"BG","emoji":"🇧🇬","unicode":"U+1F1E7 U+1F1EC","name":"Bulgaria","title":"flag for Bulgaria","dialCode":"+359"},{"code":"BH","emoji":"🇧🇭","unicode":"U+1F1E7 U+1F1ED","name":"Bahrain","title":"flag for Bahrain","dialCode":"+973"},{"code":"BI","emoji":"🇧🇮","unicode":"U+1F1E7 U+1F1EE","name":"Burundi","title":"flag for Burundi","dialCode":"+257"},{"code":"BJ","emoji":"🇧🇯","unicode":"U+1F1E7 U+1F1EF","name":"Benin","title":"flag for Benin","dialCode":"+229"},{"code":"BL","emoji":"🇧🇱","unicode":"U+1F1E7 U+1F1F1","name":"Saint Barthélemy","title":"flag for Saint Barthélemy","dialCode":"+590"},{"code":"BM","emoji":"🇧🇲","unicode":"U+1F1E7 U+1F1F2","name":"Bermuda","title":"flag for Bermuda","dialCode":"+1 441"},{"code":"BN","emoji":"🇧🇳","unicode":"U+1F1E7 U+1F1F3","name":"Brunei Darussalam","title":"flag for Brunei Darussalam","dialCode":"+673"},{"code":"BO","emoji":"🇧🇴","unicode":"U+1F1E7 U+1F1F4","name":"Bolivia","title":"flag for Bolivia","dialCode":"+591"},{"code":"BQ","emoji":"🇧🇶","unicode":"U+1F1E7 U+1F1F6","name":"Bonaire, Sint Eustatius and Saba","title":"flag for Bonaire, Sint Eustatius and Saba"},{"code":"BR","emoji":"🇧🇷","unicode":"U+1F1E7 U+1F1F7","name":"Brazil","title":"flag for Brazil","dialCode":"+55"},{"code":"BS","emoji":"🇧🇸","unicode":"U+1F1E7 U+1F1F8","name":"Bahamas","title":"flag for Bahamas","dialCode":"+1 242"},{"code":"BT","emoji":"🇧🇹","unicode":"U+1F1E7 U+1F1F9","name":"Bhutan","title":"flag for Bhutan","dialCode":"+975"},{"code":"BV","emoji":"🇧🇻","unicode":"U+1F1E7 U+1F1FB","name":"Bouvet Island","title":"flag for Bouvet Island"},{"code":"BW","emoji":"🇧🇼","unicode":"U+1F1E7 U+1F1FC","name":"Botswana","title":"flag for Botswana","dialCode":"+267"},{"code":"BY","emoji":"🇧🇾","unicode":"U+1F1E7 U+1F1FE","name":"Belarus","title":"flag for Belarus","dialCode":"+375"},{"code":"BZ","emoji":"🇧🇿","unicode":"U+1F1E7 U+1F1FF","name":"Belize","title":"flag for Belize","dialCode":"+501"},{"code":"CA","emoji":"🇨🇦","unicode":"U+1F1E8 U+1F1E6","name":"Canada","title":"flag for Canada","dialCode":"+1"},{"code":"CC","emoji":"🇨🇨","unicode":"U+1F1E8 U+1F1E8","name":"Cocos (Keeling) Islands","title":"flag for Cocos (Keeling) Islands","dialCode":"+61"},{"code":"CD","emoji":"🇨🇩","unicode":"U+1F1E8 U+1F1E9","name":"Congo","title":"flag for Congo","dialCode":"+243"},{"code":"CF","emoji":"🇨🇫","unicode":"U+1F1E8 U+1F1EB","name":"Central African Republic","title":"flag for Central African Republic","dialCode":"+236"},{"code":"CG","emoji":"🇨🇬","unicode":"U+1F1E8 U+1F1EC","name":"Congo","title":"flag for Congo","dialCode":"+242"},{"code":"CH","emoji":"🇨🇭","unicode":"U+1F1E8 U+1F1ED","name":"Switzerland","title":"flag for Switzerland","dialCode":"+41"},{"code":"CI","emoji":"🇨🇮","unicode":"U+1F1E8 U+1F1EE","name":"Côte D\'Ivoire","title":"flag for Côte D\'Ivoire","dialCode":"+225"},{"code":"CK","emoji":"🇨🇰","unicode":"U+1F1E8 U+1F1F0","name":"Cook Islands","title":"flag for Cook Islands","dialCode":"+682"},{"code":"CL","emoji":"🇨🇱","unicode":"U+1F1E8 U+1F1F1","name":"Chile","title":"flag for Chile","dialCode":"+56"},{"code":"CM","emoji":"🇨🇲","unicode":"U+1F1E8 U+1F1F2","name":"Cameroon","title":"flag for Cameroon","dialCode":"+237"},{"code":"CN","emoji":"🇨🇳","unicode":"U+1F1E8 U+1F1F3","name":"China","title":"flag for China","dialCode":"+86"},{"code":"CO","emoji":"🇨🇴","unicode":"U+1F1E8 U+1F1F4","name":"Colombia","title":"flag for Colombia","dialCode":"+57"},{"code":"CR","emoji":"🇨🇷","unicode":"U+1F1E8 U+1F1F7","name":"Costa Rica","title":"flag for Costa Rica","dialCode":"+506"},{"code":"CU","emoji":"🇨🇺","unicode":"U+1F1E8 U+1F1FA","name":"Cuba","title":"flag for Cuba","dialCode":"+53"},{"code":"CV","emoji":"🇨🇻","unicode":"U+1F1E8 U+1F1FB","name":"Cape Verde","title":"flag for Cape Verde","dialCode":"+238"},{"code":"CW","emoji":"🇨🇼","unicode":"U+1F1E8 U+1F1FC","name":"Curaçao","title":"flag for Curaçao"},{"code":"CX","emoji":"🇨🇽","unicode":"U+1F1E8 U+1F1FD","name":"Christmas Island","title":"flag for Christmas Island","dialCode":"+61"},{"code":"CY","emoji":"🇨🇾","unicode":"U+1F1E8 U+1F1FE","name":"Cyprus","title":"flag for Cyprus","dialCode":"+537"},{"code":"CZ","emoji":"🇨🇿","unicode":"U+1F1E8 U+1F1FF","name":"Czech Republic","title":"flag for Czech Republic","dialCode":"+420"},{"code":"DE","emoji":"🇩🇪","unicode":"U+1F1E9 U+1F1EA","name":"Germany","title":"flag for Germany","dialCode":"+49"},{"code":"DJ","emoji":"🇩🇯","unicode":"U+1F1E9 U+1F1EF","name":"Djibouti","title":"flag for Djibouti","dialCode":"+253"},{"code":"DK","emoji":"🇩🇰","unicode":"U+1F1E9 U+1F1F0","name":"Denmark","title":"flag for Denmark","dialCode":"+45"},{"code":"DM","emoji":"🇩🇲","unicode":"U+1F1E9 U+1F1F2","name":"Dominica","title":"flag for Dominica","dialCode":"+1 767"},{"code":"DO","emoji":"🇩🇴","unicode":"U+1F1E9 U+1F1F4","name":"Dominican Republic","title":"flag for Dominican Republic","dialCode":"+1 849"},{"code":"DZ","emoji":"🇩🇿","unicode":"U+1F1E9 U+1F1FF","name":"Algeria","title":"flag for Algeria","dialCode":"+213"},{"code":"EC","emoji":"🇪🇨","unicode":"U+1F1EA U+1F1E8","name":"Ecuador","title":"flag for Ecuador","dialCode":"+593"},{"code":"EE","emoji":"🇪🇪","unicode":"U+1F1EA U+1F1EA","name":"Estonia","title":"flag for Estonia","dialCode":"+372"},{"code":"EG","emoji":"🇪🇬","unicode":"U+1F1EA U+1F1EC","name":"Egypt","title":"flag for Egypt","dialCode":"+20"},{"code":"EH","emoji":"🇪🇭","unicode":"U+1F1EA U+1F1ED","name":"Western Sahara","title":"flag for Western Sahara"},{"code":"ER","emoji":"🇪🇷","unicode":"U+1F1EA U+1F1F7","name":"Eritrea","title":"flag for Eritrea","dialCode":"+291"},{"code":"ES","emoji":"🇪🇸","unicode":"U+1F1EA U+1F1F8","name":"Spain","title":"flag for Spain","dialCode":"+34"},{"code":"ET","emoji":"🇪🇹","unicode":"U+1F1EA U+1F1F9","name":"Ethiopia","title":"flag for Ethiopia","dialCode":"+251"},{"code":"EU","emoji":"🇪🇺","unicode":"U+1F1EA U+1F1FA","name":"European Union","title":"flag for European Union"},{"code":"FI","emoji":"🇫🇮","unicode":"U+1F1EB U+1F1EE","name":"Finland","title":"flag for Finland","dialCode":"+358"},{"code":"FJ","emoji":"🇫🇯","unicode":"U+1F1EB U+1F1EF","name":"Fiji","title":"flag for Fiji","dialCode":"+679"},{"code":"FK","emoji":"🇫🇰","unicode":"U+1F1EB U+1F1F0","name":"Falkland Islands (Malvinas)","title":"flag for Falkland Islands (Malvinas)","dialCode":"+500"},{"code":"FM","emoji":"🇫🇲","unicode":"U+1F1EB U+1F1F2","name":"Micronesia","title":"flag for Micronesia","dialCode":"+691"},{"code":"FO","emoji":"🇫🇴","unicode":"U+1F1EB U+1F1F4","name":"Faroe Islands","title":"flag for Faroe Islands","dialCode":"+298"},{"code":"FR","emoji":"🇫🇷","unicode":"U+1F1EB U+1F1F7","name":"France","title":"flag for France","dialCode":"+33"},{"code":"GA","emoji":"🇬🇦","unicode":"U+1F1EC U+1F1E6","name":"Gabon","title":"flag for Gabon","dialCode":"+241"},{"code":"GB","emoji":"🇬🇧","unicode":"U+1F1EC U+1F1E7","name":"United Kingdom","title":"flag for United Kingdom","dialCode":"+44"},{"code":"GD","emoji":"🇬🇩","unicode":"U+1F1EC U+1F1E9","name":"Grenada","title":"flag for Grenada","dialCode":"+1 473"},{"code":"GE","emoji":"🇬🇪","unicode":"U+1F1EC U+1F1EA","name":"Georgia","title":"flag for Georgia","dialCode":"+995"},{"code":"GF","emoji":"🇬🇫","unicode":"U+1F1EC U+1F1EB","name":"French Guiana","title":"flag for French Guiana","dialCode":"+594"},{"code":"GG","emoji":"🇬🇬","unicode":"U+1F1EC U+1F1EC","name":"Guernsey","title":"flag for Guernsey","dialCode":"+44"},{"code":"GH","emoji":"🇬🇭","unicode":"U+1F1EC U+1F1ED","name":"Ghana","title":"flag for Ghana","dialCode":"+233"},{"code":"GI","emoji":"🇬🇮","unicode":"U+1F1EC U+1F1EE","name":"Gibraltar","title":"flag for Gibraltar","dialCode":"+350"},{"code":"GL","emoji":"🇬🇱","unicode":"U+1F1EC U+1F1F1","name":"Greenland","title":"flag for Greenland","dialCode":"+299"},{"code":"GM","emoji":"🇬🇲","unicode":"U+1F1EC U+1F1F2","name":"Gambia","title":"flag for Gambia","dialCode":"+220"},{"code":"GN","emoji":"🇬🇳","unicode":"U+1F1EC U+1F1F3","name":"Guinea","title":"flag for Guinea","dialCode":"+224"},{"code":"GP","emoji":"🇬🇵","unicode":"U+1F1EC U+1F1F5","name":"Guadeloupe","title":"flag for Guadeloupe","dialCode":"+590"},{"code":"GQ","emoji":"🇬🇶","unicode":"U+1F1EC U+1F1F6","name":"Equatorial Guinea","title":"flag for Equatorial Guinea","dialCode":"+240"},{"code":"GR","emoji":"🇬🇷","unicode":"U+1F1EC U+1F1F7","name":"Greece","title":"flag for Greece","dialCode":"+30"},{"code":"GS","emoji":"🇬🇸","unicode":"U+1F1EC U+1F1F8","name":"South Georgia","title":"flag for South Georgia","dialCode":"+500"},{"code":"GT","emoji":"🇬🇹","unicode":"U+1F1EC U+1F1F9","name":"Guatemala","title":"flag for Guatemala","dialCode":"+502"},{"code":"GU","emoji":"🇬🇺","unicode":"U+1F1EC U+1F1FA","name":"Guam","title":"flag for Guam","dialCode":"+1 671"},{"code":"GW","emoji":"🇬🇼","unicode":"U+1F1EC U+1F1FC","name":"Guinea-Bissau","title":"flag for Guinea-Bissau","dialCode":"+245"},{"code":"GY","emoji":"🇬🇾","unicode":"U+1F1EC U+1F1FE","name":"Guyana","title":"flag for Guyana","dialCode":"+595"},{"code":"HK","emoji":"🇭🇰","unicode":"U+1F1ED U+1F1F0","name":"Hong Kong","title":"flag for Hong Kong","dialCode":"+852"},{"code":"HM","emoji":"🇭🇲","unicode":"U+1F1ED U+1F1F2","name":"Heard Island and Mcdonald Islands","title":"flag for Heard Island and Mcdonald Islands"},{"code":"HN","emoji":"🇭🇳","unicode":"U+1F1ED U+1F1F3","name":"Honduras","title":"flag for Honduras","dialCode":"+504"},{"code":"HR","emoji":"🇭🇷","unicode":"U+1F1ED U+1F1F7","name":"Croatia","title":"flag for Croatia","dialCode":"+385"},{"code":"HT","emoji":"🇭🇹","unicode":"U+1F1ED U+1F1F9","name":"Haiti","title":"flag for Haiti","dialCode":"+509"},{"code":"HU","emoji":"🇭🇺","unicode":"U+1F1ED U+1F1FA","name":"Hungary","title":"flag for Hungary","dialCode":"+36"},{"code":"ID","emoji":"🇮🇩","unicode":"U+1F1EE U+1F1E9","name":"Indonesia","title":"flag for Indonesia","dialCode":"+62"},{"code":"IE","emoji":"🇮🇪","unicode":"U+1F1EE U+1F1EA","name":"Ireland","title":"flag for Ireland","dialCode":"+353"},{"code":"IL","emoji":"🇮🇱","unicode":"U+1F1EE U+1F1F1","name":"Israel","title":"flag for Israel","dialCode":"+972"},{"code":"IM","emoji":"🇮🇲","unicode":"U+1F1EE U+1F1F2","name":"Isle of Man","title":"flag for Isle of Man","dialCode":"+44"},{"code":"IN","emoji":"🇮🇳","unicode":"U+1F1EE U+1F1F3","name":"India","title":"flag for India","dialCode":"+91"},{"code":"IO","emoji":"🇮🇴","unicode":"U+1F1EE U+1F1F4","name":"British Indian Ocean Territory","title":"flag for British Indian Ocean Territory","dialCode":"+246"},{"code":"IQ","emoji":"🇮🇶","unicode":"U+1F1EE U+1F1F6","name":"Iraq","title":"flag for Iraq","dialCode":"+964"},{"code":"IR","emoji":"🇮🇷","unicode":"U+1F1EE U+1F1F7","name":"Iran","title":"flag for Iran","dialCode":"+98"},{"code":"IS","emoji":"🇮🇸","unicode":"U+1F1EE U+1F1F8","name":"Iceland","title":"flag for Iceland","dialCode":"+354"},{"code":"IT","emoji":"🇮🇹","unicode":"U+1F1EE U+1F1F9","name":"Italy","title":"flag for Italy","dialCode":"+39"},{"code":"JE","emoji":"🇯🇪","unicode":"U+1F1EF U+1F1EA","name":"Jersey","title":"flag for Jersey","dialCode":"+44"},{"code":"JM","emoji":"🇯🇲","unicode":"U+1F1EF U+1F1F2","name":"Jamaica","title":"flag for Jamaica","dialCode":"+1 876"},{"code":"JO","emoji":"🇯🇴","unicode":"U+1F1EF U+1F1F4","name":"Jordan","title":"flag for Jordan","dialCode":"+962"},{"code":"JP","emoji":"🇯🇵","unicode":"U+1F1EF U+1F1F5","name":"Japan","title":"flag for Japan","dialCode":"+81"},{"code":"KE","emoji":"🇰🇪","unicode":"U+1F1F0 U+1F1EA","name":"Kenya","title":"flag for Kenya","dialCode":"+254"},{"code":"KG","emoji":"🇰🇬","unicode":"U+1F1F0 U+1F1EC","name":"Kyrgyzstan","title":"flag for Kyrgyzstan","dialCode":"+996"},{"code":"KH","emoji":"🇰🇭","unicode":"U+1F1F0 U+1F1ED","name":"Cambodia","title":"flag for Cambodia","dialCode":"+855"},{"code":"KI","emoji":"🇰🇮","unicode":"U+1F1F0 U+1F1EE","name":"Kiribati","title":"flag for Kiribati","dialCode":"+686"},{"code":"KM","emoji":"🇰🇲","unicode":"U+1F1F0 U+1F1F2","name":"Comoros","title":"flag for Comoros","dialCode":"+269"},{"code":"KN","emoji":"🇰🇳","unicode":"U+1F1F0 U+1F1F3","name":"Saint Kitts and Nevis","title":"flag for Saint Kitts and Nevis","dialCode":"+1 869"},{"code":"KP","emoji":"🇰🇵","unicode":"U+1F1F0 U+1F1F5","name":"North Korea","title":"flag for North Korea","dialCode":"+850"},{"code":"KR","emoji":"🇰🇷","unicode":"U+1F1F0 U+1F1F7","name":"South Korea","title":"flag for South Korea","dialCode":"+82"},{"code":"KW","emoji":"🇰🇼","unicode":"U+1F1F0 U+1F1FC","name":"Kuwait","title":"flag for Kuwait","dialCode":"+965"},{"code":"KY","emoji":"🇰🇾","unicode":"U+1F1F0 U+1F1FE","name":"Cayman Islands","title":"flag for Cayman Islands","dialCode":"+ 345"},{"code":"KZ","emoji":"🇰🇿","unicode":"U+1F1F0 U+1F1FF","name":"Kazakhstan","title":"flag for Kazakhstan","dialCode":"+7 7"},{"code":"LA","emoji":"🇱🇦","unicode":"U+1F1F1 U+1F1E6","name":"Lao People\'s Democratic Republic","title":"flag for Lao People\'s Democratic Republic","dialCode":"+856"},{"code":"LB","emoji":"🇱🇧","unicode":"U+1F1F1 U+1F1E7","name":"Lebanon","title":"flag for Lebanon","dialCode":"+961"},{"code":"LC","emoji":"🇱🇨","unicode":"U+1F1F1 U+1F1E8","name":"Saint Lucia","title":"flag for Saint Lucia","dialCode":"+1 758"},{"code":"LI","emoji":"🇱🇮","unicode":"U+1F1F1 U+1F1EE","name":"Liechtenstein","title":"flag for Liechtenstein","dialCode":"+423"},{"code":"LK","emoji":"🇱🇰","unicode":"U+1F1F1 U+1F1F0","name":"Sri Lanka","title":"flag for Sri Lanka","dialCode":"+94"},{"code":"LR","emoji":"🇱🇷","unicode":"U+1F1F1 U+1F1F7","name":"Liberia","title":"flag for Liberia","dialCode":"+231"},{"code":"LS","emoji":"🇱🇸","unicode":"U+1F1F1 U+1F1F8","name":"Lesotho","title":"flag for Lesotho","dialCode":"+266"},{"code":"LT","emoji":"🇱🇹","unicode":"U+1F1F1 U+1F1F9","name":"Lithuania","title":"flag for Lithuania","dialCode":"+370"},{"code":"LU","emoji":"🇱🇺","unicode":"U+1F1F1 U+1F1FA","name":"Luxembourg","title":"flag for Luxembourg","dialCode":"+352"},{"code":"LV","emoji":"🇱🇻","unicode":"U+1F1F1 U+1F1FB","name":"Latvia","title":"flag for Latvia","dialCode":"+371"},{"code":"LY","emoji":"🇱🇾","unicode":"U+1F1F1 U+1F1FE","name":"Libya","title":"flag for Libya","dialCode":"+218"},{"code":"MA","emoji":"🇲🇦","unicode":"U+1F1F2 U+1F1E6","name":"Morocco","title":"flag for Morocco","dialCode":"+212"},{"code":"MC","emoji":"🇲🇨","unicode":"U+1F1F2 U+1F1E8","name":"Monaco","title":"flag for Monaco","dialCode":"+377"},{"code":"MD","emoji":"🇲🇩","unicode":"U+1F1F2 U+1F1E9","name":"Moldova","title":"flag for Moldova","dialCode":"+373"},{"code":"ME","emoji":"🇲🇪","unicode":"U+1F1F2 U+1F1EA","name":"Montenegro","title":"flag for Montenegro","dialCode":"+382"},{"code":"MF","emoji":"🇲🇫","unicode":"U+1F1F2 U+1F1EB","name":"Saint Martin (French Part)","title":"flag for Saint Martin (French Part)","dialCode":"+590"},{"code":"MG","emoji":"🇲🇬","unicode":"U+1F1F2 U+1F1EC","name":"Madagascar","title":"flag for Madagascar","dialCode":"+261"},{"code":"MH","emoji":"🇲🇭","unicode":"U+1F1F2 U+1F1ED","name":"Marshall Islands","title":"flag for Marshall Islands","dialCode":"+692"},{"code":"MK","emoji":"🇲🇰","unicode":"U+1F1F2 U+1F1F0","name":"Macedonia","title":"flag for Macedonia","dialCode":"+389"},{"code":"ML","emoji":"🇲🇱","unicode":"U+1F1F2 U+1F1F1","name":"Mali","title":"flag for Mali","dialCode":"+223"},{"code":"MM","emoji":"🇲🇲","unicode":"U+1F1F2 U+1F1F2","name":"Myanmar","title":"flag for Myanmar","dialCode":"+95"},{"code":"MN","emoji":"🇲🇳","unicode":"U+1F1F2 U+1F1F3","name":"Mongolia","title":"flag for Mongolia","dialCode":"+976"},{"code":"MO","emoji":"🇲🇴","unicode":"U+1F1F2 U+1F1F4","name":"Macao","title":"flag for Macao","dialCode":"+853"},{"code":"MP","emoji":"🇲🇵","unicode":"U+1F1F2 U+1F1F5","name":"Northern Mariana Islands","title":"flag for Northern Mariana Islands","dialCode":"+1 670"},{"code":"MQ","emoji":"🇲🇶","unicode":"U+1F1F2 U+1F1F6","name":"Martinique","title":"flag for Martinique","dialCode":"+596"},{"code":"MR","emoji":"🇲🇷","unicode":"U+1F1F2 U+1F1F7","name":"Mauritania","title":"flag for Mauritania","dialCode":"+222"},{"code":"MS","emoji":"🇲🇸","unicode":"U+1F1F2 U+1F1F8","name":"Montserrat","title":"flag for Montserrat","dialCode":"+1664"},{"code":"MT","emoji":"🇲🇹","unicode":"U+1F1F2 U+1F1F9","name":"Malta","title":"flag for Malta","dialCode":"+356"},{"code":"MU","emoji":"🇲🇺","unicode":"U+1F1F2 U+1F1FA","name":"Mauritius","title":"flag for Mauritius","dialCode":"+230"},{"code":"MV","emoji":"🇲🇻","unicode":"U+1F1F2 U+1F1FB","name":"Maldives","title":"flag for Maldives","dialCode":"+960"},{"code":"MW","emoji":"🇲🇼","unicode":"U+1F1F2 U+1F1FC","name":"Malawi","title":"flag for Malawi","dialCode":"+265"},{"code":"MX","emoji":"🇲🇽","unicode":"U+1F1F2 U+1F1FD","name":"Mexico","title":"flag for Mexico","dialCode":"+52"},{"code":"MY","emoji":"🇲🇾","unicode":"U+1F1F2 U+1F1FE","name":"Malaysia","title":"flag for Malaysia","dialCode":"+60"},{"code":"MZ","emoji":"🇲🇿","unicode":"U+1F1F2 U+1F1FF","name":"Mozambique","title":"flag for Mozambique","dialCode":"+258"},{"code":"NA","emoji":"🇳🇦","unicode":"U+1F1F3 U+1F1E6","name":"Namibia","title":"flag for Namibia","dialCode":"+264"},{"code":"NC","emoji":"🇳🇨","unicode":"U+1F1F3 U+1F1E8","name":"New Caledonia","title":"flag for New Caledonia","dialCode":"+687"},{"code":"NE","emoji":"🇳🇪","unicode":"U+1F1F3 U+1F1EA","name":"Niger","title":"flag for Niger","dialCode":"+227"},{"code":"NF","emoji":"🇳🇫","unicode":"U+1F1F3 U+1F1EB","name":"Norfolk Island","title":"flag for Norfolk Island","dialCode":"+672"},{"code":"NG","emoji":"🇳🇬","unicode":"U+1F1F3 U+1F1EC","name":"Nigeria","title":"flag for Nigeria","dialCode":"+234"},{"code":"NI","emoji":"🇳🇮","unicode":"U+1F1F3 U+1F1EE","name":"Nicaragua","title":"flag for Nicaragua","dialCode":"+505"},{"code":"NL","emoji":"🇳🇱","unicode":"U+1F1F3 U+1F1F1","name":"Netherlands","title":"flag for Netherlands","dialCode":"+31"},{"code":"NO","emoji":"🇳🇴","unicode":"U+1F1F3 U+1F1F4","name":"Norway","title":"flag for Norway","dialCode":"+47"},{"code":"NP","emoji":"🇳🇵","unicode":"U+1F1F3 U+1F1F5","name":"Nepal","title":"flag for Nepal","dialCode":"+977"},{"code":"NR","emoji":"🇳🇷","unicode":"U+1F1F3 U+1F1F7","name":"Nauru","title":"flag for Nauru","dialCode":"+674"},{"code":"NU","emoji":"🇳🇺","unicode":"U+1F1F3 U+1F1FA","name":"Niue","title":"flag for Niue","dialCode":"+683"},{"code":"NZ","emoji":"🇳🇿","unicode":"U+1F1F3 U+1F1FF","name":"New Zealand","title":"flag for New Zealand","dialCode":"+64"},{"code":"OM","emoji":"🇴🇲","unicode":"U+1F1F4 U+1F1F2","name":"Oman","title":"flag for Oman","dialCode":"+968"},{"code":"PA","emoji":"🇵🇦","unicode":"U+1F1F5 U+1F1E6","name":"Panama","title":"flag for Panama","dialCode":"+507"},{"code":"PE","emoji":"🇵🇪","unicode":"U+1F1F5 U+1F1EA","name":"Peru","title":"flag for Peru","dialCode":"+51"},{"code":"PF","emoji":"🇵🇫","unicode":"U+1F1F5 U+1F1EB","name":"French Polynesia","title":"flag for French Polynesia","dialCode":"+689"},{"code":"PG","emoji":"🇵🇬","unicode":"U+1F1F5 U+1F1EC","name":"Papua New Guinea","title":"flag for Papua New Guinea","dialCode":"+675"},{"code":"PH","emoji":"🇵🇭","unicode":"U+1F1F5 U+1F1ED","name":"Philippines","title":"flag for Philippines","dialCode":"+63"},{"code":"PK","emoji":"🇵🇰","unicode":"U+1F1F5 U+1F1F0","name":"Pakistan","title":"flag for Pakistan","dialCode":"+92"},{"code":"PL","emoji":"🇵🇱","unicode":"U+1F1F5 U+1F1F1","name":"Poland","title":"flag for Poland","dialCode":"+48"},{"code":"PM","emoji":"🇵🇲","unicode":"U+1F1F5 U+1F1F2","name":"Saint Pierre and Miquelon","title":"flag for Saint Pierre and Miquelon","dialCode":"+508"},{"code":"PN","emoji":"🇵🇳","unicode":"U+1F1F5 U+1F1F3","name":"Pitcairn","title":"flag for Pitcairn","dialCode":"+872"},{"code":"PR","emoji":"🇵🇷","unicode":"U+1F1F5 U+1F1F7","name":"Puerto Rico","title":"flag for Puerto Rico","dialCode":"+1 939"},{"code":"PS","emoji":"🇵🇸","unicode":"U+1F1F5 U+1F1F8","name":"Palestinian Territory","title":"flag for Palestinian Territory","dialCode":"+970"},{"code":"PT","emoji":"🇵🇹","unicode":"U+1F1F5 U+1F1F9","name":"Portugal","title":"flag for Portugal","dialCode":"+351"},{"code":"PW","emoji":"🇵🇼","unicode":"U+1F1F5 U+1F1FC","name":"Palau","title":"flag for Palau","dialCode":"+680"},{"code":"PY","emoji":"🇵🇾","unicode":"U+1F1F5 U+1F1FE","name":"Paraguay","title":"flag for Paraguay","dialCode":"+595"},{"code":"QA","emoji":"🇶🇦","unicode":"U+1F1F6 U+1F1E6","name":"Qatar","title":"flag for Qatar","dialCode":"+974"},{"code":"RE","emoji":"🇷🇪","unicode":"U+1F1F7 U+1F1EA","name":"Réunion","title":"flag for Réunion","dialCode":"+262"},{"code":"RO","emoji":"🇷🇴","unicode":"U+1F1F7 U+1F1F4","name":"Romania","title":"flag for Romania","dialCode":"+40"},{"code":"RS","emoji":"🇷🇸","unicode":"U+1F1F7 U+1F1F8","name":"Serbia","title":"flag for Serbia","dialCode":"+381"},{"code":"RU","emoji":"🇷🇺","unicode":"U+1F1F7 U+1F1FA","name":"Russia","title":"flag for Russia","dialCode":"+7"},{"code":"RW","emoji":"🇷🇼","unicode":"U+1F1F7 U+1F1FC","name":"Rwanda","title":"flag for Rwanda","dialCode":"+250"},{"code":"SA","emoji":"🇸🇦","unicode":"U+1F1F8 U+1F1E6","name":"Saudi Arabia","title":"flag for Saudi Arabia","dialCode":"+966"},{"code":"SB","emoji":"🇸🇧","unicode":"U+1F1F8 U+1F1E7","name":"Solomon Islands","title":"flag for Solomon Islands","dialCode":"+677"},{"code":"SC","emoji":"🇸🇨","unicode":"U+1F1F8 U+1F1E8","name":"Seychelles","title":"flag for Seychelles","dialCode":"+248"},{"code":"SD","emoji":"🇸🇩","unicode":"U+1F1F8 U+1F1E9","name":"Sudan","title":"flag for Sudan","dialCode":"+249"},{"code":"SE","emoji":"🇸🇪","unicode":"U+1F1F8 U+1F1EA","name":"Sweden","title":"flag for Sweden","dialCode":"+46"},{"code":"SG","emoji":"🇸🇬","unicode":"U+1F1F8 U+1F1EC","name":"Singapore","title":"flag for Singapore","dialCode":"+65"},{"code":"SH","emoji":"🇸🇭","unicode":"U+1F1F8 U+1F1ED","name":"Saint Helena, Ascension and Tristan Da Cunha","title":"flag for Saint Helena, Ascension and Tristan Da Cunha","dialCode":"+290"},{"code":"SI","emoji":"🇸🇮","unicode":"U+1F1F8 U+1F1EE","name":"Slovenia","title":"flag for Slovenia","dialCode":"+386"},{"code":"SJ","emoji":"🇸🇯","unicode":"U+1F1F8 U+1F1EF","name":"Svalbard and Jan Mayen","title":"flag for Svalbard and Jan Mayen","dialCode":"+47"},{"code":"SK","emoji":"🇸🇰","unicode":"U+1F1F8 U+1F1F0","name":"Slovakia","title":"flag for Slovakia","dialCode":"+421"},{"code":"SL","emoji":"🇸🇱","unicode":"U+1F1F8 U+1F1F1","name":"Sierra Leone","title":"flag for Sierra Leone","dialCode":"+232"},{"code":"SM","emoji":"🇸🇲","unicode":"U+1F1F8 U+1F1F2","name":"San Marino","title":"flag for San Marino","dialCode":"+378"},{"code":"SN","emoji":"🇸🇳","unicode":"U+1F1F8 U+1F1F3","name":"Senegal","title":"flag for Senegal","dialCode":"+221"},{"code":"SO","emoji":"🇸🇴","unicode":"U+1F1F8 U+1F1F4","name":"Somalia","title":"flag for Somalia","dialCode":"+252"},{"code":"SR","emoji":"🇸🇷","unicode":"U+1F1F8 U+1F1F7","name":"Suriname","title":"flag for Suriname","dialCode":"+597"},{"code":"SS","emoji":"🇸🇸","unicode":"U+1F1F8 U+1F1F8","name":"South Sudan","title":"flag for South Sudan"},{"code":"ST","emoji":"🇸🇹","unicode":"U+1F1F8 U+1F1F9","name":"Sao Tome and Principe","title":"flag for Sao Tome and Principe","dialCode":"+239"},{"code":"SV","emoji":"🇸🇻","unicode":"U+1F1F8 U+1F1FB","name":"El Salvador","title":"flag for El Salvador","dialCode":"+503"},{"code":"SX","emoji":"🇸🇽","unicode":"U+1F1F8 U+1F1FD","name":"Sint Maarten (Dutch Part)","title":"flag for Sint Maarten (Dutch Part)"},{"code":"SY","emoji":"🇸🇾","unicode":"U+1F1F8 U+1F1FE","name":"Syrian Arab Republic","title":"flag for Syrian Arab Republic","dialCode":"+963"},{"code":"SZ","emoji":"🇸🇿","unicode":"U+1F1F8 U+1F1FF","name":"Swaziland","title":"flag for Swaziland","dialCode":"+268"},{"code":"TC","emoji":"🇹🇨","unicode":"U+1F1F9 U+1F1E8","name":"Turks and Caicos Islands","title":"flag for Turks and Caicos Islands","dialCode":"+1 649"},{"code":"TD","emoji":"🇹🇩","unicode":"U+1F1F9 U+1F1E9","name":"Chad","title":"flag for Chad","dialCode":"+235"},{"code":"TF","emoji":"🇹🇫","unicode":"U+1F1F9 U+1F1EB","name":"French Southern Territories","title":"flag for French Southern Territories"},{"code":"TG","emoji":"🇹🇬","unicode":"U+1F1F9 U+1F1EC","name":"Togo","title":"flag for Togo","dialCode":"+228"},{"code":"TH","emoji":"🇹🇭","unicode":"U+1F1F9 U+1F1ED","name":"Thailand","title":"flag for Thailand","dialCode":"+66"},{"code":"TJ","emoji":"🇹🇯","unicode":"U+1F1F9 U+1F1EF","name":"Tajikistan","title":"flag for Tajikistan","dialCode":"+992"},{"code":"TK","emoji":"🇹🇰","unicode":"U+1F1F9 U+1F1F0","name":"Tokelau","title":"flag for Tokelau","dialCode":"+690"},{"code":"TL","emoji":"🇹🇱","unicode":"U+1F1F9 U+1F1F1","name":"Timor-Leste","title":"flag for Timor-Leste","dialCode":"+670"},{"code":"TM","emoji":"🇹🇲","unicode":"U+1F1F9 U+1F1F2","name":"Turkmenistan","title":"flag for Turkmenistan","dialCode":"+993"},{"code":"TN","emoji":"🇹🇳","unicode":"U+1F1F9 U+1F1F3","name":"Tunisia","title":"flag for Tunisia","dialCode":"+216"},{"code":"TO","emoji":"🇹🇴","unicode":"U+1F1F9 U+1F1F4","name":"Tonga","title":"flag for Tonga","dialCode":"+676"},{"code":"TR","emoji":"🇹🇷","unicode":"U+1F1F9 U+1F1F7","name":"Turkey","title":"flag for Turkey","dialCode":"+90"},{"code":"TT","emoji":"🇹🇹","unicode":"U+1F1F9 U+1F1F9","name":"Trinidad and Tobago","title":"flag for Trinidad and Tobago","dialCode":"+1 868"},{"code":"TV","emoji":"🇹🇻","unicode":"U+1F1F9 U+1F1FB","name":"Tuvalu","title":"flag for Tuvalu","dialCode":"+688"},{"code":"TW","emoji":"🇹🇼","unicode":"U+1F1F9 U+1F1FC","name":"Taiwan","title":"flag for Taiwan","dialCode":"+886"},{"code":"TZ","emoji":"🇹🇿","unicode":"U+1F1F9 U+1F1FF","name":"Tanzania","title":"flag for Tanzania","dialCode":"+255"},{"code":"UA","emoji":"🇺🇦","unicode":"U+1F1FA U+1F1E6","name":"Ukraine","title":"flag for Ukraine","dialCode":"+380"},{"code":"UG","emoji":"🇺🇬","unicode":"U+1F1FA U+1F1EC","name":"Uganda","title":"flag for Uganda","dialCode":"+256"},{"code":"UM","emoji":"🇺🇲","unicode":"U+1F1FA U+1F1F2","name":"United States Minor Outlying Islands","title":"flag for United States Minor Outlying Islands"},{"code":"US","emoji":"🇺🇸","unicode":"U+1F1FA U+1F1F8","name":"United States","title":"flag for United States","dialCode":"+1"},{"code":"UY","emoji":"🇺🇾","unicode":"U+1F1FA U+1F1FE","name":"Uruguay","title":"flag for Uruguay","dialCode":"+598"},{"code":"UZ","emoji":"🇺🇿","unicode":"U+1F1FA U+1F1FF","name":"Uzbekistan","title":"flag for Uzbekistan","dialCode":"+998"},{"code":"VA","emoji":"🇻🇦","unicode":"U+1F1FB U+1F1E6","name":"Vatican City","title":"flag for Vatican City","dialCode":"+379"},{"code":"VC","emoji":"🇻🇨","unicode":"U+1F1FB U+1F1E8","name":"Saint Vincent and The Grenadines","title":"flag for Saint Vincent and The Grenadines","dialCode":"+1 784"},{"code":"VE","emoji":"🇻🇪","unicode":"U+1F1FB U+1F1EA","name":"Venezuela","title":"flag for Venezuela","dialCode":"+58"},{"code":"VG","emoji":"🇻🇬","unicode":"U+1F1FB U+1F1EC","name":"Virgin Islands, British","title":"flag for Virgin Islands, British","dialCode":"+1 284"},{"code":"VI","emoji":"🇻🇮","unicode":"U+1F1FB U+1F1EE","name":"Virgin Islands, U.S.","title":"flag for Virgin Islands, U.S.","dialCode":"+1 340"},{"code":"VN","emoji":"🇻🇳","unicode":"U+1F1FB U+1F1F3","name":"Viet Nam","title":"flag for Viet Nam","dialCode":"+84"},{"code":"VU","emoji":"🇻🇺","unicode":"U+1F1FB U+1F1FA","name":"Vanuatu","title":"flag for Vanuatu","dialCode":"+678"},{"code":"WF","emoji":"🇼🇫","unicode":"U+1F1FC U+1F1EB","name":"Wallis and Futuna","title":"flag for Wallis and Futuna","dialCode":"+681"},{"code":"WS","emoji":"🇼🇸","unicode":"U+1F1FC U+1F1F8","name":"Samoa","title":"flag for Samoa","dialCode":"+685"},{"code":"XK","emoji":"🇽🇰","unicode":"U+1F1FD U+1F1F0","name":"Kosovo","title":"flag for Kosovo","dialCode":"+383"},{"code":"YE","emoji":"🇾🇪","unicode":"U+1F1FE U+1F1EA","name":"Yemen","title":"flag for Yemen","dialCode":"+967"},{"code":"YT","emoji":"🇾🇹","unicode":"U+1F1FE U+1F1F9","name":"Mayotte","title":"flag for Mayotte","dialCode":"+262"},{"code":"ZA","emoji":"🇿🇦","unicode":"U+1F1FF U+1F1E6","name":"South Africa","title":"flag for South Africa","dialCode":"+27"},{"code":"ZM","emoji":"🇿🇲","unicode":"U+1F1FF U+1F1F2","name":"Zambia","title":"flag for Zambia","dialCode":"+260"},{"code":"ZW","emoji":"🇿🇼","unicode":"U+1F1FF U+1F1FC","name":"Zimbabwe","title":"flag for Zimbabwe","dialCode":"+263"}]');

/***/ })

}]);