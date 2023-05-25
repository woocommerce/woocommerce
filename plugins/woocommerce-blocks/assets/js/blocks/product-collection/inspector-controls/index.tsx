/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
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
import OrderByControl from './order-by-control';
import OnSaleControl from './on-sale-control';
import { setQueryAttribute } from './utils';
import { DEFAULT_FILTERS, getDefaultSettings } from './constants';

const ProductCollectionInspectorControls = (
	props: BlockEditProps< ProductCollectionAttributes >
) => {
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
				<OrderByControl { ...props } />
			</ToolsPanel>

			<ToolsPanel
				label={ __( 'Filters', 'woo-gutenberg-products-block' ) }
				resetAll={ () => {
					setQueryAttribute( props, DEFAULT_FILTERS );
				} }
			>
				<OnSaleControl { ...props } />
			</ToolsPanel>
		</InspectorControls>
	);
};

export default ProductCollectionInspectorControls;
