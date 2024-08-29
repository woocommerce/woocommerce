/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';

export type FormattedPriceProps = React.DetailedHTMLProps<
	React.HTMLAttributes< HTMLSpanElement >,
	HTMLSpanElement
> & {
	product: Product;
};
