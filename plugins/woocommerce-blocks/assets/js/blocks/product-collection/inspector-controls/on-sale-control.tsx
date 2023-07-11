/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	ToggleControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { QueryControlProps } from '../types';

const OnSaleControl = ( props: QueryControlProps ) => {
	const { query, setQueryAttribute } = props;

	return (
		<ToolsPanelItem
			label={ __( 'On Sale', 'woo-gutenberg-products-block' ) }
			hasValue={ () => query.woocommerceOnSale === true }
			isShownByDefault
			onDeselect={ () => {
				setQueryAttribute( {
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
					setQueryAttribute( {
						woocommerceOnSale,
					} );
				} }
			/>
		</ToolsPanelItem>
	);
};

export default OnSaleControl;
