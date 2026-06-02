declare module '@wordpress/editor/build-types/bindings/pattern-overrides' {
	declare const _default: {
	    name: string;
	    getValues({ select, clientId, context, bindings }: {
	        select: any;
	        clientId: any;
	        context: any;
	        bindings: any;
	    }): {};
	    setValues({ select, dispatch, clientId, bindings }: {
	        select: any;
	        dispatch: any;
	        clientId: any;
	        bindings: any;
	    }): void;
	    canUserEditValue: () => boolean;
	};
	export default _default;
}
