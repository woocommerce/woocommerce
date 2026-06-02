declare module '@wordpress/editor/build-types/bindings/post-meta' {
	declare const _default: {
	    name: string;
	    getValues({ select, context, bindings }: {
	        select: any;
	        context: any;
	        bindings: any;
	    }): {};
	    setValues({ dispatch, context, bindings }: {
	        dispatch: any;
	        context: any;
	        bindings: any;
	    }): void;
	    canUserEditValue({ select, context, args }: {
	        select: any;
	        context: any;
	        args: any;
	    }): boolean;
	    getFieldsList({ select, context }: {
	        select: any;
	        context: any;
	    }): any;
	};
	export default _default;
}
