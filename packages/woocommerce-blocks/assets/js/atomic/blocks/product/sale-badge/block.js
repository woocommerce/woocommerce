/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import Label from '@woocommerce/base-components/label';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Product Sale Badge Block Component.
 *
 * @param {Object} props             Incoming props.
 * @param {string} [props.className] CSS Class name for the component.
 * @param {string} [props.align]     Alignment of the badge.
 * @param {Object} [props.product]   Optional product object. Product from context will be used if
 *                                   this is not provided.
 * @return {*} The component.
 */
const Block = ( { className, align, ...props } ) => {
	const { parentClassName } = useInnerBlockLayoutContext();
	const productDataContext = useProductDataContext();
	const product = props.product || productDataContext.product;

	if ( ! product || ! product.on_sale ) {
		return null;
	}

	const alignClass =
		typeof align === 'string'
			? `wc-block-components-product-sale-badge--align${ align }`
			: '';

	return (
		<div
			className={ classnames(
				'wc-block-components-product-sale-badge',
				className,
				alignClass,
				`${ parentClassName }__product-onsale`
			) }
		>
			<Label
				label={ __( 'Sale', 'woocommerce' ) }
				screenReaderLabel={ __(
					'Product on sale',
					'woocommerce'
				) }
			/>
		</div>
	);
};

Block.propTypes = {
	className: PropTypes.string,
	align: PropTypes.string,
	product: PropTypes.object,
};

export default Block;
