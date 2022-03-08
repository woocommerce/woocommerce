/**
 * External dependencies
 */
import classNames from 'classnames';

type MiniCartItemsBlockProps = {
	children: JSX.Element;
	className: string;
};

const Block = ( {
	children,
	className,
}: MiniCartItemsBlockProps ): JSX.Element => {
	return (
		<div className={ classNames( className, 'wc-block-mini-cart__items' ) }>
			{ children }
		</div>
	);
};

export default Block;
