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
import { withProductDataContext } from '@woocommerce/shared-hocs';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Product Rating Block Component.
 *
 * @param {Object} props             Incoming props.
 * @param {string} [props.className] CSS Class name for the component.
 * @return {*} The component.
 */
const Block = ( { className } ) => {
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();
	const rating = getAverageRating( product );

	if ( ! rating ) {
		return null;
	}

	const starStyle = {
		width: ( rating / 5 ) * 100 + '%',
	};

	const ratingText = sprintf(
		/* translators: %f is referring to the average rating value */
		__( 'Rated %f out of 5', 'woocommerce' ),
		rating
	);

	return (
		<div
			className={ classnames(
				className,
				'wc-block-components-product-rating',
				{
					[ `${ parentClassName }__product-rating` ]: parentClassName,
				}
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
	const rating = parseFloat( product.average_rating );

	return Number.isFinite( rating ) && rating > 0 ? rating : 0;
};

Block.propTypes = {
	className: PropTypes.string,
};

export default withProductDataContext( Block );
