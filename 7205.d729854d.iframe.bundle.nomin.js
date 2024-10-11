"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7205],{

/***/ "../../node_modules/.pnpm/array.prototype.flat@1.3.2/node_modules/array.prototype.flat/implementation.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var ArraySpeciesCreate = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ArraySpeciesCreate.js");
var FlattenIntoArray = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/FlattenIntoArray.js");
var Get = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Get.js");
var ToIntegerOrInfinity = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToIntegerOrInfinity.js");
var ToLength = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToLength.js");
var ToObject = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToObject.js");

module.exports = function flat() {
	var O = ToObject(this);
	var sourceLen = ToLength(Get(O, 'length'));

	var depthNum = 1;
	if (arguments.length > 0 && typeof arguments[0] !== 'undefined') {
		depthNum = ToIntegerOrInfinity(arguments[0]);
	}

	var A = ArraySpeciesCreate(O, 0);
	FlattenIntoArray(A, O, sourceLen, 0, depthNum);
	return A;
};


/***/ }),

/***/ "../../node_modules/.pnpm/array.prototype.flat@1.3.2/node_modules/array.prototype.flat/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var define = __webpack_require__("../../node_modules/.pnpm/define-properties@1.2.1/node_modules/define-properties/index.js");
var callBind = __webpack_require__("../../node_modules/.pnpm/call-bind@1.0.5/node_modules/call-bind/index.js");

var implementation = __webpack_require__("../../node_modules/.pnpm/array.prototype.flat@1.3.2/node_modules/array.prototype.flat/implementation.js");
var getPolyfill = __webpack_require__("../../node_modules/.pnpm/array.prototype.flat@1.3.2/node_modules/array.prototype.flat/polyfill.js");
var polyfill = getPolyfill();
var shim = __webpack_require__("../../node_modules/.pnpm/array.prototype.flat@1.3.2/node_modules/array.prototype.flat/shim.js");

var boundFlat = callBind(polyfill);

define(boundFlat, {
	getPolyfill: getPolyfill,
	implementation: implementation,
	shim: shim
});

module.exports = boundFlat;


/***/ }),

/***/ "../../node_modules/.pnpm/array.prototype.flat@1.3.2/node_modules/array.prototype.flat/polyfill.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var implementation = __webpack_require__("../../node_modules/.pnpm/array.prototype.flat@1.3.2/node_modules/array.prototype.flat/implementation.js");

module.exports = function getPolyfill() {
	return Array.prototype.flat || implementation;
};


/***/ }),

/***/ "../../node_modules/.pnpm/array.prototype.flat@1.3.2/node_modules/array.prototype.flat/shim.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var define = __webpack_require__("../../node_modules/.pnpm/define-properties@1.2.1/node_modules/define-properties/index.js");
var shimUnscopables = __webpack_require__("../../node_modules/.pnpm/es-shim-unscopables@1.0.2/node_modules/es-shim-unscopables/index.js");

var getPolyfill = __webpack_require__("../../node_modules/.pnpm/array.prototype.flat@1.3.2/node_modules/array.prototype.flat/polyfill.js");

