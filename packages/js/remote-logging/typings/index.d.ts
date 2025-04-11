declare module '@woocommerce/settings' {
	export declare function getAdminLink( path: string ): string;
	export declare function getSetting< T >(
		name: string,
		fallback?: unknown,
		filter?: ( val: unknown, fb: unknown ) => unknown
	): T;
	export declare function isWpVersion(
		version: string,
		operator: '>' | '>=' | '=' | '<' | '<='
	): boolean;
}
