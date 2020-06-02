/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useInnerBlockConfigurationContext } from '@woocommerce/shared-context';
import Label from '@woocommerce/base-components/label';

const ProductSaleBadge = ( { className, product, align } ) => {
	const { layoutStyleClassPrefix } = useInnerBlockConfigurationContext();
	const alignClass =
		typeof align === 'string'
			? `${ layoutStyleClassPrefix }__product-onsale--align${ align }`
			: '';

	if ( product && product.on_sale ) {
		return (
			<div
				className={ classnames(
					'wc-block-component__sale-badge',
					className,
					alignClass,
					`${ layoutStyleClassPrefix }__product-onsale`
				) }
			>
				<Label
					label={ __( 'Sale', 'woo-gutenberg-products-block' ) }
					screenReaderLabel={ __(
						'Product on sale',
						'woo-gutenberg-products-block'
					) }
				/>
			</div>
		);
	}

	return null;
};

export default ProductSaleBadge;
