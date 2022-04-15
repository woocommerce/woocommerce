/**
 * External dependencies
 */
import classnames from 'classnames';
import { SidebarLayout } from '@woocommerce/base-components/sidebar-layout';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { useEffect } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useCartBlockContext } from '../../context';

const FrontendBlock = ( {
	children,
	className,
}: {
	children: JSX.Element | JSX.Element[];
	className: string;
} ): JSX.Element | null => {
	const { cartItems, cartIsLoading, cartItemErrors } = useStoreCart();
	const { hasDarkControls } = useCartBlockContext();
	const { createErrorNotice } = useDispatch( 'core/notices' );

	// Ensures any cart errors listed in the API response get shown.
	useEffect( () => {
		cartItemErrors.forEach( ( error ) => {
			createErrorNotice( decodeEntities( error.message ), {
				isDismissible: true,
				id: error.code,
				context: 'wc/cart',
			} );
		} );
	}, [ createErrorNotice, cartItemErrors ] );

	if ( cartIsLoading || cartItems.length >= 1 ) {
		return (
			<SidebarLayout
				className={ classnames( 'wc-block-cart', className, {
					'has-dark-controls': hasDarkControls,
				} ) }
			>
				{ children }
			</SidebarLayout>
		);
	}
	return null;
};

export default FrontendBlock;