module.exports = function shimFlat() {
	var polyfill = getPolyfill();

	define(
		Array.prototype,
		{ flat: polyfill },
		{ flat: function () { return Array.prototype.flat !== polyfill; } }
	);

	shimUnscopables('flat');

	return polyfill;
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ArrayCreate.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $ArrayPrototype = GetIntrinsic('%Array.prototype%');
var $RangeError = GetIntrinsic('%RangeError%');
var $SyntaxError = GetIntrinsic('%SyntaxError%');
var $TypeError = GetIntrinsic('%TypeError%');

var isInteger = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isInteger.js");

var MAX_ARRAY_LENGTH = Math.pow(2, 32) - 1;

var hasProto = __webpack_require__("../../node_modules/.pnpm/has-proto@1.0.1/node_modules/has-proto/index.js")();

var $setProto = GetIntrinsic('%Object.setPrototypeOf%', true) || (
	hasProto
		? function (O, proto) {
			O.__proto__ = proto; // eslint-disable-line no-proto, no-param-reassign
			return O;
		}
		: null
);

// https://262.ecma-international.org/12.0/#sec-arraycreate

module.exports = function ArrayCreate(length) {
	if (!isInteger(length) || length < 0) {
		throw new $TypeError('Assertion failed: `length` must be an integer Number >= 0');
	}
	if (length > MAX_ARRAY_LENGTH) {
		throw new $RangeError('length is greater than (2**32 - 1)');
	}
	var proto = arguments.length > 1 ? arguments[1] : $ArrayPrototype;
	var A = []; // steps 3, 5
	if (proto !== $ArrayPrototype) { // step 4
		if (!$setProto) {
			throw new $SyntaxError('ArrayCreate: a `proto` argument that is not `Array.prototype` is not supported in an environment that does not support setting the [[Prototype]]');
		}
		$setProto(A, proto);
	}
	if (length !== 0) { // bypasses the need for step 6
		A.length = length;
	}
	/* step 6, the above as a shortcut for the below
	OrdinaryDefineOwnProperty(A, 'length', {
		'[[Configurable]]': false,
		'[[Enumerable]]': false,
		'[[Value]]': length,
		'[[Writable]]': true
	});
	*/
	return A;
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ArraySpeciesCreate.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $species = GetIntrinsic('%Symbol.species%', true);
var $TypeError = GetIntrinsic('%TypeError%');

var ArrayCreate = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ArrayCreate.js");
var Get = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Get.js");
var IsArray = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsArray.js");
var IsConstructor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsConstructor.js");
var Type = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Type.js");

var isInteger = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isInteger.js");

// https://262.ecma-international.org/12.0/#sec-arrayspeciescreate

module.exports = function ArraySpeciesCreate(originalArray, length) {
	if (!isInteger(length) || length < 0) {
		throw new $TypeError('Assertion failed: length must be an integer >= 0');
	}

	var isArray = IsArray(originalArray);
	if (!isArray) {
		return ArrayCreate(length);
	}

	var C = Get(originalArray, 'constructor');
	// TODO: figure out how to make a cross-realm normal Array, a same-realm Array
	// if (IsConstructor(C)) {
	// 	if C is another realm's Array, C = undefined
	// 	Object.getPrototypeOf(Object.getPrototypeOf(Object.getPrototypeOf(Array))) === null ?
	// }
	if ($species && Type(C) === 'Object') {
		C = Get(C, $species);
		if (C === null) {
			C = void 0;
		}
	}

	if (typeof C === 'undefined') {
		return ArrayCreate(length);
	}
	if (!IsConstructor(C)) {
		throw new $TypeError('C must be a constructor');
	}
	return new C(length); // Construct(C, length);
};



/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Call.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");
var callBound = __webpack_require__("../../node_modules/.pnpm/call-bind@1.0.5/node_modules/call-bind/callBound.js");

var $TypeError = GetIntrinsic('%TypeError%');

var IsArray = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsArray.js");

var $apply = GetIntrinsic('%Reflect.apply%', true) || callBound('Function.prototype.apply');

// https://262.ecma-international.org/6.0/#sec-call

module.exports = function Call(F, V) {
	var argumentsList = arguments.length > 2 ? arguments[2] : [];
	if (!IsArray(argumentsList)) {
		throw new $TypeError('Assertion failed: optional `argumentsList`, if provided, must be a List');
	}
	return $apply(F, V, argumentsList);
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/CreateDataProperty.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $TypeError = GetIntrinsic('%TypeError%');

var IsPropertyKey = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsPropertyKey.js");
var OrdinaryDefineOwnProperty = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/OrdinaryDefineOwnProperty.js");
var Type = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Type.js");

// https://262.ecma-international.org/6.0/#sec-createdataproperty

module.exports = function CreateDataProperty(O, P, V) {
	if (Type(O) !== 'Object') {
		throw new $TypeError('Assertion failed: Type(O) is not Object');
	}
	if (!IsPropertyKey(P)) {
		throw new $TypeError('Assertion failed: IsPropertyKey(P) is not true');
	}
	var newDesc = {
		'[[Configurable]]': true,
		'[[Enumerable]]': true,
		'[[Value]]': V,
		'[[Writable]]': true
	};
	return OrdinaryDefineOwnProperty(O, P, newDesc);
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/CreateDataPropertyOrThrow.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $TypeError = GetIntrinsic('%TypeError%');

var CreateDataProperty = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/CreateDataProperty.js");
var IsPropertyKey = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsPropertyKey.js");
var Type = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Type.js");

// // https://262.ecma-international.org/14.0/#sec-createdatapropertyorthrow

module.exports = function CreateDataPropertyOrThrow(O, P, V) {
	if (Type(O) !== 'Object') {
		throw new $TypeError('Assertion failed: Type(O) is not Object');
	}
	if (!IsPropertyKey(P)) {
		throw new $TypeError('Assertion failed: IsPropertyKey(P) is not true');
	}
	var success = CreateDataProperty(O, P, V);
	if (!success) {
		throw new $TypeError('unable to create data property');
	}
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/DefinePropertyOrThrow.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $TypeError = GetIntrinsic('%TypeError%');

var isPropertyDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isPropertyDescriptor.js");
var DefineOwnProperty = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/DefineOwnProperty.js");

var FromPropertyDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/FromPropertyDescriptor.js");
var IsAccessorDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsAccessorDescriptor.js");
var IsDataDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsDataDescriptor.js");
var IsPropertyKey = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsPropertyKey.js");
var SameValue = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/SameValue.js");
var ToPropertyDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToPropertyDescriptor.js");
var Type = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Type.js");

// https://262.ecma-international.org/6.0/#sec-definepropertyorthrow

module.exports = function DefinePropertyOrThrow(O, P, desc) {
	if (Type(O) !== 'Object') {
		throw new $TypeError('Assertion failed: Type(O) is not Object');
	}

	if (!IsPropertyKey(P)) {
		throw new $TypeError('Assertion failed: IsPropertyKey(P) is not true');
	}

	var Desc = isPropertyDescriptor({
		Type: Type,
		IsDataDescriptor: IsDataDescriptor,
		IsAccessorDescriptor: IsAccessorDescriptor
	}, desc) ? desc : ToPropertyDescriptor(desc);
	if (!isPropertyDescriptor({
		Type: Type,
		IsDataDescriptor: IsDataDescriptor,
		IsAccessorDescriptor: IsAccessorDescriptor
	}, Desc)) {
		throw new $TypeError('Assertion failed: Desc is not a valid Property Descriptor');
	}

	return DefineOwnProperty(
		IsDataDescriptor,
		SameValue,
		FromPropertyDescriptor,
		O,
		P,
		Desc
	);
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/FlattenIntoArray.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $TypeError = GetIntrinsic('%TypeError%');

var MAX_SAFE_INTEGER = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/maxSafeInteger.js");

var Call = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Call.js");
var CreateDataPropertyOrThrow = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/CreateDataPropertyOrThrow.js");
var Get = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Get.js");
var HasProperty = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/HasProperty.js");
var IsArray = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsArray.js");
var LengthOfArrayLike = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/LengthOfArrayLike.js");
var ToString = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToString.js");

// https://262.ecma-international.org/11.0/#sec-flattenintoarray

module.exports = function FlattenIntoArray(target, source, sourceLen, start, depth) {
	var mapperFunction;
	if (arguments.length > 5) {
		mapperFunction = arguments[5];
	}

	var targetIndex = start;
	var sourceIndex = 0;
	while (sourceIndex < sourceLen) {
		var P = ToString(sourceIndex);
		var exists = HasProperty(source, P);
		if (exists === true) {
			var element = Get(source, P);
			if (typeof mapperFunction !== 'undefined') {
				if (arguments.length <= 6) {
					throw new $TypeError('Assertion failed: thisArg is required when mapperFunction is provided');
				}
				element = Call(mapperFunction, arguments[6], [element, sourceIndex, source]);
			}
			var shouldFlatten = false;
			if (depth > 0) {
				shouldFlatten = IsArray(element);
			}
			if (shouldFlatten) {
				var elementLen = LengthOfArrayLike(element);
				targetIndex = FlattenIntoArray(target, element, elementLen, targetIndex, depth - 1);
			} else {
				if (targetIndex >= MAX_SAFE_INTEGER) {
					throw new $TypeError('index too large');
				}
				CreateDataPropertyOrThrow(target, ToString(targetIndex), element);
				targetIndex += 1;
			}
		}
		sourceIndex += 1;
	}

	return targetIndex;
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/FromPropertyDescriptor.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var assertRecord = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/assertRecord.js");
var fromPropertyDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/fromPropertyDescriptor.js");

var Type = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Type.js");

// https://262.ecma-international.org/6.0/#sec-frompropertydescriptor

module.exports = function FromPropertyDescriptor(Desc) {
	if (typeof Desc !== 'undefined') {
		assertRecord(Type, 'Property Descriptor', 'Desc', Desc);
	}

	return fromPropertyDescriptor(Desc);
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Get.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $TypeError = GetIntrinsic('%TypeError%');

var inspect = __webpack_require__("../../node_modules/.pnpm/object-inspect@1.13.1/node_modules/object-inspect/index.js");

var IsPropertyKey = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsPropertyKey.js");
var Type = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Type.js");

// https://262.ecma-international.org/6.0/#sec-get-o-p

module.exports = function Get(O, P) {
	// 7.3.1.1
	if (Type(O) !== 'Object') {
		throw new $TypeError('Assertion failed: Type(O) is not Object');
	}
	// 7.3.1.2
	if (!IsPropertyKey(P)) {
		throw new $TypeError('Assertion failed: IsPropertyKey(P) is not true, got ' + inspect(P));
	}
	// 7.3.1.3
	return O[P];
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/HasProperty.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $TypeError = GetIntrinsic('%TypeError%');

var IsPropertyKey = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsPropertyKey.js");
var Type = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Type.js");

// https://262.ecma-international.org/6.0/#sec-hasproperty

module.exports = function HasProperty(O, P) {
	if (Type(O) !== 'Object') {
		throw new $TypeError('Assertion failed: `O` must be an Object');
	}
	if (!IsPropertyKey(P)) {
		throw new $TypeError('Assertion failed: `P` must be a Property Key');
	}
	return P in O;
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsAccessorDescriptor.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var hasOwn = __webpack_require__("../../node_modules/.pnpm/hasown@2.0.0/node_modules/hasown/index.js");

var Type = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Type.js");

var assertRecord = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/assertRecord.js");

// https://262.ecma-international.org/5.1/#sec-8.10.1

module.exports = function IsAccessorDescriptor(Desc) {
	if (typeof Desc === 'undefined') {
		return false;
	}

	assertRecord(Type, 'Property Descriptor', 'Desc', Desc);

	if (!hasOwn(Desc, '[[Get]]') && !hasOwn(Desc, '[[Set]]')) {
		return false;
	}

	return true;
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsArray.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



// https://262.ecma-international.org/6.0/#sec-isarray
module.exports = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/IsArray.js");


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsCallable.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



// http://262.ecma-international.org/5.1/#sec-9.11

module.exports = __webpack_require__("../../node_modules/.pnpm/is-callable@1.2.7/node_modules/is-callable/index.js");


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsConstructor.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/GetIntrinsic.js");

var $construct = GetIntrinsic('%Reflect.construct%', true);

var DefinePropertyOrThrow = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/DefinePropertyOrThrow.js");
try {
	DefinePropertyOrThrow({}, '', { '[[Get]]': function () {} });
} catch (e) {
	// Accessor properties aren't supported
	DefinePropertyOrThrow = null;
}

// https://262.ecma-international.org/6.0/#sec-isconstructor

if (DefinePropertyOrThrow && $construct) {
	var isConstructorMarker = {};
	var badArrayLike = {};
	DefinePropertyOrThrow(badArrayLike, 'length', {
		'[[Get]]': function () {
			throw isConstructorMarker;
		},
		'[[Enumerable]]': true
	});

	module.exports = function IsConstructor(argument) {
		try {
			// `Reflect.construct` invokes `IsConstructor(target)` before `Get(args, 'length')`:
			$construct(argument, badArrayLike);
		} catch (err) {
			return err === isConstructorMarker;
		}
	};
} else {
	module.exports = function IsConstructor(argument) {
		// unfortunately there's no way to truly check this without try/catch `new argument` in old environments
		return typeof argument === 'function' && !!argument.prototype;
	};
}


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsDataDescriptor.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var hasOwn = __webpack_require__("../../node_modules/.pnpm/hasown@2.0.0/node_modules/hasown/index.js");

var Type = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Type.js");

var assertRecord = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/assertRecord.js");

// https://262.ecma-international.org/5.1/#sec-8.10.2

module.exports = function IsDataDescriptor(Desc) {
	if (typeof Desc === 'undefined') {
		return false;
	}

	assertRecord(Type, 'Property Descriptor', 'Desc', Desc);

	if (!hasOwn(Desc, '[[Value]]') && !hasOwn(Desc, '[[Writable]]')) {
		return false;
	}

	return true;
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsExtensible.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $preventExtensions = GetIntrinsic('%Object.preventExtensions%', true);
var $isExtensible = GetIntrinsic('%Object.isExtensible%', true);

var isPrimitive = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isPrimitive.js");

// https://262.ecma-international.org/6.0/#sec-isextensible-o

module.exports = $preventExtensions
	? function IsExtensible(obj) {
		return !isPrimitive(obj) && $isExtensible(obj);
	}
	: function IsExtensible(obj) {
		return !isPrimitive(obj);
	};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsGenericDescriptor.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var assertRecord = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/assertRecord.js");

var IsAccessorDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsAccessorDescriptor.js");
var IsDataDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsDataDescriptor.js");
var Type = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Type.js");

// https://262.ecma-international.org/6.0/#sec-isgenericdescriptor

module.exports = function IsGenericDescriptor(Desc) {
	if (typeof Desc === 'undefined') {
		return false;
	}

	assertRecord(Type, 'Property Descriptor', 'Desc', Desc);

	if (!IsAccessorDescriptor(Desc) && !IsDataDescriptor(Desc)) {
		return true;
	}

	return false;
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsPropertyKey.js":
/***/ ((module) => {



// https://262.ecma-international.org/6.0/#sec-ispropertykey

module.exports = function IsPropertyKey(argument) {
	return typeof argument === 'string' || typeof argument === 'symbol';
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/LengthOfArrayLike.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $TypeError = GetIntrinsic('%TypeError%');

var Get = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Get.js");
var ToLength = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToLength.js");
var Type = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Type.js");

// https://262.ecma-international.org/11.0/#sec-lengthofarraylike

module.exports = function LengthOfArrayLike(obj) {
	if (Type(obj) !== 'Object') {
		throw new $TypeError('Assertion failed: `obj` must be an Object');
	}
	return ToLength(Get(obj, 'length'));
};

// TODO: use this all over


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/OrdinaryDefineOwnProperty.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $gOPD = __webpack_require__("../../node_modules/.pnpm/gopd@1.0.1/node_modules/gopd/index.js");
var $SyntaxError = GetIntrinsic('%SyntaxError%');
var $TypeError = GetIntrinsic('%TypeError%');

var isPropertyDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isPropertyDescriptor.js");

var IsAccessorDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsAccessorDescriptor.js");
var IsDataDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsDataDescriptor.js");
var IsExtensible = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsExtensible.js");
var IsPropertyKey = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsPropertyKey.js");
var ToPropertyDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToPropertyDescriptor.js");
var SameValue = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/SameValue.js");
var Type = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Type.js");
var ValidateAndApplyPropertyDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ValidateAndApplyPropertyDescriptor.js");

// https://262.ecma-international.org/6.0/#sec-ordinarydefineownproperty

module.exports = function OrdinaryDefineOwnProperty(O, P, Desc) {
	if (Type(O) !== 'Object') {
		throw new $TypeError('Assertion failed: O must be an Object');
	}
	if (!IsPropertyKey(P)) {
		throw new $TypeError('Assertion failed: P must be a Property Key');
	}
	if (!isPropertyDescriptor({
		Type: Type,
		IsDataDescriptor: IsDataDescriptor,
		IsAccessorDescriptor: IsAccessorDescriptor
	}, Desc)) {
		throw new $TypeError('Assertion failed: Desc must be a Property Descriptor');
	}
	if (!$gOPD) {
		// ES3/IE 8 fallback
		if (IsAccessorDescriptor(Desc)) {
			throw new $SyntaxError('This environment does not support accessor property descriptors.');
		}
		var creatingNormalDataProperty = !(P in O)
			&& Desc['[[Writable]]']
			&& Desc['[[Enumerable]]']
			&& Desc['[[Configurable]]']
			&& '[[Value]]' in Desc;
		var settingExistingDataProperty = (P in O)
			&& (!('[[Configurable]]' in Desc) || Desc['[[Configurable]]'])
			&& (!('[[Enumerable]]' in Desc) || Desc['[[Enumerable]]'])
			&& (!('[[Writable]]' in Desc) || Desc['[[Writable]]'])
			&& '[[Value]]' in Desc;
		if (creatingNormalDataProperty || settingExistingDataProperty) {
			O[P] = Desc['[[Value]]']; // eslint-disable-line no-param-reassign
			return SameValue(O[P], Desc['[[Value]]']);
		}
		throw new $SyntaxError('This environment does not support defining non-writable, non-enumerable, or non-configurable properties');
	}
	var desc = $gOPD(O, P);
	var current = desc && ToPropertyDescriptor(desc);
	var extensible = IsExtensible(O);
	return ValidateAndApplyPropertyDescriptor(O, P, extensible, Desc, current);
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/SameValue.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var $isNaN = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isNaN.js");

// http://262.ecma-international.org/5.1/#sec-9.12

module.exports = function SameValue(x, y) {
	if (x === y) { // 0 === -0, but they are not identical.
		if (x === 0) { return 1 / x === 1 / y; }
		return true;
	}
	return $isNaN(x) && $isNaN(y);
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/StringToNumber.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $Number = GetIntrinsic('%Number%');
var $RegExp = GetIntrinsic('%RegExp%');
var $TypeError = GetIntrinsic('%TypeError%');
var $parseInteger = GetIntrinsic('%parseInt%');

var callBound = __webpack_require__("../../node_modules/.pnpm/call-bind@1.0.5/node_modules/call-bind/callBound.js");
var regexTester = __webpack_require__("../../node_modules/.pnpm/safe-regex-test@1.0.0/node_modules/safe-regex-test/index.js");

var $strSlice = callBound('String.prototype.slice');
var isBinary = regexTester(/^0b[01]+$/i);
var isOctal = regexTester(/^0o[0-7]+$/i);
var isInvalidHexLiteral = regexTester(/^[-+]0x[0-9a-f]+$/i);
var nonWS = ['\u0085', '\u200b', '\ufffe'].join('');
var nonWSregex = new $RegExp('[' + nonWS + ']', 'g');
var hasNonWS = regexTester(nonWSregex);

var $trim = __webpack_require__("../../node_modules/.pnpm/string.prototype.trim@1.2.8/node_modules/string.prototype.trim/index.js");

var Type = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Type.js");

// https://262.ecma-international.org/13.0/#sec-stringtonumber

module.exports = function StringToNumber(argument) {
	if (Type(argument) !== 'String') {
		throw new $TypeError('Assertion failed: `argument` is not a String');
	}
	if (isBinary(argument)) {
		return $Number($parseInteger($strSlice(argument, 2), 2));
	}
	if (isOctal(argument)) {
		return $Number($parseInteger($strSlice(argument, 2), 8));
	}
	if (hasNonWS(argument) || isInvalidHexLiteral(argument)) {
		return NaN;
	}
	var trimmed = $trim(argument);
	if (trimmed !== argument) {
		return StringToNumber(trimmed);
	}
	return $Number(argument);
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToBoolean.js":
/***/ ((module) => {



// http://262.ecma-international.org/5.1/#sec-9.2

module.exports = function ToBoolean(value) { return !!value; };


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToIntegerOrInfinity.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var ToNumber = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToNumber.js");
var truncate = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/truncate.js");

var $isNaN = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isNaN.js");
var $isFinite = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isFinite.js");

// https://262.ecma-international.org/14.0/#sec-tointegerorinfinity

module.exports = function ToIntegerOrInfinity(value) {
	var number = ToNumber(value);
	if ($isNaN(number) || number === 0) { return 0; }
	if (!$isFinite(number)) { return number; }
	return truncate(number);
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToLength.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var MAX_SAFE_INTEGER = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/maxSafeInteger.js");

var ToIntegerOrInfinity = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToIntegerOrInfinity.js");

module.exports = function ToLength(argument) {
	var len = ToIntegerOrInfinity(argument);
	if (len <= 0) { return 0; } // includes converting -0 to +0
	if (len > MAX_SAFE_INTEGER) { return MAX_SAFE_INTEGER; }
	return len;
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToNumber.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $TypeError = GetIntrinsic('%TypeError%');
var $Number = GetIntrinsic('%Number%');
var isPrimitive = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isPrimitive.js");

var ToPrimitive = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToPrimitive.js");
var StringToNumber = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/StringToNumber.js");

// https://262.ecma-international.org/13.0/#sec-tonumber

module.exports = function ToNumber(argument) {
	var value = isPrimitive(argument) ? argument : ToPrimitive(argument, $Number);
	if (typeof value === 'symbol') {
		throw new $TypeError('Cannot convert a Symbol value to a number');
	}
	if (typeof value === 'bigint') {
		throw new $TypeError('Conversion from \'BigInt\' to \'number\' is not allowed.');
	}
	if (typeof value === 'string') {
		return StringToNumber(value);
	}
	return $Number(value);
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToObject.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $Object = GetIntrinsic('%Object%');

var RequireObjectCoercible = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/RequireObjectCoercible.js");

// https://262.ecma-international.org/6.0/#sec-toobject

module.exports = function ToObject(value) {
	RequireObjectCoercible(value);
	return $Object(value);
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToPrimitive.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var toPrimitive = __webpack_require__("../../node_modules/.pnpm/es-to-primitive@1.2.1/node_modules/es-to-primitive/es2015.js");

// https://262.ecma-international.org/6.0/#sec-toprimitive

module.exports = function ToPrimitive(input) {
	if (arguments.length > 1) {
		return toPrimitive(input, arguments[1]);
	}
	return toPrimitive(input);
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToPropertyDescriptor.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var hasOwn = __webpack_require__("../../node_modules/.pnpm/hasown@2.0.0/node_modules/hasown/index.js");

var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $TypeError = GetIntrinsic('%TypeError%');

var Type = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Type.js");
var ToBoolean = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToBoolean.js");
var IsCallable = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsCallable.js");

// https://262.ecma-international.org/5.1/#sec-8.10.5

module.exports = function ToPropertyDescriptor(Obj) {
	if (Type(Obj) !== 'Object') {
		throw new $TypeError('ToPropertyDescriptor requires an object');
	}

	var desc = {};
	if (hasOwn(Obj, 'enumerable')) {
		desc['[[Enumerable]]'] = ToBoolean(Obj.enumerable);
	}
	if (hasOwn(Obj, 'configurable')) {
		desc['[[Configurable]]'] = ToBoolean(Obj.configurable);
	}
	if (hasOwn(Obj, 'value')) {
		desc['[[Value]]'] = Obj.value;
	}
	if (hasOwn(Obj, 'writable')) {
		desc['[[Writable]]'] = ToBoolean(Obj.writable);
	}
	if (hasOwn(Obj, 'get')) {
		var getter = Obj.get;
		if (typeof getter !== 'undefined' && !IsCallable(getter)) {
			throw new $TypeError('getter must be a function');
		}
		desc['[[Get]]'] = getter;
	}
	if (hasOwn(Obj, 'set')) {
		var setter = Obj.set;
		if (typeof setter !== 'undefined' && !IsCallable(setter)) {
			throw new $TypeError('setter must be a function');
		}
		desc['[[Set]]'] = setter;
	}

	if ((hasOwn(desc, '[[Get]]') || hasOwn(desc, '[[Set]]')) && (hasOwn(desc, '[[Value]]') || hasOwn(desc, '[[Writable]]'))) {
		throw new $TypeError('Invalid property descriptor. Cannot both specify accessors and a value or writable attribute');
	}
	return desc;
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToString.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $String = GetIntrinsic('%String%');
var $TypeError = GetIntrinsic('%TypeError%');

// https://262.ecma-international.org/6.0/#sec-tostring

module.exports = function ToString(argument) {
	if (typeof argument === 'symbol') {
		throw new $TypeError('Cannot convert a Symbol value to a string');
	}
	return $String(argument);
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Type.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var ES5Type = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/5/Type.js");

// https://262.ecma-international.org/11.0/#sec-ecmascript-data-types-and-values

module.exports = function Type(x) {
	if (typeof x === 'symbol') {
		return 'Symbol';
	}
	if (typeof x === 'bigint') {
		return 'BigInt';
	}
	return ES5Type(x);
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ValidateAndApplyPropertyDescriptor.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $TypeError = GetIntrinsic('%TypeError%');

var DefineOwnProperty = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/DefineOwnProperty.js");
var isFullyPopulatedPropertyDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isFullyPopulatedPropertyDescriptor.js");
var isPropertyDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isPropertyDescriptor.js");

var FromPropertyDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/FromPropertyDescriptor.js");
var IsAccessorDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsAccessorDescriptor.js");
var IsDataDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsDataDescriptor.js");
var IsGenericDescriptor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsGenericDescriptor.js");
var IsPropertyKey = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/IsPropertyKey.js");
var SameValue = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/SameValue.js");
var Type = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Type.js");

// https://262.ecma-international.org/13.0/#sec-validateandapplypropertydescriptor

// see https://github.com/tc39/ecma262/pull/2468 for ES2022 changes

// eslint-disable-next-line max-lines-per-function, max-statements
module.exports = function ValidateAndApplyPropertyDescriptor(O, P, extensible, Desc, current) {
	var oType = Type(O);
	if (oType !== 'Undefined' && oType !== 'Object') {
		throw new $TypeError('Assertion failed: O must be undefined or an Object');
	}
	if (!IsPropertyKey(P)) {
		throw new $TypeError('Assertion failed: P must be a Property Key');
	}
	if (Type(extensible) !== 'Boolean') {
		throw new $TypeError('Assertion failed: extensible must be a Boolean');
	}
	if (!isPropertyDescriptor({
		Type: Type,
		IsDataDescriptor: IsDataDescriptor,
		IsAccessorDescriptor: IsAccessorDescriptor
	}, Desc)) {
		throw new $TypeError('Assertion failed: Desc must be a Property Descriptor');
	}
	if (Type(current) !== 'Undefined' && !isPropertyDescriptor({
		Type: Type,
		IsDataDescriptor: IsDataDescriptor,
		IsAccessorDescriptor: IsAccessorDescriptor
	}, current)) {
		throw new $TypeError('Assertion failed: current must be a Property Descriptor, or undefined');
	}

	if (Type(current) === 'Undefined') { // step 2
		if (!extensible) {
			return false; // step 2.a
		}
		if (oType === 'Undefined') {
			return true; // step 2.b
		}
		if (IsAccessorDescriptor(Desc)) { // step 2.c
			return DefineOwnProperty(
				IsDataDescriptor,
				SameValue,
				FromPropertyDescriptor,
				O,
				P,
				Desc
			);
		}
		// step 2.d
		return DefineOwnProperty(
			IsDataDescriptor,
			SameValue,
			FromPropertyDescriptor,
			O,
			P,
			{
				'[[Configurable]]': !!Desc['[[Configurable]]'],
				'[[Enumerable]]': !!Desc['[[Enumerable]]'],
				'[[Value]]': Desc['[[Value]]'],
				'[[Writable]]': !!Desc['[[Writable]]']
			}
		);
	}

	// 3. Assert: current is a fully populated Property Descriptor.
	if (!isFullyPopulatedPropertyDescriptor({
		IsAccessorDescriptor: IsAccessorDescriptor,
		IsDataDescriptor: IsDataDescriptor
	}, current)) {
		throw new $TypeError('`current`, when present, must be a fully populated and valid Property Descriptor');
	}

	// 4. If every field in Desc is absent, return true.
	// this can't really match the assertion that it's a Property Descriptor in our JS implementation

	// 5. If current.[[Configurable]] is false, then
	if (!current['[[Configurable]]']) {
		if ('[[Configurable]]' in Desc && Desc['[[Configurable]]']) {
			// step 5.a
			return false;
		}
		if ('[[Enumerable]]' in Desc && !SameValue(Desc['[[Enumerable]]'], current['[[Enumerable]]'])) {
			// step 5.b
			return false;
		}
		if (!IsGenericDescriptor(Desc) && !SameValue(IsAccessorDescriptor(Desc), IsAccessorDescriptor(current))) {
			// step 5.c
			return false;
		}
		if (IsAccessorDescriptor(current)) { // step 5.d
			if ('[[Get]]' in Desc && !SameValue(Desc['[[Get]]'], current['[[Get]]'])) {
				return false;
			}
			if ('[[Set]]' in Desc && !SameValue(Desc['[[Set]]'], current['[[Set]]'])) {
				return false;
			}
		} else if (!current['[[Writable]]']) { // step 5.e
			if ('[[Writable]]' in Desc && Desc['[[Writable]]']) {
				return false;
			}
			if ('[[Value]]' in Desc && !SameValue(Desc['[[Value]]'], current['[[Value]]'])) {
				return false;
			}
		}
	}

	// 6. If O is not undefined, then
	if (oType !== 'Undefined') {
		var configurable;
		var enumerable;
		if (IsDataDescriptor(current) && IsAccessorDescriptor(Desc)) { // step 6.a
			configurable = ('[[Configurable]]' in Desc ? Desc : current)['[[Configurable]]'];
			enumerable = ('[[Enumerable]]' in Desc ? Desc : current)['[[Enumerable]]'];
			// Replace the property named P of object O with an accessor property having [[Configurable]] and [[Enumerable]] attributes as described by current and each other attribute set to its default value.
			return DefineOwnProperty(
				IsDataDescriptor,
				SameValue,
				FromPropertyDescriptor,
				O,
				P,
				{
					'[[Configurable]]': !!configurable,
					'[[Enumerable]]': !!enumerable,
					'[[Get]]': ('[[Get]]' in Desc ? Desc : current)['[[Get]]'],
					'[[Set]]': ('[[Set]]' in Desc ? Desc : current)['[[Set]]']
				}
			);
		} else if (IsAccessorDescriptor(current) && IsDataDescriptor(Desc)) {
			configurable = ('[[Configurable]]' in Desc ? Desc : current)['[[Configurable]]'];
			enumerable = ('[[Enumerable]]' in Desc ? Desc : current)['[[Enumerable]]'];
			// i. Replace the property named P of object O with a data property having [[Configurable]] and [[Enumerable]] attributes as described by current and each other attribute set to its default value.
			return DefineOwnProperty(
				IsDataDescriptor,
				SameValue,
				FromPropertyDescriptor,
				O,
				P,
				{
					'[[Configurable]]': !!configurable,
					'[[Enumerable]]': !!enumerable,
					'[[Value]]': ('[[Value]]' in Desc ? Desc : current)['[[Value]]'],
					'[[Writable]]': !!('[[Writable]]' in Desc ? Desc : current)['[[Writable]]']
				}
			);
		}

		// For each field of Desc that is present, set the corresponding attribute of the property named P of object O to the value of the field.
		return DefineOwnProperty(
			IsDataDescriptor,
			SameValue,
			FromPropertyDescriptor,
			O,
			P,
			Desc
		);
	}

	return true; // step 7
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/floor.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var Type = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/Type.js");

// var modulo = require('./modulo');
var $floor = Math.floor;

// http://262.ecma-international.org/11.0/#eqn-floor

module.exports = function floor(x) {
	// return x - modulo(x, 1);
	if (Type(x) === 'BigInt') {
		return x;
	}
	return $floor(x);
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/truncate.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var floor = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/floor.js");

var $TypeError = GetIntrinsic('%TypeError%');

// https://262.ecma-international.org/14.0/#eqn-truncate

module.exports = function truncate(x) {
	if (typeof x !== 'number' && typeof x !== 'bigint') {
		throw new $TypeError('argument must be a Number or a BigInt');
	}
	var result = x < 0 ? -floor(-x) : floor(x);
	return result === 0 ? 0 : result; // in the spec, these are math values, so we filter out -0 here
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/5/Type.js":
/***/ ((module) => {



// https://262.ecma-international.org/5.1/#sec-8

module.exports = function Type(x) {
	if (x === null) {
		return 'Null';
	}
	if (typeof x === 'undefined') {
		return 'Undefined';
	}
	if (typeof x === 'function' || typeof x === 'object') {
		return 'Object';
	}
	if (typeof x === 'number') {
		return 'Number';
	}
	if (typeof x === 'boolean') {
		return 'Boolean';
	}
	if (typeof x === 'string') {
		return 'String';
	}
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/GetIntrinsic.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



// TODO: remove, semver-major

module.exports = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/DefineOwnProperty.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var hasPropertyDescriptors = __webpack_require__("../../node_modules/.pnpm/has-property-descriptors@1.0.1/node_modules/has-property-descriptors/index.js");

var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $defineProperty = hasPropertyDescriptors() && GetIntrinsic('%Object.defineProperty%', true);

var hasArrayLengthDefineBug = hasPropertyDescriptors.hasArrayLengthDefineBug();

// eslint-disable-next-line global-require
var isArray = hasArrayLengthDefineBug && __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/IsArray.js");

var callBound = __webpack_require__("../../node_modules/.pnpm/call-bind@1.0.5/node_modules/call-bind/callBound.js");

var $isEnumerable = callBound('Object.prototype.propertyIsEnumerable');

// eslint-disable-next-line max-params
module.exports = function DefineOwnProperty(IsDataDescriptor, SameValue, FromPropertyDescriptor, O, P, desc) {
	if (!$defineProperty) {
		if (!IsDataDescriptor(desc)) {
			// ES3 does not support getters/setters
			return false;
		}
		if (!desc['[[Configurable]]'] || !desc['[[Writable]]']) {
			return false;
		}

		// fallback for ES3
		if (P in O && $isEnumerable(O, P) !== !!desc['[[Enumerable]]']) {
			// a non-enumerable existing property
			return false;
		}

		// property does not exist at all, or exists but is enumerable
		var V = desc['[[Value]]'];
		// eslint-disable-next-line no-param-reassign
		O[P] = V; // will use [[Define]]
		return SameValue(O[P], V);
	}
	if (
		hasArrayLengthDefineBug
		&& P === 'length'
		&& '[[Value]]' in desc
		&& isArray(O)
		&& O.length !== desc['[[Value]]']
	) {
		// eslint-disable-next-line no-param-reassign
		O.length = desc['[[Value]]'];
		return O.length === desc['[[Value]]'];
	}

	$defineProperty(O, P, FromPropertyDescriptor(desc));
	return true;
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/IsArray.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $Array = GetIntrinsic('%Array%');

// eslint-disable-next-line global-require
var toStr = !$Array.isArray && __webpack_require__("../../node_modules/.pnpm/call-bind@1.0.5/node_modules/call-bind/callBound.js")('Object.prototype.toString');

module.exports = $Array.isArray || function IsArray(argument) {
	return toStr(argument) === '[object Array]';
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/assertRecord.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $TypeError = GetIntrinsic('%TypeError%');
var $SyntaxError = GetIntrinsic('%SyntaxError%');

var hasOwn = __webpack_require__("../../node_modules/.pnpm/hasown@2.0.0/node_modules/hasown/index.js");
var isInteger = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isInteger.js");

var isMatchRecord = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isMatchRecord.js");

var predicates = {
	// https://262.ecma-international.org/6.0/#sec-property-descriptor-specification-type
	'Property Descriptor': function isPropertyDescriptor(Desc) {
		var allowed = {
			'[[Configurable]]': true,
			'[[Enumerable]]': true,
			'[[Get]]': true,
			'[[Set]]': true,
			'[[Value]]': true,
			'[[Writable]]': true
		};

		if (!Desc) {
			return false;
		}
		for (var key in Desc) { // eslint-disable-line
			if (hasOwn(Desc, key) && !allowed[key]) {
				return false;
			}
		}

		var isData = hasOwn(Desc, '[[Value]]');
		var IsAccessor = hasOwn(Desc, '[[Get]]') || hasOwn(Desc, '[[Set]]');
		if (isData && IsAccessor) {
			throw new $TypeError('Property Descriptors may not be both accessor and data descriptors');
		}
		return true;
	},
	// https://262.ecma-international.org/13.0/#sec-match-records
	'Match Record': isMatchRecord,
	'Iterator Record': function isIteratorRecord(value) {
		return hasOwn(value, '[[Iterator]]') && hasOwn(value, '[[NextMethod]]') && hasOwn(value, '[[Done]]');
	},
	'PromiseCapability Record': function isPromiseCapabilityRecord(value) {
		return !!value
			&& hasOwn(value, '[[Resolve]]')
			&& typeof value['[[Resolve]]'] === 'function'
			&& hasOwn(value, '[[Reject]]')
			&& typeof value['[[Reject]]'] === 'function'
			&& hasOwn(value, '[[Promise]]')
			&& value['[[Promise]]']
			&& typeof value['[[Promise]]'].then === 'function';
	},
	'AsyncGeneratorRequest Record': function isAsyncGeneratorRequestRecord(value) {
		return !!value
			&& hasOwn(value, '[[Completion]]') // TODO: confirm is a completion record
			&& hasOwn(value, '[[Capability]]')
			&& predicates['PromiseCapability Record'](value['[[Capability]]']);
	},
	'RegExp Record': function isRegExpRecord(value) {
		return value
			&& hasOwn(value, '[[IgnoreCase]]')
			&& typeof value['[[IgnoreCase]]'] === 'boolean'
			&& hasOwn(value, '[[Multiline]]')
			&& typeof value['[[Multiline]]'] === 'boolean'
			&& hasOwn(value, '[[DotAll]]')
			&& typeof value['[[DotAll]]'] === 'boolean'
			&& hasOwn(value, '[[Unicode]]')
			&& typeof value['[[Unicode]]'] === 'boolean'
			&& hasOwn(value, '[[CapturingGroupsCount]]')
			&& typeof value['[[CapturingGroupsCount]]'] === 'number'
			&& isInteger(value['[[CapturingGroupsCount]]'])
			&& value['[[CapturingGroupsCount]]'] >= 0;
	}
};

module.exports = function assertRecord(Type, recordType, argumentName, value) {
	var predicate = predicates[recordType];
	if (typeof predicate !== 'function') {
		throw new $SyntaxError('unknown record type: ' + recordType);
	}
	if (Type(value) !== 'Object' || !predicate(value)) {
		throw new $TypeError(argumentName + ' must be a ' + recordType);
	}
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/fromPropertyDescriptor.js":
/***/ ((module) => {



module.exports = function fromPropertyDescriptor(Desc) {
	if (typeof Desc === 'undefined') {
		return Desc;
	}
	var obj = {};
	if ('[[Value]]' in Desc) {
		obj.value = Desc['[[Value]]'];
	}
	if ('[[Writable]]' in Desc) {
		obj.writable = !!Desc['[[Writable]]'];
	}
	if ('[[Get]]' in Desc) {
		obj.get = Desc['[[Get]]'];
	}
	if ('[[Set]]' in Desc) {
		obj.set = Desc['[[Set]]'];
	}
	if ('[[Enumerable]]' in Desc) {
		obj.enumerable = !!Desc['[[Enumerable]]'];
	}
	if ('[[Configurable]]' in Desc) {
		obj.configurable = !!Desc['[[Configurable]]'];
	}
	return obj;
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isFinite.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var $isNaN = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isNaN.js");

module.exports = function (x) { return (typeof x === 'number' || typeof x === 'bigint') && !$isNaN(x) && x !== Infinity && x !== -Infinity; };


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isFullyPopulatedPropertyDescriptor.js":
/***/ ((module) => {



module.exports = function isFullyPopulatedPropertyDescriptor(ES, Desc) {
	return !!Desc
		&& typeof Desc === 'object'
		&& '[[Enumerable]]' in Desc
		&& '[[Configurable]]' in Desc
		&& (ES.IsAccessorDescriptor(Desc) || ES.IsDataDescriptor(Desc));
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isInteger.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var $abs = GetIntrinsic('%Math.abs%');
var $floor = GetIntrinsic('%Math.floor%');

var $isNaN = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isNaN.js");
var $isFinite = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isFinite.js");

module.exports = function isInteger(argument) {
	if (typeof argument !== 'number' || $isNaN(argument) || !$isFinite(argument)) {
		return false;
	}
	var absValue = $abs(argument);
	return $floor(absValue) === absValue;
};



/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isMatchRecord.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var hasOwn = __webpack_require__("../../node_modules/.pnpm/hasown@2.0.0/node_modules/hasown/index.js");

// https://262.ecma-international.org/13.0/#sec-match-records

module.exports = function isMatchRecord(record) {
	return (
		hasOwn(record, '[[StartIndex]]')
        && hasOwn(record, '[[EndIndex]]')
        && record['[[StartIndex]]'] >= 0
        && record['[[EndIndex]]'] >= record['[[StartIndex]]']
        && String(parseInt(record['[[StartIndex]]'], 10)) === String(record['[[StartIndex]]'])
        && String(parseInt(record['[[EndIndex]]'], 10)) === String(record['[[EndIndex]]'])
	);
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isNaN.js":
/***/ ((module) => {



module.exports = Number.isNaN || function isNaN(a) {
	return a !== a;
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isPrimitive.js":
/***/ ((module) => {



module.exports = function isPrimitive(value) {
	return value === null || (typeof value !== 'function' && typeof value !== 'object');
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/isPropertyDescriptor.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");

var hasOwn = __webpack_require__("../../node_modules/.pnpm/hasown@2.0.0/node_modules/hasown/index.js");
var $TypeError = GetIntrinsic('%TypeError%');

module.exports = function IsPropertyDescriptor(ES, Desc) {
	if (ES.Type(Desc) !== 'Object') {
		return false;
	}
	var allowed = {
		'[[Configurable]]': true,
		'[[Enumerable]]': true,
		'[[Get]]': true,
		'[[Set]]': true,
		'[[Value]]': true,
		'[[Writable]]': true
	};

	for (var key in Desc) { // eslint-disable-line no-restricted-syntax
		if (hasOwn(Desc, key) && !allowed[key]) {
			return false;
		}
	}

	if (ES.IsDataDescriptor(Desc) && ES.IsAccessorDescriptor(Desc)) {
		throw new $TypeError('Property Descriptors may not be both accessor and data descriptors');
	}
	return true;
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/helpers/maxSafeInteger.js":
/***/ ((module) => {



module.exports = Number.MAX_SAFE_INTEGER || 9007199254740991; // Math.pow(2, 53) - 1;


/***/ }),

/***/ "../../node_modules/.pnpm/es-shim-unscopables@1.0.2/node_modules/es-shim-unscopables/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var hasOwn = __webpack_require__("../../node_modules/.pnpm/hasown@2.0.0/node_modules/hasown/index.js");

var hasUnscopables = typeof Symbol === 'function' && typeof Symbol.unscopables === 'symbol';

var map = hasUnscopables && Array.prototype[Symbol.unscopables];

var $TypeError = TypeError;

module.exports = function shimUnscopables(method) {
	if (typeof method !== 'string' || !method) {
		throw new $TypeError('method must be a non-empty string');
	}
	if (!hasOwn(Array.prototype, method)) {
		throw new $TypeError('method must be on Array.prototype');
	}
	if (hasUnscopables) {
		map[method] = true;
	}
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-to-primitive@1.2.1/node_modules/es-to-primitive/es2015.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var hasSymbols = typeof Symbol === 'function' && typeof Symbol.iterator === 'symbol';

var isPrimitive = __webpack_require__("../../node_modules/.pnpm/es-to-primitive@1.2.1/node_modules/es-to-primitive/helpers/isPrimitive.js");
var isCallable = __webpack_require__("../../node_modules/.pnpm/is-callable@1.2.7/node_modules/is-callable/index.js");
var isDate = __webpack_require__("../../node_modules/.pnpm/is-date-object@1.0.5/node_modules/is-date-object/index.js");
var isSymbol = __webpack_require__("../../node_modules/.pnpm/is-symbol@1.0.4/node_modules/is-symbol/index.js");

var ordinaryToPrimitive = function OrdinaryToPrimitive(O, hint) {
	if (typeof O === 'undefined' || O === null) {
		throw new TypeError('Cannot call method on ' + O);
	}
	if (typeof hint !== 'string' || (hint !== 'number' && hint !== 'string')) {
		throw new TypeError('hint must be "string" or "number"');
	}
	var methodNames = hint === 'string' ? ['toString', 'valueOf'] : ['valueOf', 'toString'];
	var method, result, i;
	for (i = 0; i < methodNames.length; ++i) {
		method = O[methodNames[i]];
		if (isCallable(method)) {
			result = method.call(O);
			if (isPrimitive(result)) {
				return result;
			}
		}
	}
	throw new TypeError('No default value');
};

var GetMethod = function GetMethod(O, P) {
	var func = O[P];
	if (func !== null && typeof func !== 'undefined') {
		if (!isCallable(func)) {
			throw new TypeError(func + ' returned for property ' + P + ' of object ' + O + ' is not a function');
		}
		return func;
	}
	return void 0;
};

// http://www.ecma-international.org/ecma-262/6.0/#sec-toprimitive
module.exports = function ToPrimitive(input) {
	if (isPrimitive(input)) {
		return input;
	}
	var hint = 'default';
	if (arguments.length > 1) {
		if (arguments[1] === String) {
			hint = 'string';
		} else if (arguments[1] === Number) {
			hint = 'number';
		}
	}

	var exoticToPrim;
	if (hasSymbols) {
		if (Symbol.toPrimitive) {
			exoticToPrim = GetMethod(input, Symbol.toPrimitive);
		} else if (isSymbol(input)) {
			exoticToPrim = Symbol.prototype.valueOf;
		}
	}
	if (typeof exoticToPrim !== 'undefined') {
		var result = exoticToPrim.call(input, hint);
		if (isPrimitive(result)) {
			return result;
		}
		throw new TypeError('unable to convert exotic object to primitive');
	}
	if (hint === 'default' && (isDate(input) || isSymbol(input))) {
		hint = 'string';
	}
	return ordinaryToPrimitive(input, hint === 'default' ? 'number' : hint);
};


/***/ }),

/***/ "../../node_modules/.pnpm/es-to-primitive@1.2.1/node_modules/es-to-primitive/helpers/isPrimitive.js":
/***/ ((module) => {



module.exports = function isPrimitive(value) {
	return value === null || (typeof value !== 'function' && typeof value !== 'object');
};


/***/ }),

/***/ "../../node_modules/.pnpm/global-cache@1.2.1/node_modules/global-cache/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var define = __webpack_require__("../../node_modules/.pnpm/define-properties@1.2.1/node_modules/define-properties/index.js");
var isSymbol = __webpack_require__("../../node_modules/.pnpm/is-symbol@1.0.4/node_modules/is-symbol/index.js");

var globalKey = '__ global cache key __';
/* istanbul ignore else */
// eslint-disable-next-line no-restricted-properties
if (typeof Symbol === 'function' && isSymbol(Symbol('foo')) && typeof Symbol['for'] === 'function') {
	// eslint-disable-next-line no-restricted-properties
	globalKey = Symbol['for'](globalKey);
}

var trueThunk = function () {
	return true;
};

var ensureCache = function ensureCache() {
	if (!__webpack_require__.g[globalKey]) {
		var properties = {};
		properties[globalKey] = {};
		var predicates = {};
		predicates[globalKey] = trueThunk;
		define(__webpack_require__.g, properties, predicates);
	}
	return __webpack_require__.g[globalKey];
};

var cache = ensureCache();

var isPrimitive = function isPrimitive(val) {
	return val === null || (typeof val !== 'object' && typeof val !== 'function');
};

var getPrimitiveKey = function getPrimitiveKey(val) {
	if (isSymbol(val)) {
		return Symbol.prototype.valueOf.call(val);
	}
	return typeof val + ' | ' + String(val);
};

var requirePrimitiveKey = function requirePrimitiveKey(val) {
	if (!isPrimitive(val)) {
		throw new TypeError('key must not be an object');
	}
};

var globalCache = {
	clear: function clear() {
		delete __webpack_require__.g[globalKey];
		cache = ensureCache();
	},

	'delete': function deleteKey(key) {
		requirePrimitiveKey(key);
		delete cache[getPrimitiveKey(key)];
		return !globalCache.has(key);
	},

	get: function get(key) {
		requirePrimitiveKey(key);
		return cache[getPrimitiveKey(key)];
	},

	has: function has(key) {
		requirePrimitiveKey(key);
		return getPrimitiveKey(key) in cache;
	},

	set: function set(key, value) {
		requirePrimitiveKey(key);
		var primitiveKey = getPrimitiveKey(key);
		var props = {};
		props[primitiveKey] = value;
		var predicates = {};
		predicates[primitiveKey] = trueThunk;
		define(cache, props, predicates);
		return globalCache.has(key);
	},

	setIfMissingThenGet: function setIfMissingThenGet(key, valueThunk) {
		if (globalCache.has(key)) {
			return globalCache.get(key);
		}
		var item = valueThunk();
		globalCache.set(key, item);
		return item;
	}
};

module.exports = globalCache;


/***/ }),

/***/ "../../node_modules/.pnpm/is-date-object@1.0.5/node_modules/is-date-object/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var getDay = Date.prototype.getDay;
var tryDateObject = function tryDateGetDayCall(value) {
	try {
		getDay.call(value);
		return true;
	} catch (e) {
		return false;
	}
};

var toStr = Object.prototype.toString;
var dateClass = '[object Date]';
var hasToStringTag = __webpack_require__("../../node_modules/.pnpm/has-tostringtag@1.0.0/node_modules/has-tostringtag/shams.js")();

module.exports = function isDateObject(value) {
	if (typeof value !== 'object' || value === null) {
		return false;
	}
	return hasToStringTag ? tryDateObject(value) : toStr.call(value) === dateClass;
};


/***/ }),

/***/ "../../node_modules/.pnpm/is-regex@1.1.4/node_modules/is-regex/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var callBound = __webpack_require__("../../node_modules/.pnpm/call-bind@1.0.5/node_modules/call-bind/callBound.js");
var hasToStringTag = __webpack_require__("../../node_modules/.pnpm/has-tostringtag@1.0.0/node_modules/has-tostringtag/shams.js")();
var has;
var $exec;
var isRegexMarker;
var badStringifier;

if (hasToStringTag) {
	has = callBound('Object.prototype.hasOwnProperty');
	$exec = callBound('RegExp.prototype.exec');
	isRegexMarker = {};

	var throwRegexMarker = function () {
		throw isRegexMarker;
	};
	badStringifier = {
		toString: throwRegexMarker,
		valueOf: throwRegexMarker
	};

	if (typeof Symbol.toPrimitive === 'symbol') {
		badStringifier[Symbol.toPrimitive] = throwRegexMarker;
	}
}

var $toString = callBound('Object.prototype.toString');
var gOPD = Object.getOwnPropertyDescriptor;
var regexClass = '[object RegExp]';

module.exports = hasToStringTag
	// eslint-disable-next-line consistent-return
	? function isRegex(value) {
		if (!value || typeof value !== 'object') {
			return false;
		}

		var descriptor = gOPD(value, 'lastIndex');
		var hasLastIndexDataProperty = descriptor && has(descriptor, 'value');
		if (!hasLastIndexDataProperty) {
			return false;
		}

		try {
			$exec(value, badStringifier);
		} catch (e) {
			return e === isRegexMarker;
		}
	}
	: function isRegex(value) {
		// In older browsers, typeof regex incorrectly returns 'function'
		if (!value || (typeof value !== 'object' && typeof value !== 'function')) {
			return false;
		}

		return $toString(value) === regexClass;
	};


/***/ }),

/***/ "../../node_modules/.pnpm/is-symbol@1.0.4/node_modules/is-symbol/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var toStr = Object.prototype.toString;
var hasSymbols = __webpack_require__("../../node_modules/.pnpm/has-symbols@1.0.3/node_modules/has-symbols/index.js")();

if (hasSymbols) {
	var symToStr = Symbol.prototype.toString;
	var symStringRegex = /^Symbol\(.*\)$/;
	var isSymbolObject = function isRealSymbolObject(value) {
		if (typeof value.valueOf() !== 'symbol') {
			return false;
		}
		return symStringRegex.test(symToStr.call(value));
	};

	module.exports = function isSymbol(value) {
		if (typeof value === 'symbol') {
			return true;
		}
		if (toStr.call(value) !== '[object Symbol]') {
			return false;
		}
		try {
			return isSymbolObject(value);
		} catch (e) {
			return false;
		}
	};
} else {

	module.exports = function isSymbol(value) {
		// this environment does not support Symbols.
		return  false && 0;
	};
}


/***/ }),

/***/ "../../node_modules/.pnpm/safe-regex-test@1.0.0/node_modules/safe-regex-test/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var callBound = __webpack_require__("../../node_modules/.pnpm/call-bind@1.0.5/node_modules/call-bind/callBound.js");
var GetIntrinsic = __webpack_require__("../../node_modules/.pnpm/get-intrinsic@1.2.2/node_modules/get-intrinsic/index.js");
var isRegex = __webpack_require__("../../node_modules/.pnpm/is-regex@1.1.4/node_modules/is-regex/index.js");

var $exec = callBound('RegExp.prototype.exec');
var $TypeError = GetIntrinsic('%TypeError%');

module.exports = function regexTester(regex) {
	if (!isRegex(regex)) {
		throw new $TypeError('`regex` must be a RegExp');
	}
	return function test(s) {
		return $exec(regex, s) !== null;
	};
};


/***/ }),

/***/ "../../node_modules/.pnpm/string.prototype.trim@1.2.8/node_modules/string.prototype.trim/implementation.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var RequireObjectCoercible = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/RequireObjectCoercible.js");
var ToString = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/ToString.js");
var callBound = __webpack_require__("../../node_modules/.pnpm/call-bind@1.0.5/node_modules/call-bind/callBound.js");
var $replace = callBound('String.prototype.replace');

var mvsIsWS = (/^\s$/).test('\u180E');
/* eslint-disable no-control-regex */
var leftWhitespace = mvsIsWS
	? /^[\x09\x0A\x0B\x0C\x0D\x20\xA0\u1680\u180E\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200A\u202F\u205F\u3000\u2028\u2029\uFEFF]+/
	: /^[\x09\x0A\x0B\x0C\x0D\x20\xA0\u1680\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200A\u202F\u205F\u3000\u2028\u2029\uFEFF]+/;
var rightWhitespace = mvsIsWS
	? /[\x09\x0A\x0B\x0C\x0D\x20\xA0\u1680\u180E\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200A\u202F\u205F\u3000\u2028\u2029\uFEFF]+$/
	: /[\x09\x0A\x0B\x0C\x0D\x20\xA0\u1680\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200A\u202F\u205F\u3000\u2028\u2029\uFEFF]+$/;
/* eslint-enable no-control-regex */

module.exports = function trim() {
	var S = ToString(RequireObjectCoercible(this));
	return $replace($replace(S, leftWhitespace, ''), rightWhitespace, '');
};


/***/ }),

/***/ "../../node_modules/.pnpm/string.prototype.trim@1.2.8/node_modules/string.prototype.trim/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var callBind = __webpack_require__("../../node_modules/.pnpm/call-bind@1.0.5/node_modules/call-bind/index.js");
var define = __webpack_require__("../../node_modules/.pnpm/define-properties@1.2.1/node_modules/define-properties/index.js");
var RequireObjectCoercible = __webpack_require__("../../node_modules/.pnpm/es-abstract@1.22.3/node_modules/es-abstract/2023/RequireObjectCoercible.js");

var implementation = __webpack_require__("../../node_modules/.pnpm/string.prototype.trim@1.2.8/node_modules/string.prototype.trim/implementation.js");
var getPolyfill = __webpack_require__("../../node_modules/.pnpm/string.prototype.trim@1.2.8/node_modules/string.prototype.trim/polyfill.js");
var shim = __webpack_require__("../../node_modules/.pnpm/string.prototype.trim@1.2.8/node_modules/string.prototype.trim/shim.js");

var bound = callBind(getPolyfill());
var boundMethod = function trim(receiver) {
	RequireObjectCoercible(receiver);
	return bound(receiver);
};

define(boundMethod, {
	getPolyfill: getPolyfill,
	implementation: implementation,
	shim: shim
});

module.exports = boundMethod;


/***/ }),

/***/ "../../node_modules/.pnpm/string.prototype.trim@1.2.8/node_modules/string.prototype.trim/polyfill.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var implementation = __webpack_require__("../../node_modules/.pnpm/string.prototype.trim@1.2.8/node_modules/string.prototype.trim/implementation.js");

var zeroWidthSpace = '\u200b';
var mongolianVowelSeparator = '\u180E';

module.exports = function getPolyfill() {
	if (
		String.prototype.trim
		&& zeroWidthSpace.trim() === zeroWidthSpace
		&& mongolianVowelSeparator.trim() === mongolianVowelSeparator
		&& ('_' + mongolianVowelSeparator).trim() === ('_' + mongolianVowelSeparator)
		&& (mongolianVowelSeparator + '_').trim() === (mongolianVowelSeparator + '_')
	) {
		return String.prototype.trim;
	}
	return implementation;
};


/***/ }),

/***/ "../../node_modules/.pnpm/string.prototype.trim@1.2.8/node_modules/string.prototype.trim/shim.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



var define = __webpack_require__("../../node_modules/.pnpm/define-properties@1.2.1/node_modules/define-properties/index.js");
var getPolyfill = __webpack_require__("../../node_modules/.pnpm/string.prototype.trim@1.2.8/node_modules/string.prototype.trim/polyfill.js");

module.exports = function shimStringTrim() {
	var polyfill = getPolyfill();
	define(String.prototype, { trim: polyfill }, {
		trim: function testTrim() {
			return String.prototype.trim !== polyfill;
		}
	});
	return polyfill;
};


/***/ })

}]);