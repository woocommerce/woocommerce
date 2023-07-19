/**
 * External dependencies
 */
import classnames from 'classnames';
import { useProductDataContext } from '@woocommerce/shared-context';
import { useStyleProps } from '@woocommerce/base-hooks';
import { __ } from '@wordpress/i18n';
import { withProductDataContext } from '@woocommerce/shared-hocs';

type ProductAverageRatingProps = {
	className?: string;
	textAlign?: string;
};

export const Block = ( props: ProductAverageRatingProps ): JSX.Element => {
	const { textAlign } = props;
	const styleProps = useStyleProps( props );
	const { product } = useProductDataContext();

	const className = classnames(
		styleProps.className,
		'wc-block-components-product-average-rating',
		{
			[ `has-text-align-${ textAlign }` ]: textAlign,
		}
	);

	return (
		<div className={ className } style={ styleProps.style }>
			{ Number( product.average_rating ) > 0
				? product.average_rating
				: __( 'No ratings', 'woo-gutenberg-products-block' ) }
		</div>
	);
};

export default withProductDataContext( Block );
