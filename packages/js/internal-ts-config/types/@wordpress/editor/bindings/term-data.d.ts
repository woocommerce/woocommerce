declare module '@wordpress/editor/build-types/bindings/term-data' {
	declare const _default: {
	    name: string;
	    usesContext: string[];
	    getValues({ select, context, bindings, clientId }: {
	        select: any;
	        context: any;
	        bindings: any;
	        clientId: any;
	    }): {};
	    setValues({ dispatch, context, bindings }: {
	        dispatch: any;
	        context: any;
	        bindings: any;
	    }): boolean;
	    canUserEditValue({ select, context, args }: {
	        select: any;
	        context: any;
	        args: any;
	    }): boolean;
	    getFieldsList({ select, context }: {
	        select: any;
	        context: any;
	    }): {
	        label: any;
	        type: any;
	        args: {
	            field: string;
	        };
	    }[];
	};
	export default _default;
}
