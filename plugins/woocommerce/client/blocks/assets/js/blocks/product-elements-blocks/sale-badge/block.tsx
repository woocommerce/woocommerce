/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
// @ts-expect-error: WordPress does not provide types for this package.
import ServerSideRender from '@wordpress/server-side-render';
import {
	formatPrice,
	getCurrencyFromPriceResponse,
} from '@woocommerce/price-format';
import clsx from 'clsx';
import { Label } from '@woocommerce/blocks-components';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { useStyleProps } from '@woocommerce/base-hooks';
import { withProductDataContext } from '@woocommerce/shared-hocs';
import type { HTMLAttributes, ReactElement } from 'react';

/**
 * Internal dependencies
 */
import './style.scss';
import type { BlockAttributes } from './types';

type Props = BlockAttributes &
	HTMLAttributes< HTMLDivElement > & {
		align: boolean;
		isDescendentOfSingleProductTemplate: boolean;
		blockAttributes?: BlockAttributes;
	};

export const Block = ( props: Props ): ReactElement | null => {
	const { className, align, isDescendentOfSingleProductTemplate } = props;
	const styleProps = useStyleProps( props );
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();

	/**
	 * Only show sale badge for products that are on sale.
	 * Always show in templates for preview purposes.
	 */
	if (
		( ! product.id || ! product.on_sale ) &&
		! isDescendentOfSingleProductTemplate
	) {
		return null;
	}

	const isNumeric =
		props.badgeContent === 'amount' || props.badgeContent === 'percentage';

	if (
		isNumeric &&
		product.type === 'variable' &&
		product.id &&
		props.blockAttributes
	) {
		return (
			<ServerSideRender
				block="woocommerce/product-sale-badge"
				attributes={ props.blockAttributes }
				urlQueryArgs={ { post_id: product.id } }
			/>
		);
	}

	let label = props.saleText ?? __( 'Sale', 'woocommerce' );
	if (
		isNumeric &&
		product.type !== 'variable' &&
		product.type !== 'grouped'
	) {
		const prices = 'prices' in product ? product.prices : undefined;
		const regular = Number( prices?.regular_price );
		const price = Number( prices?.price );
		if ( regular > 0 && price < regular ) {
			const discount = regular - price;
			const value =
				props.badgeContent === 'percentage'
					? `${ Math.round( ( discount / regular ) * 100 ) }%`
					: formatPrice(
							discount,
							getCurrencyFromPriceResponse( prices )
					  );
			label = `${ props.prefix ?? '' }${ value }${ props.suffix ?? '' }`;
		}
	}

	const alignClass =
		typeof align === 'string'
			? `wc-block-components-product-sale-badge--align-${ align }`
			: '';

	return (
		<div
			className={ clsx(
				'wc-block-components-product-sale-badge',
				className,
				alignClass,
				{
					[ `${ parentClassName }__product-onsale` ]: parentClassName,
				},
				styleProps.className
			) }
			style={ styleProps.style }
		>
			<Label
				label={ label }
				screenReaderLabel={ sprintf(
					/* translators: %s: sale badge text. */
					__( 'Product on sale: %s', 'woocommerce' ),
					label
				) }
			/>
		</div>
	);
};

export default withProductDataContext( Block );
