declare module '@woocommerce/settings' {
	export declare const CURRENCY: {
		code: string;
		precision: number;
		symbol: string;
		symbolPosition: string;
		decimalSeparator?: string;
		thousandSeparator?: string;
	};
	export declare function getAdminLink( path: string ): string;
	export function getSetting< T >(
		name: string,
		fallback?: unknown,
		filter?: ( val: unknown, fb: unknown ) => unknown
	): T;
}
