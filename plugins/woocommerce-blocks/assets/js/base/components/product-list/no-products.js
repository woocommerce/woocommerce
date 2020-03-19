/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useProductLayoutContext } from '@woocommerce/base-context';
import { Icon, notice } from '@woocommerce/icons';

const NoProducts = () => {
	const { layoutStyleClassPrefix } = useProductLayoutContext();
	return (
		<div className={ `${ layoutStyleClassPrefix }__no-products` }>
			<Icon
				className={ `${ layoutStyleClassPrefix }__no-products-image` }
				alt=""
				srcElement={ notice }
				size={ 100 }
			/>
			<strong
				className={ `${ layoutStyleClassPrefix }__no-products-title` }
			>
				{ __( 'No products', 'woo-gutenberg-products-block' ) }
			</strong>
			<p
				className={ `${ layoutStyleClassPrefix }__no-products-description` }
			>
				{ __(
					'There are currently no products available to display.',
					'woo-gutenberg-products-block'
				) }
			</p>
		</div>
	);
};

export default NoProducts;
