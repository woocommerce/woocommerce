/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { WC_BLOCKS_ASSET_URL } from '@woocommerce/block-settings';
import { useProductLayoutContext } from '@woocommerce/base-context/product-layout-context';

const NoProducts = () => {
	const { layoutStyleClassPrefix } = useProductLayoutContext();
	return (
		<div className={ `${ layoutStyleClassPrefix }__no-products` }>
			<img
				src={ WC_BLOCKS_ASSET_URL + 'img/no-products.svg' }
				alt={ __( 'No products', 'woocommerce' ) }
				className={ `${ layoutStyleClassPrefix }__no-products-image` }
			/>
			<strong
				className={ `${ layoutStyleClassPrefix }__no-products-title` }
			>
				{ __( 'No products', 'woocommerce' ) }
			</strong>
			<p
				className={ `${ layoutStyleClassPrefix }__no-products-description` }
			>
				{ __(
					'There are currently no products available to display.',
					'woocommerce'
				) }
			</p>
		</div>
	);
};

export default NoProducts;
