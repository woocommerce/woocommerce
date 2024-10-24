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
		<h2 className={ clsx( className, 'wc-block-cart__totals-title' ) }>
			{ content }
		</h2>
	);
};

export default Block;
