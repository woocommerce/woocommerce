/// <reference path="./conservative-map-item.d.ts" />
/// <reference path="./forward-resolver.d.ts" />
/// <reference path="./get-nested-value.d.ts" />
/// <reference path="./get-normalized-comma-separable.d.ts" />
/// <reference path="./if-matching-action.d.ts" />
/// <reference path="./is-numeric-id.d.ts" />
/// <reference path="./is-raw-attribute.d.ts" />
/// <reference path="./on-sub-key.d.ts" />
/// <reference path="./receive-intermediate-results.d.ts" />
/// <reference path="./replace-action.d.ts" />
/// <reference path="./set-nested-value.d.ts" />
/// <reference path="./user-permissions.d.ts" />
/// <reference path="./with-weak-map-cache.d.ts" />

declare module '@wordpress/core-data/build-types/utils' {
	export { default as conservativeMapItem } from "@wordpress/core-data/build-types/utils/conservative-map-item";
	export { default as getNormalizedCommaSeparable } from "@wordpress/core-data/build-types/utils/get-normalized-comma-separable";
	export { default as ifMatchingAction } from "@wordpress/core-data/build-types/utils/if-matching-action";
	export { default as forwardResolver } from "@wordpress/core-data/build-types/utils/forward-resolver";
	export { default as onSubKey } from "@wordpress/core-data/build-types/utils/on-sub-key";
	export { default as replaceAction } from "@wordpress/core-data/build-types/utils/replace-action";
	export { default as withWeakMapCache } from "@wordpress/core-data/build-types/utils/with-weak-map-cache";
	export { default as isRawAttribute } from "@wordpress/core-data/build-types/utils/is-raw-attribute";
	export { default as setNestedValue } from "@wordpress/core-data/build-types/utils/set-nested-value";
	export { default as getNestedValue } from "@wordpress/core-data/build-types/utils/get-nested-value";
	export { default as isNumericID } from "@wordpress/core-data/build-types/utils/is-numeric-id";
	export { RECEIVE_INTERMEDIATE_RESULTS } from "@wordpress/core-data/build-types/utils/receive-intermediate-results";
	export { getUserPermissionCacheKey, getUserPermissionsFromAllowHeader, ALLOWED_RESOURCE_ACTIONS } from "@wordpress/core-data/build-types/utils/user-permissions";
}
