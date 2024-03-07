/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';

export type ProductImageProps = React.DetailedHTMLProps<
	React.HTMLAttributes< HTMLDivElement >,
	HTMLDivElement
> & { product: Product };
