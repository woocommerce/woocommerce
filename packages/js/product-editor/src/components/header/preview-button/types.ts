/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { Ref } from 'react';

export type PreviewButtonProps = {
	productStatus: Product[ 'status' ];
	productType: string;
	visibleTab?: string | null;
	disabled?: boolean;
	href?: string;
	ref?: Ref< HTMLElement >;
	onClick?: ( event: React.MouseEvent< HTMLElement > ) => void;
};
