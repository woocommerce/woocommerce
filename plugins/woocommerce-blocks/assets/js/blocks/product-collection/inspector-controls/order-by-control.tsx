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
} from '../types';
import { getDefaultQuery } from '../constants';

const orderOptions = [
	{
		label: __( 'A → Z', 'woo-gutenberg-products-block' ),
		value: 'title/asc',
	},
	{
		label: __( 'Z → A', 'woo-gutenberg-products-block' ),
		value: 'title/desc',
	},
	{
		label: __( 'Newest to oldest', 'woo-gutenberg-products-block' ),
		value: 'date/desc',
	},
	{
		label: __( 'Oldest to newest', 'woo-gutenberg-products-block' ),
		value: 'date/asc',
	},
	{
		value: 'popularity/desc',
		label: __( 'Best Selling', 'woo-gutenberg-products-block' ),
	},
	{
		value: 'rating/desc',
		label: __( 'Top Rated', 'woo-gutenberg-products-block' ),
	},
];

const OrderByControl = ( props: QueryControlProps ) => {
	const { query, setQueryAttribute } = props;
	const { order, orderBy } = query;
	const defaultQuery = getDefaultQuery( query );

	return (
		<ToolsPanelItem
			label={ __( 'Order by', 'woo-gutenberg-products-block' ) }
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
				label={ __( 'Order by', 'woo-gutenberg-products-block' ) }
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
