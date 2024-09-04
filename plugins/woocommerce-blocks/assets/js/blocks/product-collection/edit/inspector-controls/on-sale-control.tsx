/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	CoreFilterNames,
	type ProductCollectionQuery,
	type QueryControlProps,
} from '../../types';
import { DEFAULT_FILTERS } from '../../constants';

const OnSaleControl = ( props: QueryControlProps ) => {
	const { query, trackInteraction, setQueryAttribute } = props;

	function changeOnSaleQueryAttribute(
		woocommerceOnSale?: ProductCollectionQuery[ 'woocommerceOnSale' ]
	) {
		setQueryAttribute( { woocommerceOnSale } );
		trackInteraction( CoreFilterNames.ON_SALE );
	}

	function handleToolsPanelItemSelect() {
		changeOnSaleQueryAttribute( 'show-only' );
	}

	function handleToolsPanelItemDeselect() {
		changeOnSaleQueryAttribute( DEFAULT_FILTERS.woocommerceOnSale );
	}

	function handleOnSaleProductsChange(
		optionValue: ProductCollectionQuery[ 'woocommerceOnSale' ]
	) {
		changeOnSaleQueryAttribute( optionValue );
	}

	return (
		<ToolsPanelItem
			label={ __( 'On Sale', 'woocommerce' ) }
			hasValue={ () =>
				query.woocommerceOnSale !== DEFAULT_FILTERS.woocommerceOnSale
			}
			onSelect={ handleToolsPanelItemSelect }
			onDeselect={ handleToolsPanelItemDeselect }
			resetAllFilter={ handleToolsPanelItemDeselect }
		>
			<ToggleGroupControl
				label={ __( 'On-sale products', 'woocommerce' ) }
				help={
					query.woocommerceOnSale === 'show-only'
						? __(
								'Only on-sale products will be displayed in this collection.',
								'woocommerce'
						  )
						: __(
								'On-sale products will be excluded from this collection.',
								'woocommerce'
						  )
				}
				isBlock
				value={ query.woocommerceOnSale }
				onChange={ handleOnSaleProductsChange }
			>
				<ToggleGroupControlOption
					label={ __( 'Show Only', 'woocommerce' ) }
					value="show-only"
				/>
				<ToggleGroupControlOption
					label={ __( "Don't Show", 'woocommerce' ) }
					value="dont-show"
				/>
			</ToggleGroupControl>
		</ToolsPanelItem>
	);
};

export default OnSaleControl;
