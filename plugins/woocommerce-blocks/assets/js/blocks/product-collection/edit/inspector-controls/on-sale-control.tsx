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
import { CoreFilterNames, QueryControlProps } from '../../types';
import { DEFAULT_FILTERS } from '../../constants';

const OnSaleControl = ( props: QueryControlProps ) => {
	const { query, trackInteraction, setQueryAttribute } = props;

	function changeOnSaleQueryAttribute( woocommerceOnSale?: boolean ) {
		setQueryAttribute( { woocommerceOnSale } );
		trackInteraction( CoreFilterNames.ON_SALE );
	}

	function handleToolsPanelItemSelect() {
		changeOnSaleQueryAttribute( true );
	}

	function handleToolsPanelItemDeselect() {
		changeOnSaleQueryAttribute( DEFAULT_FILTERS.woocommerceOnSale );
	}

	function handleOnSaleProductsChange( optionValue: string ) {
		changeOnSaleQueryAttribute( optionValue === 'show-only' );
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
				help={ __(
					'Only on sale products will be displayed in this collection.',
					'woocommerce'
				) }
				isBlock
				value={ query.woocommerceOnSale ? 'show-only' : 'dont-show' }
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
