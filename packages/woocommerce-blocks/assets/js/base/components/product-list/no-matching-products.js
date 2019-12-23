/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { WC_BLOCKS_ASSET_URL } from '@woocommerce/block-settings';
import { useProductLayoutContext } from '@woocommerce/base-context/product-layout-context';

const NoMatchingProducts = ( { resetCallback = () => {} } ) => {
	const { layoutStyleClassPrefix } = useProductLayoutContext();
	return (
		<div className={ `${ layoutStyleClassPrefix }__no-products` }>
			<img
				src={ WC_BLOCKS_ASSET_URL + 'img/no-matching-products.svg' }
				alt={ __( 'No products', 'woocommerce' ) }
				className={ `${ layoutStyleClassPrefix }__no-products-image` }
			/>
			<strong
				className={ `${ layoutStyleClassPrefix }__no-products-title` }
			>
				{ __( 'No products found', 'woocommerce' ) }
			</strong>
			<p
				className={ `${ layoutStyleClassPrefix }__no-products-description` }
			>
				{ __(
					'We were unable to find any results based on your search.',
					'woocommerce'
				) }
			</p>
			<button onClick={ resetCallback }>
				{ __( 'Reset Search', 'woocommerce' ) }
			</button>
		</div>
	);
};

export default NoMatchingProducts;
