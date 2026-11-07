/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getSettingWithCoercion } from '@woocommerce/settings';
import { isNumber } from '@woocommerce/types';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	RangeControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { CoreFilterNames, TrackInteraction } from '../../types';

const MIN_PRODUCTS_PER_PAGE = 1;
const MAX_PRODUCTS_PER_PAGE = 100;
const OPTION_NAME = 'woocommerce_catalog_products_per_page';

const label = __( 'Products per page', 'woocommerce' );

const DefaultQueryProductsPerPageControl = ( {
	trackInteraction,
}: {
	trackInteraction: TrackInteraction;
} ) => {
	const storedValue = useSelect(
		( select ) =>
			(
				select( coreStore ).getEditedEntityRecord(
					'root',
					'site',
					undefined
				) as Record< string, unknown > | undefined
			 )?.[ OPTION_NAME ],
		[]
	);
	const { editEntityRecord } = useDispatch( coreStore );
	const storeDefault = getSettingWithCoercion(
		'loopShopPerPage',
		12,
		isNumber
	);
	const hasValue = isNumber( storedValue );

	const setValue = ( newValue: number | null ) => {
		void editEntityRecord( 'root', 'site', undefined, {
			[ OPTION_NAME ]: newValue,
		} );
		trackInteraction( CoreFilterNames.PRODUCTS_PER_PAGE );
	};

	return (
		<ToolsPanelItem
			label={ label }
			isShownByDefault
			hasValue={ () => hasValue }
			onDeselect={ () => setValue( null ) }
		>
			<RangeControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ label }
				help={ __(
					'All Product Collection blocks using the Default Query will show this many products per page.',
					'woocommerce'
				) }
				min={ MIN_PRODUCTS_PER_PAGE }
				max={ MAX_PRODUCTS_PER_PAGE }
				onChange={ ( newValue?: number ) => {
					if (
						newValue === undefined ||
						newValue < MIN_PRODUCTS_PER_PAGE ||
						newValue > MAX_PRODUCTS_PER_PAGE
					) {
						return;
					}
					setValue( newValue );
				} }
				value={ hasValue ? storedValue : storeDefault }
			/>
		</ToolsPanelItem>
	);
};

export default DefaultQueryProductsPerPageControl;
