/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { useInnerBlockConfigurationContext } from '@woocommerce/shared-context';
import Summary from '@woocommerce/base-components/summary';
import { getSetting } from '@woocommerce/settings';

const ProductSummary = ( { className, product } ) => {
	const { layoutStyleClassPrefix } = useInnerBlockConfigurationContext();
	const source = product.short_description
		? product.short_description
		: product.description;

	if ( ! source ) {
		return null;
	}

	const countType = getSetting( 'wordCountType', 'words' );

	return (
		<Summary
			className={ classnames(
				className,
				`${ layoutStyleClassPrefix }__product-summary`
			) }
			source={ source }
			maxLength={ 150 }
			countType={ countType }
		/>
	);
};

ProductSummary.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object.isRequired,
};

export default ProductSummary;
