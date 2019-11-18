/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { useProductLayoutContext } from '@woocommerce/base-context/product-layout-context';

const ProductTitle = ( {
	className,
	product,
	headingLevel = 2,
	productLink = true,
} ) => {
	const { layoutStyleClassPrefix } = useProductLayoutContext();
	if ( ! product.name ) {
		return null;
	}

	const productName = product.name;
	const TagName = `h${ headingLevel }`;

	return (
		<TagName
			className={ classnames(
				className,
				`${ layoutStyleClassPrefix }__product-title`
			) }
		>
			{ productLink ? (
				<a href={ product.permalink } rel="nofollow">
					{ productName }
				</a>
			) : (
				productName
			) }
		</TagName>
	);
};

ProductTitle.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object.isRequired,
	headingLevel: PropTypes.number,
	productLink: PropTypes.bool,
};

export default ProductTitle;
