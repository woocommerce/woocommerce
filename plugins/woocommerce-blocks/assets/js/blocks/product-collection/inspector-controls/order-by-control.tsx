/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockEditProps } from '@wordpress/blocks';
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
	ProductCollectionAttributes,
	TProductCollectionOrder,
	TProductCollectionOrderBy,
} from '../types';
import { getDefaultSettings } from '../constants';

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
	const defaultSettings = getDefaultSettings( props.attributes );

	return (
		<ToolsPanelItem
			label={ __( 'Order by', 'woo-gutenberg-products-block' ) }
			hasValue={ () =>
				order !== defaultSettings.query?.order ||
				orderBy !== defaultSettings.query?.orderBy
			}
			isShownByDefault
			onDeselect={ () => {
				props.setAttributes( {
					query: {
						...props.attributes.query,
						...defaultSettings.query,
					},
				} );
			} }
		>
			<SelectControl
				value={ `${ orderBy }/${ order }` }
				options={ orderOptions }
				label={ __( 'Order by', 'woo-gutenberg-products-block' ) }
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
		</ToolsPanelItem>
	);
};

export default OrderByControl;
