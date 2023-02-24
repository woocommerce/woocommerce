declare module 'gridicons/dist/*' {
	const value: React.ElementType< {
		size?: 12 | 18 | 24 | 36 | 48 | 54 | 72;
		onClick?: ( event: MouseEvent | KeyboardEvent ) => void;
	} >;
	export default value;
}

declare module '@woocommerce/components';
