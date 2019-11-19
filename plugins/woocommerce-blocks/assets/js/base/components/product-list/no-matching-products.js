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
				alt={ __( 'No products', 'woo-gutenberg-products-block' ) }
				className={ `${ layoutStyleClassPrefix }__no-products-image` }
			/>
			<strong
				className={ `${ layoutStyleClassPrefix }__no-products-title` }
			>
				{ __( 'No products found', 'woo-gutenberg-products-block' ) }
			</strong>
			<p
				className={ `${ layoutStyleClassPrefix }__no-products-description` }
			>
				{ __(
					'We were unable to find any results based on your search.',
					'woo-gutenberg-products-block'
				) }
			</p>
			<button onClick={ resetCallback }>
				{ __( 'Reset Search', 'woo-gutenberg-products-block' ) }
			</button>
		</div>
	);
};

export default NoMatchingProducts;
