/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useProductLayoutContext } from '@woocommerce/base-context';
import { Icon, search } from '@woocommerce/icons';

const NoMatchingProducts = ( { resetCallback = () => {} } ) => {
	const { layoutStyleClassPrefix } = useProductLayoutContext();
	return (
		<div className={ `${ layoutStyleClassPrefix }__no-products` }>
			<Icon
				className={ `${ layoutStyleClassPrefix }__no-products-image` }
				alt=""
				srcElement={ search }
				size={ 100 }
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
