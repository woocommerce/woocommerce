/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { __, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';

/**
 * Product Rating Block Component.
 *
 * @param {Object} props             Incoming props.
 * @param {string} [props.className] CSS Class name for the component.
 * @param {Object} [props.product]   Optional product object. Product from context will be used if
 *                                   this is not provided.
 * @return {*} The component.
 */
const ProductRating = ( { className, ...props } ) => {
	const productDataContext = useProductDataContext();
	const product = props.product || productDataContext.product;

	const { layoutStyleClassPrefix } = useInnerBlockLayoutContext();
	const componentClass = `${ layoutStyleClassPrefix }__product-rating`;

	const rating = getAverageRating( product );

	if ( ! rating ) {
		return null;
	}

	const starStyle = {
		width: ( rating / 5 ) * 100 + '%',
	};

	const ratingText = sprintf(
		__( 'Rated %f out of 5', 'woo-gutenberg-products-block' ),
		rating
	);

	return (
		<div className={ classnames( className, componentClass ) }>
			<div
				className={ `${ componentClass }__stars` }
				role="img"
				aria-label={ ratingText }
			>
				<span style={ starStyle }>{ ratingText }</span>
			</div>
		</div>
	);
};

const getAverageRating = ( product ) => {
	// eslint-disable-next-line camelcase
	const rating = parseFloat( product?.average_rating || 0 );

	return Number.isFinite( rating ) && rating > 0 ? rating : 0;
};

ProductRating.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object,
};

export default ProductRating;
