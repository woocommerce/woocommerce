/**
 * External dependencies
 */
import clsx from 'clsx';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import './style.scss';

interface ProductBadgeProps {
	children?: ReactNode;
	className?: string;
}
const ProductBadge = ( {
	children,
	className,
}: ProductBadgeProps ): JSX.Element => {
	return (
		<div
			className={ clsx( 'wc-block-components-product-badge', className ) }
		>
			{ children }
		</div>
	);
};

export default ProductBadge;
