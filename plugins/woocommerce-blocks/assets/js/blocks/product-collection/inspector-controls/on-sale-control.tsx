/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockEditProps } from '@wordpress/blocks';
import {
	ToggleControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ProductCollectionAttributes } from '../types';
import { setQueryAttribute } from '../utils';

const OnSaleControl = (
	props: BlockEditProps< ProductCollectionAttributes >
) => {
	const { query } = props.attributes;

	return (
		<ToolsPanelItem
			label={ __( 'On Sale', 'woo-gutenberg-products-block' ) }
			hasValue={ () => query.woocommerceOnSale === true }
			isShownByDefault
			onDeselect={ () => {
				setQueryAttribute( props, {
					woocommerceOnSale: false,
				} );
			} }
		>
			<ToggleControl
				label={ __(
					'Show only products on sale',
					'woo-gutenberg-products-block'
				) }
				checked={ query.woocommerceOnSale || false }
				onChange={ ( woocommerceOnSale ) => {
					setQueryAttribute( props, {
						woocommerceOnSale,
					} );
				} }
			/>
		</ToolsPanelItem>
	);
};

export default OnSaleControl;
