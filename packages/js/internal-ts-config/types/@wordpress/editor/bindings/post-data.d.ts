declare module '@wordpress/editor/build-types/bindings/post-data' {
	declare const _default: {
	    name: string;
	    getValues({ select, context, bindings, clientId }: {
	        select: any;
	        context: any;
	        bindings: any;
	        clientId: any;
	    }): {};
	    setValues({ dispatch, context, bindings, clientId, select }: {
	        dispatch: any;
	        context: any;
	        bindings: any;
	        clientId: any;
	        select: any;
	    }): false | undefined;
	    canUserEditValue({ select, context }: {
	        select: any;
	        context: any;
	    }): boolean;
	    getFieldsList({ select }: {
	        select: any;
	    }): ({
	        label: import("@wordpress/i18n").TranslatableText<"Post Date">;
	        args: {
	            field: string;
	        };
	        type: string;
	    } | {
	        label: import("@wordpress/i18n").TranslatableText<"Post Modified Date">;
	        args: {
	            field: string;
	        };
	        type: string;
	    } | {
	        label: import("@wordpress/i18n").TranslatableText<"Post Link">;
	        args: {
	            field: string;
	        };
	        type: string;
	    })[];
	};
	export default _default;
}
