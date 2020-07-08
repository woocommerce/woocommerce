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
 * Internal dependencies
 */
import './style.scss';

/**
 * Product Rating Block Component.
 *
 * @param {Object} props             Incoming props.
 * @param {string} [props.className] CSS Class name for the component.
 * @param {Object} [props.product]   Optional product object. Product from context will be used if
 *                                   this is not provided.
 * @return {*} The component.
 */
const Block = ( { className, ...props } ) => {
	const { parentClassName } = useInnerBlockLayoutContext();
	const productDataContext = useProductDataContext();
	const product = props.product || productDataContext.product;
	const rating = getAverageRating( product );

	if ( ! rating ) {
		return null;
	}

	const starStyle = {
		width: ( rating / 5 ) * 100 + '%',
	};

	const ratingText = sprintf(
		__( 'Rated %f out of 5', 'woocommerce' ),
		rating
	);

	return (
		<div
			className={ classnames(
				className,
				'star-rating',
				'wc-block-components-product-rating',
				`${ parentClassName }__product-rating`
			) }
		>
			<div
				className={ classnames(
					'wc-block-components-product-rating__stars',
					`${ parentClassName }__product-rating__stars`
				) }
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

Block.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object,
};

export default Block;
