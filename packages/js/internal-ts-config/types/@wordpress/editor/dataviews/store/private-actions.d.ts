declare module '@wordpress/editor/build-types/dataviews/store/private-actions' {
	import type { Action, Field } from '@wordpress/dataviews';
	export declare function registerEntityAction<Item>(kind: string, name: string, config: Action<Item>): {
	    type: "REGISTER_ENTITY_ACTION";
	    kind: string;
	    name: string;
	    config: Action<Item>;
	};
	export declare function unregisterEntityAction(kind: string, name: string, actionId: string): {
	    type: "UNREGISTER_ENTITY_ACTION";
	    kind: string;
	    name: string;
	    actionId: string;
	};
	export declare function registerEntityField<Item>(kind: string, name: string, config: Field<Item>): {
	    type: "REGISTER_ENTITY_FIELD";
	    kind: string;
	    name: string;
	    config: Field<Item>;
	};
	export declare function unregisterEntityField(kind: string, name: string, fieldId: string): {
	    type: "UNREGISTER_ENTITY_FIELD";
	    kind: string;
	    name: string;
	    fieldId: string;
	};
	export declare function setIsReady(kind: string, name: string): {
	    type: "SET_IS_READY";
	    kind: string;
	    name: string;
	};
	export declare const registerPostTypeSchema: (postType: string) => ({ registry }: {
	    registry: any;
	}) => Promise<void>;
}
