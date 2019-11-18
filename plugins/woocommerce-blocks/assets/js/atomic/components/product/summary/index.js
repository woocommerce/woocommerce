/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { useProductLayoutContext } from '@woocommerce/base-context/product-layout-context';

const ProductSummary = ( { className, product } ) => {
	const { layoutStyleClassPrefix } = useProductLayoutContext();
	if ( ! product.description ) {
		return null;
	}

	return (
		<div
			className={ classnames(
				className,
				`${ layoutStyleClassPrefix }__product-summary`
			) }
			dangerouslySetInnerHTML={ {
				__html: product.description,
			} }
		/>
	);
};

ProductSummary.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object.isRequired,
};

export default ProductSummary;
