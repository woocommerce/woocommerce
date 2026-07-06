/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { useStyleProps } from '@woocommerce/base-hooks';
import { withProductDataContext } from '@woocommerce/shared-hocs';
import type { HTMLAttributes } from 'react';
import { ProductResponseItem } from '@woocommerce/types';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { useProductTypeSelector } from '../../../../shared/stores/product-type-template-state';
import type { BlockAttributes } from './types';
import './style.scss';

type Props = BlockAttributes & HTMLAttributes< HTMLDivElement >;

/**
 * Determines whether the stock indicator should be visible based on product type and availability.
 *
 * @param product             The product.
 * @param availabilityText    The stock availability text.
 * @param selectedProductType The selected product type.
 * @return True if stock indicator should be visible.
 */
const isStockIndicatorVisible = (
	product: ProductResponseItem,
	availabilityText: string,
	selectedProductType: string | undefined
) => {
	// If we have product data, rely on availability text.
	if ( product.id !== 0 ) {
		return availabilityText !== '';
	}

	const productTypesWithoutStockIndicator = getSetting< string[] >(
		'productTypesWithoutStockIndicator',
		[ 'external', 'grouped', 'variable' ]
	);

	const productType = selectedProductType || product?.type;

	return ! productTypesWithoutStockIndicator.includes( productType );
};

export const Block = ( props: Props ): JSX.Element | null => {
	const { className } = props;
	const styleProps = useStyleProps( props );
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();
	const { text: availabilityText, class: availabilityClass } =
		product.stock_availability;

	const { current: currentProductType } = useProductTypeSelector();

	if (
		! isStockIndicatorVisible(
			product,
			availabilityText,
			currentProductType?.slug
		)
	) {
		return null;
	}
	const isInTemplate = product.id === 0;
	const lowStock = product.low_stock_remaining;

	return (
		<div
			className={ clsx( className, {
				[ `${ parentClassName }__stock-indicator` ]: parentClassName,
				[ `wc-block-components-product-stock-indicator--${ availabilityClass }` ]:
					availabilityClass,
				'wc-block-components-product-stock-indicator--in-stock':
					isInTemplate,
				'wc-block-components-product-stock-indicator--low-stock':
					!! lowStock,
				// When inside All products block
				...( props.isDescendantOfAllProducts && {
					[ styleProps.className ]: styleProps.className,
					'wc-block-components-product-stock-indicator wp-block-woocommerce-product-stock-indicator':
						true,
				} ),
			} ) }
			// When inside All products block
			{ ...( props.isDescendantOfAllProducts && {
				style: styleProps.style,
			} ) }
		>
			{ isInTemplate
				? __( 'In stock', 'woocommerce' )
				: availabilityText }
		</div>
	);
};

const StockIndicatorBlock: React.FC< Props > = ( props ) => {
	const { product } = useProductDataContext();
	if ( product.id === 0 ) {
		return <Block { ...props } />;
	}
	return withProductDataContext( Block )( props );
};

export default StockIndicatorBlock;
