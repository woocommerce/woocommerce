/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { useProductLayoutContext } from '@woocommerce/base-context';

const ProductSummary = ( { className, product } ) => {
	const { layoutStyleClassPrefix } = useProductLayoutContext();
	if ( ! product.summary ) {
		return null;
	}

	return (
		<div
			className={ classnames(
				className,
				`${ layoutStyleClassPrefix }__product-summary`
			) }
			dangerouslySetInnerHTML={ {
				__html: product.summary,
			} }
		/>
	);
};

ProductSummary.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object.isRequired,
};

export default ProductSummary;
