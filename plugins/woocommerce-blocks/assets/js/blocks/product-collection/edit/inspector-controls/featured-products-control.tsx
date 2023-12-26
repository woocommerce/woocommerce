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

const FeaturedProductsControl = ( props: QueryControlProps ) => {
	const { query, setQueryAttribute } = props;

	return (
		<ToolsPanelItem
			label={ __( 'Featured', 'woocommerce' ) }
			hasValue={ () => query.featured === true }
			onDeselect={ () => {
				setQueryAttribute( {
					featured: false,
				} );
			} }
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
