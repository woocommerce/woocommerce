/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	TProductCollectionOrder,
	TProductCollectionOrderBy,
	QueryControlProps,
	CoreFilterNames,
} from '../../../types';
import { DEFAULT_QUERY } from '../../../constants';
import OrderByControl from './order-by-control';

const orderOptions = [
	{
		label: __( 'A → Z', 'woocommerce' ),
		value: 'title/asc',
	},
	{
		label: __( 'Z → A', 'woocommerce' ),
		value: 'title/desc',
	},
	{
		label: __( 'Newest to oldest', 'woocommerce' ),
		value: 'date/desc',
	},
	{
		label: __( 'Oldest to newest', 'woocommerce' ),
		value: 'date/asc',
	},
	{
		label: __( 'Price, high to low', 'woocommerce' ),
		value: 'price/desc',
	},
	{
		label: __( 'Price, low to high', 'woocommerce' ),
		value: 'price/asc',
	},
	{
		label: __( 'Sales, high to low', 'woocommerce' ),
		value: 'sales/desc',
	},
	{
		label: __( 'Sales, low to high', 'woocommerce' ),
		value: 'sales/asc',
	},
	{
		value: 'rating/desc',
		label: __( 'Rating, high to low', 'woocommerce' ),
	},
	{
		value: 'rating/asc',
		label: __( 'Rating, low to high', 'woocommerce' ),
	},
	{
		// In WooCommerce, "Manual (menu order + name)" refers to a custom ordering set by the store owner.
		// Products can be manually arranged in the desired order in the WooCommerce admin panel.
		value: 'menu_order/asc',
		label: __( 'Manual (menu order + name)', 'woocommerce' ),
	},
	{
		value: 'random',
		label: __( 'Random', 'woocommerce' ),
	},
];

const CustomQueryOrderByControl = ( props: QueryControlProps ) => {
	const { query, trackInteraction, setQueryAttribute } = props;
	const { order, orderBy } = query;

	const deselectCallback = () => {
		setQueryAttribute( { orderBy: DEFAULT_QUERY.orderBy } );
		trackInteraction( CoreFilterNames.ORDER );
	};

	let orderValue = order ? `${ orderBy }/${ order }` : orderBy;

	// This is to provide backward compatibility as we removed the 'popularity' (Best Selling) option from the order options.
	if ( orderBy === 'popularity' ) {
		orderValue = `sales/${ order }`;
	}

	return (
		<OrderByControl
			selectedValue={ orderValue }
			hasValue={ () =>
				order !== DEFAULT_QUERY.order ||
				orderBy !== DEFAULT_QUERY.orderBy
			}
			orderOptions={ orderOptions }
			onChange={ ( value: string ) => {
				const [ newOrderBy, newOrder ] = value.split( '/' );
				setQueryAttribute( {
					orderBy: newOrderBy as TProductCollectionOrderBy,
					order: ( newOrder as TProductCollectionOrder ) || undefined,
				} );
				trackInteraction( CoreFilterNames.ORDER );
			} }
			onDeselect={ deselectCallback }
			help={ __(
				'Set the products order in this collection.',
				'woocommerce'
			) }
		/>
	);
};

export default CustomQueryOrderByControl;
