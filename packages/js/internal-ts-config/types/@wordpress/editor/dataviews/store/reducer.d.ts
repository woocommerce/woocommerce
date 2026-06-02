/// <reference path="./private-actions.d.ts" />

declare module '@wordpress/editor/build-types/dataviews/store/reducer' {
	import type { Action, Field } from '@wordpress/dataviews';
	type ReduxAction = ReturnType<typeof import('@wordpress/editor/build-types/dataviews/store/private-actions').registerEntityAction> | ReturnType<typeof import('@wordpress/editor/build-types/dataviews/store/private-actions').unregisterEntityAction> | ReturnType<typeof import('@wordpress/editor/build-types/dataviews/store/private-actions').registerEntityField> | ReturnType<typeof import('@wordpress/editor/build-types/dataviews/store/private-actions').unregisterEntityField> | ReturnType<typeof import('@wordpress/editor/build-types/dataviews/store/private-actions').setIsReady>;
	export type ActionState = Record<string, Record<string, Action<any>[]>>;
	export type FieldsState = Record<string, Record<string, Field<any>[]>>;
	export type ReadyState = Record<string, Record<string, boolean>>;
	export type State = {
	    actions: ActionState;
	    fields: FieldsState;
	    isReady: ReadyState;
	};
	declare const _default: import("redux").Reducer<{
	    actions: {
	        [x: string]: Record<string, Action<any>[]> | {
	            [x: string]: Action<any>[] | (Action<unknown> | Action<any>)[];
	        };
	    };
	    fields: {
	        [x: string]: Record<string, Field<any>[]> | {
	            [x: string]: Field<any>[] | (Field<any> | Field<unknown>)[];
	        };
	    };
	    isReady: ReadyState;
	}, ReduxAction, Partial<{
	    actions: never;
	    fields: never;
	    isReady: never;
	}>>;
	export default _default;
}
