/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useInnerBlockLayoutContext } from '@woocommerce/shared-context';
import { Icon, notice } from '@woocommerce/icons';

const NoProducts = () => {
	const { parentClassName } = useInnerBlockLayoutContext();
	return (
		<div className={ `${ parentClassName }__no-products` }>
			<Icon
				className={ `${ parentClassName }__no-products-image` }
				alt=""
				srcElement={ notice }
				size={ 100 }
			/>
			<strong className={ `${ parentClassName }__no-products-title` }>
				{ __( 'No products', 'woocommerce' ) }
			</strong>
			<p className={ `${ parentClassName }__no-products-description` }>
				{ __(
					'There are currently no products available to display.',
					'woocommerce'
				) }
			</p>
		</div>
	);
};

export default NoProducts;
