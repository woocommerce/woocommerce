/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import {
	Notice,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import ProductsPerPageRangeControl from './products-per-page-range-control';

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
			<ProductsPerPageRangeControl
				label={ __( 'Products per page', 'woocommerce' ) }
				value={ perPage || loopShopPerPage }
				onChange={ setPerPage }
			/>
		</ToolsPanelItem>
	);
};

export default GlobalProductsPerPageControl;
