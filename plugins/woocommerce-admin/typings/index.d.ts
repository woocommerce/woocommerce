declare module '@woocommerce/e2e-utils';
declare module '@woocommerce/e2e-environment';
declare module '@wordpress/data';
declare module '@wordpress/compose';
declare module 'gridicons/dist/*' {
	const value: React.ElementType< {
		size?: 12 | 18 | 24 | 36 | 48 | 54 | 72;
		onClick?: ( event: MouseEvent | KeyboardEvent ) => void;
	} >;
	export default value;
}
