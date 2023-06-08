/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import {
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ProductCollectionAttributes } from '../types';
import ColumnsControl from './columns-control';
import InheritQueryControl from './inherit-query-control';
import OrderByControl from './order-by-control';
import OnSaleControl from './on-sale-control';
import { setQueryAttribute } from '../utils';
import { DEFAULT_FILTERS, getDefaultSettings } from '../constants';
import StockStatusControl from './stock-status-control';
import KeywordControl from './keyword-control';
import AttributesControl from './attributes-control';
import TaxonomyControls from './taxonomy-controls';
import HandPickedProductsControl from './hand-picked-products-control';
import AuthorControl from './author-control';

const ProductCollectionInspectorControls = (
	props: BlockEditProps< ProductCollectionAttributes >
) => {
	const query = props.attributes.query;
	const inherit = query?.inherit;
	const displayQueryControls = inherit === false;

	const setQueryAttributeBind = useMemo(
		() => setQueryAttribute.bind( null, props ),
		[ props ]
	);

	return (
		<InspectorControls>
			<ToolsPanel
				label={ __( 'Settings', 'woo-gutenberg-products-block' ) }
				resetAll={ () => {
					const defaultSettings = getDefaultSettings(
						props.attributes
					);
					props.setAttributes( defaultSettings );
				} }
			>
				<ColumnsControl { ...props } />
				<InheritQueryControl
					setQueryAttribute={ setQueryAttributeBind }
					query={ query }
				/>
				{ displayQueryControls ? (
					<OrderByControl { ...props } />
				) : null }
			</ToolsPanel>

			{ displayQueryControls ? (
				<ToolsPanel
					label={ __( 'Filters', 'woo-gutenberg-products-block' ) }
					resetAll={ ( resetAllFilters: ( () => void )[] ) => {
						setQueryAttribute( props, DEFAULT_FILTERS );
						resetAllFilters.forEach( ( resetFilter ) =>
							resetFilter()
						);
					} }
					className="wc-block-editor-product-collection-inspector-toolspanel__filters"
				>
					<OnSaleControl { ...props } />
					<StockStatusControl { ...props } />
					<HandPickedProductsControl
						setQueryAttribute={ setQueryAttributeBind }
						selectedProductIds={
							query.woocommerceHandPickedProducts
						}
					/>
					<KeywordControl { ...props } />
					<AttributesControl
						woocommerceAttributes={
							query.woocommerceAttributes || []
						}
						setQueryAttribute={ setQueryAttributeBind }
					/>
					<TaxonomyControls
						setQueryAttribute={ setQueryAttributeBind }
						query={ query }
					/>
					<AuthorControl
						value={ query.author }
						setQueryAttribute={ setQueryAttributeBind }
					/>
				</ToolsPanel>
			) : null }
		</InspectorControls>
	);
};

export default ProductCollectionInspectorControls;
