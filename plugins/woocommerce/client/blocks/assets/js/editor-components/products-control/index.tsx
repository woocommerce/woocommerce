/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { SearchListControl } from '@woocommerce/editor-components/search-list-control';
import { withSearchedProducts } from '@woocommerce/block-hocs';
import ErrorMessage from '@woocommerce/editor-components/error-placeholder/error-message';
import { decodeEntities } from '@wordpress/html-entities';
import { convertProductResponseItemToSearchItem } from '@woocommerce/utils';
import type { ProductResponseItem } from '@woocommerce/types';
import type { ErrorObject } from '@woocommerce/editor-components/error-placeholder';
import type {
	SearchListItem,
	SearchListMessages,
} from '@woocommerce/editor-components/search-list-control/types';
import type { ComponentType } from 'react';

interface ProductsControlProps {
	error: ErrorObject | null;
	isLoading?: boolean;
	onSearch?: ( search: string ) => void;
	products?: ProductResponseItem[];
	selected?: number[];
	onChange: ( value: SearchListItem< ProductResponseItem >[] ) => void;
	isCompact?: boolean;
}

const ProductsControl = ( {
	error,
	onChange,
	onSearch,
	selected = [],
	products = [],
	isLoading = true,
	isCompact = false,
}: ProductsControlProps ): JSX.Element => {
	const messages: Partial< SearchListMessages > = {
		clear: __( 'Clear all products', 'woocommerce' ),
		noItems: __( "Your store doesn't have any products.", 'woocommerce' ),
		search: __( 'Search for products to display', 'woocommerce' ),
		selected: ( n: number ) =>
			sprintf(
				/* translators: %d is the number of selected products. */
				_n(
					'%d product selected',
					'%d products selected',
					n,
					'woocommerce'
				),
				n
			),
		updated: __( 'Product search results updated.', 'woocommerce' ),
	};

	if ( error ) {
		return <ErrorMessage error={ error } />;
	}

	const productList = products.map( convertProductResponseItemToSearchItem );

	return (
		<SearchListControl
			className="woocommerce-products"
			list={ productList.map( ( product ) => {
				const formattedSku = product.details?.sku
					? ' (' + product.details.sku + ')'
					: '';
				return {
					...product,
					name: `${ decodeEntities(
						product.name
					) }${ formattedSku }`,
				};
			} ) }
			isCompact={ isCompact }
			isLoading={ isLoading }
			isSingle={ false }
			selected={ productList.filter( ( { details } ) => {
				if ( ! details || ! Number.isSafeInteger( details.id ) ) {
					return false;
				}
				return selected.includes( details.id );
			} ) }
			onSearch={ onSearch }
			onChange={ onChange }
			messages={ messages }
		/>
	);
};

const WrappedProductsControl: ComponentType< {
	onChange: ( value: SearchListItem< ProductResponseItem >[] ) => void;
	selected: number[];
	isCompact?: boolean;
} > =
	// @ts-expect-error HOC typing for injected products is narrower than this control's search list item shape.
	withSearchedProducts( ProductsControl );

export default WrappedProductsControl;
