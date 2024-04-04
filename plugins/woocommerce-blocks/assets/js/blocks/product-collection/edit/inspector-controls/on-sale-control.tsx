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
import { QueryControlProps } from '../../types';

const OnSaleControl = ( props: QueryControlProps ) => {
	const { query, setQueryAttribute } = props;

	const deselectCallback = () => {
		setQueryAttribute( { woocommerceOnSale: false } );
	};

	return (
		<ToolsPanelItem
			label={ __( 'On Sale', 'woocommerce' ) }
			hasValue={ () => query.woocommerceOnSale === true }
			isShownByDefault
			onDeselect={ deselectCallback }
			resetAllFilter={ deselectCallback }
		>
			<ToggleControl
				label={ __( 'Show only products on sale', 'woocommerce' ) }
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
