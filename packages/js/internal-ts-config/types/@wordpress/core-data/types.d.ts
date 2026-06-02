declare module '@wordpress/core-data/build-types/types' {
	export interface AnyFunction {
	    (...args: any[]): any;
	}
	export interface WPBlockSelection {
	    clientId: string;
	    attributeKey: string;
	    offset: number;
	}
	export interface WPSelection {
	    selectionEnd: WPBlockSelection;
	    selectionStart: WPBlockSelection;
	}
}
