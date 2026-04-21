declare module '@woocommerce/settings' {
	export const CURRENCY: {
		code?: string;
		precision?: number | string;
		symbol?: string;
		symbolPosition?: string;
		decimalSeparator?: string;
		thousandSeparator?: string;
	};
}
