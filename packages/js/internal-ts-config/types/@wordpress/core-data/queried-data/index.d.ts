/// <reference path="./actions.d.ts" />
/// <reference path="./reducer.d.ts" />
/// <reference path="./selectors.d.ts" />

declare module '@wordpress/core-data/build-types/queried-data' {
	export * from "@wordpress/core-data/build-types/queried-data/actions";
	export * from "@wordpress/core-data/build-types/queried-data/selectors";
	export { default as reducer } from "@wordpress/core-data/build-types/queried-data/reducer";
}
