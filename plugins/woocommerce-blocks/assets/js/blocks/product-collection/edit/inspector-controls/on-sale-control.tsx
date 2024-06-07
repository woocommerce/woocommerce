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
import { CoreFilterNames, QueryControlProps } from '../../types';
import { DEFAULT_FILTERS } from '../../constants';

const OnSaleControl = ( props: QueryControlProps ) => {
	const { query, trackInteraction, setQueryAttribute } = props;

	const deselectCallback = () => {
		setQueryAttribute( {
			woocommerceOnSale: DEFAULT_FILTERS.woocommerceOnSale,
		} );
		trackInteraction( CoreFilterNames.ON_SALE );
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
					trackInteraction( CoreFilterNames.ON_SALE );
				} }
			/>
		</ToolsPanelItem>
	);
};

export default OnSaleControl;
