/**
 * External dependencies
 */
import { CustomSelectControl, PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ProductQueryBlock, ProductQueryBlockQuery } from '../types';
import { setQueryAttribute } from '../utils';

const PRESETS = [
	{
		key: 'title/asc',
		name: __( 'Sorted by title', 'woocommerce' ),
	},
	{ key: 'date/desc', name: __( 'Newest', 'woocommerce' ) },
	{
		key: 'popularity/desc',
		name: __( 'Best Selling', 'woocommerce' ),
	},
	{
		key: 'rating/desc',
		name: __( 'Top Rated', 'woocommerce' ),
	},
];

export function PopularPresets( props: ProductQueryBlock ) {
	const { query } = props.attributes;

	return (
		<PanelBody
			className="woocommerce-product-query-panel__sort"
			title={ __( 'Popular Filters', 'woocommerce' ) }
			initialOpen={ true }
		>
			<p>
				{ __( 'Arrange products by popular pre-sets.', 'woocommerce' ) }
			</p>
			<CustomSelectControl
				hideLabelFromVision={ true }
				label={ __( 'Choose among these pre-sets', 'woocommerce' ) }
				onChange={ ( option ) => {
					if ( ! option.selectedItem?.key ) return;

					const [ orderBy, order ] = option.selectedItem?.key?.split(
						'/'
					) as [
						ProductQueryBlockQuery[ 'orderBy' ],
						ProductQueryBlockQuery[ 'order' ]
					];

					setQueryAttribute( props, { order, orderBy } );
				} }
				options={ PRESETS }
				value={ PRESETS.find(
					( option ) =>
						option.key === `${ query.orderBy }/${ query.order }`
				) }
			/>
		</PanelBody>
	);
}
