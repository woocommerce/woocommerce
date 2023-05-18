/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	ProductCollectionAttributes,
	TProductCollectionOrder,
	TProductCollectionOrderBy,
} from '../types';

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

const OrderByControl = (
	props: BlockEditProps< ProductCollectionAttributes >
) => {
	const { order, orderBy } = props.attributes.query;
	return (
		<SelectControl
			label={ __( 'Order by', 'woo-gutenberg-products-block' ) }
			value={ `${ orderBy }/${ order }` }
			options={ orderOptions }
			onChange={ ( value ) => {
				const [ newOrderBy, newOrder ] = value.split( '/' );
				props.setAttributes( {
					query: {
						...props.attributes.query,
						order: newOrder as TProductCollectionOrder,
						orderBy: newOrderBy as TProductCollectionOrderBy,
					},
				} );
			} }
		/>
	);
};

export default OrderByControl;
