/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import Summary from '@woocommerce/base-components/summary';
import { getSetting } from '@woocommerce/settings';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Product Summary Block Component.
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
	const { product } = productDataContext || props;

	if ( ! product ) {
		return (
			<div
				className={ classnames(
					className,
					`wc-block-components-product-summary`
				) }
			/>
		);
	}

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
				`wc-block-components-product-summary`,
				`${ parentClassName }__product-summary`
			) }
			source={ source }
			maxLength={ 150 }
			countType={ countType }
		/>
	);
};

Block.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object,
};

export default Block;
