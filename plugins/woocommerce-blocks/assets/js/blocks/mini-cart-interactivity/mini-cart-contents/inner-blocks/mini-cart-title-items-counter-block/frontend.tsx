/**
 * External dependencies
 */
import clsx from 'clsx';
import { _n, sprintf } from '@wordpress/i18n';
// import { useStoreCart } from '@woocommerce/base-context';
// import { useStyleProps } from '@woocommerce/base-hooks';

type Props = {
	className?: string;
};

const Block = ( props: Props ): JSX.Element => {
	// const { cartItemsCount } = useStoreCart();
	// const styleProps = useStyleProps( props );

	const cartItemsCount = 0;

	return (
		<span className={ clsx( props.className ) }>
			{ sprintf(
				/* translators: %d is the count of items in the cart. */
				_n( '(%d item)', '(%d items)', cartItemsCount, 'woocommerce' ),
				cartItemsCount
			) }
		</span>
	);
};

export default Block;
