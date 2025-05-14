/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { Label } from '@woocommerce/blocks-components';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { useStyleProps } from '@woocommerce/base-hooks';
import { withProductDataContext } from '@woocommerce/shared-hocs';
import type { HTMLAttributes } from 'react';
import { store as coreStore } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import './style.scss';
import type { BlockAttributes } from './types';

type Props = BlockAttributes &
	HTMLAttributes< HTMLDivElement > & { align: boolean };

export const Block = ( props: Props ): JSX.Element | null => {
	const { className, align } = props;
	const styleProps = useStyleProps( props );
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();

	const isTemplate = useSelect( ( select ) => {
		const { getCurrentPostType } = select( coreStore );

		const template = getCurrentPostType() === 'wp_template';

		return template;
	}, [] );

	/**
	 * Only show sale badge for products that are on sale.
	 * Always show in templates for preview purposes.
	 */
	if ( ( ! product.id || ! product.on_sale ) && ! isTemplate ) {
		return null;
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
				label={ __( 'Sale', 'woocommerce' ) }
				screenReaderLabel={ __( 'Product on sale', 'woocommerce' ) }
			/>
		</div>
	);
};

export default withProductDataContext( Block );
