/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	SelectControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	TProductCollectionOrder,
	TProductCollectionOrderBy,
	QueryControlProps,
	CoreFilterNames,
} from '../../types';
import { getDefaultQuery } from '../../utils';

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
		value: 'popularity/desc',
		label: __( 'Best Selling', 'woocommerce' ),
	},
	{
		value: 'rating/desc',
		label: __( 'Top Rated', 'woocommerce' ),
	},
];

const OrderByControl = ( props: QueryControlProps ) => {
	const { query, trackInteraction, setQueryAttribute } = props;
	const { order, orderBy } = query;
	const defaultQuery = getDefaultQuery( query );

	const deselectCallback = () => {
		setQueryAttribute( { orderBy: defaultQuery.orderBy } );
		trackInteraction( CoreFilterNames.ORDER );
	};

	return (
		<ToolsPanelItem
			label={ __( 'Order by', 'woocommerce' ) }
			hasValue={ () =>
				order !== defaultQuery?.order ||
				orderBy !== defaultQuery?.orderBy
			}
			isShownByDefault
			onDeselect={ deselectCallback }
			resetAllFilter={ deselectCallback }
		>
			<SelectControl
				value={ `${ orderBy }/${ order }` }
				options={ orderOptions }
				label={ __( 'Order by', 'woocommerce' ) }
				onChange={ ( value ) => {
					const [ newOrderBy, newOrder ] = value.split( '/' );
					setQueryAttribute( {
						order: newOrder as TProductCollectionOrder,
						orderBy: newOrderBy as TProductCollectionOrderBy,
					} );
					trackInteraction( CoreFilterNames.ORDER );
				} }
			/>
		</ToolsPanelItem>
	);
};

export default OrderByControl;
