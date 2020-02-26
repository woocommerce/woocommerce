/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { __, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';
import { useProductLayoutContext } from '@woocommerce/base-context/product-layout-context';

const ProductRating = ( { className, product } ) => {
	const rating = parseFloat( product.average_rating );
	const { layoutStyleClassPrefix } = useProductLayoutContext();

	if ( ! Number.isFinite( rating ) || rating === 0 ) {
		return null;
	}

	const starStyle = {
		width: ( rating / 5 ) * 100 + '%',
	};

	return (
		<div
			className={ classnames(
				className,
				`${ layoutStyleClassPrefix }__product-rating`
			) }
		>
			<div
				className={ `${ layoutStyleClassPrefix }__product-rating__stars` }
				role="img"
			>
				<span style={ starStyle }>
					{ sprintf(
						__(
							'Rated %d out of 5',
							'woocommerce'
						),
						rating
					) }
				</span>
			</div>
		</div>
	);
};

ProductRating.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object.isRequired,
};

export default ProductRating;
