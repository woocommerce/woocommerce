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
} from '../../types';
import { getDefaultQuery } from '../../constants';

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
	const { query, setQueryAttribute } = props;
	const { order, orderBy } = query;
	const defaultQuery = getDefaultQuery( query );

	return (
		<ToolsPanelItem
			label={ __( 'Order by', 'woocommerce' ) }
			hasValue={ () =>
				order !== defaultQuery?.order ||
				orderBy !== defaultQuery?.orderBy
			}
			isShownByDefault
			onDeselect={ () => {
				setQueryAttribute( defaultQuery );
			} }
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
				} }
			/>
		</ToolsPanelItem>
	);
};

export default OrderByControl;
