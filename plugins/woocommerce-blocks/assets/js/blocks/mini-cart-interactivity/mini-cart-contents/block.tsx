/**
 * External dependencies
 */
import { CartEventsProvider } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import '../../mini-cart/mini-cart-contents/inner-blocks/register-components';

type MiniCartContentsBlockProps = {
	attributes: Record< string, unknown >;
	children: JSX.Element | JSX.Element[];
};

export const MiniCartContentsBlock = (
	props: MiniCartContentsBlockProps
): JSX.Element => {
	const { children } = props;

	console.log( 'MiniCartContentsBlock', children );

	return <CartEventsProvider>{ children }</CartEventsProvider>;
};
