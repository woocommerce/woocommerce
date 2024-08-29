/**
 * External dependencies
 */
import clsx from 'clsx';

const Block = ( {
	className,
	content = '',
}: {
	className: string;
	content: string;
} ): JSX.Element => {
	return (
		<span className={ clsx( className, 'wc-block-cart__totals-title' ) }>
			{ content }
		</span>
	);
};

export default Block;
