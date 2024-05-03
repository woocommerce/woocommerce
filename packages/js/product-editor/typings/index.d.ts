declare module '@woocommerce/settings' {
	export declare function getAdminLink( path: string ): string;
	export declare function getSetting< T >(
		name: string,
		fallback?: unknown,
		filter = ( val: unknown, fb: unknown ) =>
			typeof val !== 'undefined' ? val : fb
	): T;
	export declare function isWpVersion(
		version: string,
		operator: '>' | '>=' | '=' | '<' | '<='
	): boolean;
}

declare module '@wordpress/core-data' {
	function useEntityId( kind: string, name: unknown, id?: string ): any;
	function useEntityProp< T = unknown >(
		kind: string,
		name: string,
		prop: string,
		id?: string
	): [ T, ( value: T ) => void, T ];
	function useEntityRecord< T = unknown >(
		kind: string,
		name: string,
		id?: number | string,
		options?: { enabled: boolean }
	): { record: T, editedRecord: T };
}
declare module '@wordpress/keyboard-shortcuts' {
	function useShortcut(name: string, callback: (event: KeyboardEvent) => void): void;
	const store;
}
