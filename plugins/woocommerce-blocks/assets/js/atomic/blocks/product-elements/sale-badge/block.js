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
import {
	useBorderProps,
	useColorProps,
	useSpacingProps,
	useTypographyProps,
} from '@woocommerce/base-hooks';
import { withProductDataContext } from '@woocommerce/shared-hocs';

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
 * @return {*} The component.
 */
export const Block = ( props ) => {
	const { className, align } = props;
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();
	const borderProps = useBorderProps( props );
	const colorProps = useColorProps( props );

	const typographyProps = useTypographyProps( props );
	const spacingProps = useSpacingProps( props );

	if ( ! product.id || ! product.on_sale ) {
		return null;
	}

	const alignClass =
		typeof align === 'string'
			? `wc-block-components-product-sale-badge--align-${ align }`
			: '';

	return (
		<div
			className={ classnames(
				'wc-block-components-product-sale-badge',
				className,
				alignClass,
				{
					[ `${ parentClassName }__product-onsale` ]: parentClassName,
				},
				colorProps.className,
				borderProps.className
			) }
			style={ {
				...colorProps.style,
				...borderProps.style,
				...typographyProps.style,
				...spacingProps.style,
			} }
		>
			<Label
				label={ __( 'Sale', 'woo-gutenberg-products-block' ) }
				screenReaderLabel={ __(
					'Product on sale',
					'woo-gutenberg-products-block'
				) }
			/>
		</div>
	);
};

Block.propTypes = {
	className: PropTypes.string,
	align: PropTypes.string,
};

export default withProductDataContext( Block );
