/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	ToggleControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { QueryControlProps } from '../../types';
import { DEFAULT_FILTERS } from '../../constants';

const FeaturedProductsControl = ( props: QueryControlProps ) => {
	const { query, setQueryAttribute } = props;

	const deselectCallback = () => {
		setQueryAttribute( {
			featured: DEFAULT_FILTERS.featured,
		} );
	};

	return (
		<ToolsPanelItem
			label={ __( 'Featured', 'woocommerce' ) }
			hasValue={ () => query.featured === true }
			onDeselect={ deselectCallback }
			resetAllFilter={ deselectCallback }
		>
			<BaseControl
				id="product-collection-featured-products-control"
				label={ __( 'Featured', 'woocommerce' ) }
			>
				<ToggleControl
					label={ __( 'Show only featured products', 'woocommerce' ) }
					checked={ query.featured || false }
					onChange={ ( featured ) => {
						setQueryAttribute( {
							featured,
						} );
					} }
				/>
			</BaseControl>
		</ToolsPanelItem>
	);
};

export default FeaturedProductsControl;
