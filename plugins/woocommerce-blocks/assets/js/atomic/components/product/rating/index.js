/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { __, sprintf } from '@wordpress/i18n';
import { Component } from 'react';
import classnames from 'classnames';

class ProductRating extends Component {
	static propTypes = {
		className: PropTypes.string,
		product: PropTypes.object.isRequired,
	};

	render = () => {
		const { product, className } = this.props;
		const rating = parseFloat( product.average_rating );

		if ( ! Number.isFinite( rating ) || 0 === rating ) {
			return null;
		}

		const starStyle = {
			width: ( rating / 5 ) * 100 + '%',
		};

		return (
			<div
				className={ classnames(
					className,
					'wc-block-grid__product-rating'
				) }
			>
				<div
					className="wc-block-grid__product-rating__stars"
					role="img"
				>
					<span style={ starStyle }>
						{ sprintf(
							__(
								'Rated %d out of 5',
								'woo-gutenberg-products-block'
							),
							rating
						) }
					</span>
				</div>
			</div>
		);
	};
}

export default ProductRating;
