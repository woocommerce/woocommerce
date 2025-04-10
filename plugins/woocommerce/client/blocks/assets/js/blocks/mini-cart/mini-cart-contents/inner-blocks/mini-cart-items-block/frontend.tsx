/**
 * External dependencies
 */
import clsx from 'clsx';

type MiniCartItemsBlockProps = {
	children: JSX.Element;
	className: string;
};

const Block = ( {
	children,
	className,
}: MiniCartItemsBlockProps ): JSX.Element => {
	return (
		<div
			className={ clsx( className, 'wc-block-mini-cart__items' ) }
			tabIndex={ -1 }
		>
			{ children }
		</div>
	);
};

export default Block;
