/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';

export type SaveDraftButtonProps = {
	productStatus: Product[ 'status' ];
	productType?: string;
	visibleTab?: string | null;
	disabled?: boolean;
	href?: string;
	onClick?: ( event: React.MouseEvent< HTMLElement > ) => void;
};
