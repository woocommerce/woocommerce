/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { isEmpty } from 'lodash';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Product Category Block Component.
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
	const { product } = productDataContext || props || {};

	if ( isEmpty( product ) || isEmpty( product.categories ) ) {
		return null;
	}

	return (
		<div
			className={ classnames(
				className,
				'wc-block-components-product-category-list',
				`${ parentClassName }__product-category-list`
			) }
		>
			{ __( 'Categories:', 'woo-gutenberg-products-block' ) }{ ' ' }
			<ul>
				{ Object.values( product.categories ).map(
					( { name, link, slug } ) => {
						return (
							<li key={ `category-list-item-${ slug }` }>
								<a href={ link }>{ name }</a>
							</li>
						);
					}
				) }
			</ul>
		</div>
	);
};

Block.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object,
};

export default Block;
