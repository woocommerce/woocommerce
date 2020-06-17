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
 * Product Tag List Block Component.
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

	if ( isEmpty( product ) || isEmpty( product.tags ) ) {
		return null;
	}

	return (
		<div
			className={ classnames(
				className,
				'wc-block-components-product-tag-list',
				`${ parentClassName }__product-tag-list`
			) }
		>
			{ __( 'Tags:', 'woo-gutenberg-products-block' ) }{ ' ' }
			<ul>
				{ Object.values( product.tags ).map(
					( { name, link, slug } ) => {
						return (
							<li key={ `tag-list-item-${ slug }` }>
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
