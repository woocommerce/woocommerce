/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, select } from '@wordpress/data';
import { useState } from '@wordpress/element';
import {
	CoreFilterNames,
	TrackInteraction,
} from '@woocommerce/blocks/product-collection/types';

/**
 * Internal dependencies
 */
import OrderByControl from './order-by-control';

const orderOptions = [
	{
		label: __( 'Newest to oldest', 'woocommerce' ),
		value: 'date',
	},
	{
		label: __( 'Price, high to low', 'woocommerce' ),
		value: 'price-desc',
	},
	{
		label: __( 'Price, low to high', 'woocommerce' ),
		value: 'price',
	},
	{
		label: __( 'Sales, high to low', 'woocommerce' ),
		value: 'popularity',
	},
	{
		label: __( 'Rating, high to low', 'woocommerce' ),
		value: 'rating',
	},
	{
		// In WooCommerce, "Manual (menu order + name)" refers to a custom ordering set by the store owner.
		// Products can be manually arranged in the desired order in the WooCommerce admin panel.
		value: 'menu_order',
		label: __( 'Manual (menu order + name)', 'woocommerce' ),
	},
];

const DefaultQueryOrderByControl = ( {
	trackInteraction,
}: {
	trackInteraction: TrackInteraction;
} ) => {
	const settings = select( 'core' ).getEditedEntityRecord(
		'root',
		'site'
	) as Record< string, string >;

	const [ value, setValue ] = useState(
		settings.woocommerce_default_catalog_orderby || 'menu_order'
	);

	const onChange = ( newValue: string ) => {
		setValue( newValue );
		dispatch( coreStore ).editEntityRecord( 'root', 'site', undefined, {
			[ `woocommerce_default_catalog_orderby` ]: newValue,
		} );
		trackInteraction( CoreFilterNames.DEFAULT_ORDER );
	};

	return (
		<OrderByControl
			label={ __( 'Default sort by', 'woocommerce' ) }
			selectedValue={ value }
			orderOptions={ orderOptions }
			onChange={ onChange }
			help={ __(
				'All Product Collection blocks using the Default Query will sync to this sort order.',
				'woocommerce'
			) }
		/>
	);
};

export default DefaultQueryOrderByControl;
