/**
 * External dependencies
 */
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import classNames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

const ProductPrice = ( { className, currency, regularValue, value } ) => {
	return (
		<>
			{ Number.isFinite( regularValue ) && regularValue !== value && (
				<FormattedMonetaryAmount
					className={ classNames(
						'wc-block-product-price--regular',
						className
					) }
					currency={ currency }
					value={ regularValue }
				/>
			) }
			<FormattedMonetaryAmount
				className={ classNames( 'wc-block-product-price', className ) }
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
