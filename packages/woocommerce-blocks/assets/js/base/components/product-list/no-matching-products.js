/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useInnerBlockLayoutContext } from '@woocommerce/shared-context';
import { Icon, search } from '@woocommerce/icons';

const NoMatchingProducts = ( { resetCallback = () => {} } ) => {
	const { parentClassName } = useInnerBlockLayoutContext();
	return (
		<div className={ `${ parentClassName }__no-products` }>
			<Icon
				className={ `${ parentClassName }__no-products-image` }
				alt=""
				srcElement={ search }
				size={ 100 }
			/>
			<strong className={ `${ parentClassName }__no-products-title` }>
				{ __( 'No products found', 'woocommerce' ) }
			</strong>
			<p className={ `${ parentClassName }__no-products-description` }>
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
