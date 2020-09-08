/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import classNames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

const ProductPrice = ( { className, currency, regularValue, value } ) => {
	const isDiscounted =
		Number.isFinite( regularValue ) && regularValue !== value;

	if ( isDiscounted ) {
		return (
			<span className="price wc-block-components-product-price">
				<span className="screen-reader-text">
					{ __( 'Previous price:', 'woocommerce' ) }
				</span>
				<del>
					<FormattedMonetaryAmount
						className={ classNames(
							'wc-block-components-product-price__regular',
							className
						) }
						currency={ currency }
						value={ regularValue }
					/>
				</del>
				<span className="screen-reader-text">
					{ __(
						'Discounted price:',
						'woocommerce'
					) }
				</span>
				<ins>
					<FormattedMonetaryAmount
						className={ classNames(
							'wc-block-components-product-price__value',
							className,
							{
								'is-discounted': isDiscounted,
							}
						) }
						currency={ currency }
						value={ value }
					/>
				</ins>
			</span>
		);
	}

	return (
		<span className="price wc-block-components-product-price">
			<FormattedMonetaryAmount
				className={ classNames(
					'wc-block-components-product-price__value',
					className,
					{
						'is-discounted': isDiscounted,
					}
				) }
				currency={ currency }
				value={ value }
			/>
		</span>
	);
};

ProductPrice.propTypes = {
	currency: PropTypes.object.isRequired,
	value: PropTypes.number.isRequired,
	className: PropTypes.string,
	regularValue: PropTypes.number,
};

export default ProductPrice;
