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
	return (
		<>
			{ isDiscounted && (
				<>
					<span className="screen-reader-text">
						{ __(
							'Previous price:',
							'woocommerce'
						) }
					</span>
					<FormattedMonetaryAmount
						className={ classNames(
							'wc-block-product-price--regular',
							className
						) }
						currency={ currency }
						value={ regularValue }
					/>
					<span className="screen-reader-text">
						{ __(
							'Discounted price:',
							'woocommerce'
						) }
					</span>
				</>
			) }
			<FormattedMonetaryAmount
				className={ classNames( 'wc-block-product-price', className, {
					'is-discounted': isDiscounted,
				} ) }
				currency={ currency }
				value={ value }
			/>
		</>
	);
};

ProductPrice.propTypes = {
	currency: PropTypes.object.isRequired,
	value: PropTypes.number.isRequired,
	className: PropTypes.string,
	regularValue: PropTypes.number,
};

export default ProductPrice;
