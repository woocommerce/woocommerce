/// <reference path="./reducer.d.ts" />

declare module '@wordpress/editor/build-types/dataviews/store/private-selectors' {
	/**
	 * Internal dependencies
	 */
	import type { State } from '@wordpress/editor/build-types/dataviews/store/reducer';
	export declare function getEntityActions(state: State, kind: string, name: string): import("@wordpress/dataviews").Action<any>[];
	export declare function getEntityFields(state: State, kind: string, name: string): import("@wordpress/dataviews").Field<any>[];
	export declare function isEntityReady(state: State, kind: string, name: string): boolean;
}
