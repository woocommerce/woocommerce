/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import {
	RangeControl,
	Notice,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

const MIN_PRODUCTS_PER_PAGE = 1;
const MAX_PRODUCTS_PER_PAGE = 100;

const GlobalProductsPerPageControl = () => {
	const [ perPage, setPerPage ] = useEntityProp(
		'root',
		'site',
		'woocommerce_catalog_products_per_page'
	);

	const loopShopPerPage = getSetting< number >( 'loopShopPerPage', 9 );

	return (
		<ToolsPanelItem
			label={ __( 'Products per page', 'woocommerce' ) }
			isShownByDefault
			hasValue={ () => !! perPage }
			onDeselect={ () => setPerPage( 0 ) }
			resetAllFilter={ () => setPerPage( 0 ) }
		>
			<div style={ { marginBottom: '16px' } }>
				<Notice status="info" isDismissible={ false }>
					{ __(
						'This is a global setting that changes the products per page for the entire store.',
						'woocommerce'
					) }
				</Notice>
			</div>
			<RangeControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Products per page', 'woocommerce' ) }
				min={ MIN_PRODUCTS_PER_PAGE }
				max={ MAX_PRODUCTS_PER_PAGE }
				onChange={ ( newPerPage: number ) => {
					if (
						newPerPage < MIN_PRODUCTS_PER_PAGE ||
						newPerPage > MAX_PRODUCTS_PER_PAGE
					) {
						return;
					}
					setPerPage( newPerPage );
				} }
				value={ perPage || loopShopPerPage }
			/>
		</ToolsPanelItem>
	);
};

export default GlobalProductsPerPageControl;
