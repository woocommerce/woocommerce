/**
 * External dependencies
 */
import classnames from 'classnames';
import { SidebarLayout } from '@woocommerce/base-components/sidebar-layout';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { useEffect } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { useDispatch, useSelect } from '@wordpress/data';

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
	const { createErrorNotice, removeNotice } = useDispatch( 'core/notices' );

	/*
	 * The code for removing old notices is also present in the filled-mini-cart-contents-block/frontend.tsx file and
	 * will take care of removing outdated errors in the Mini Cart block.
	 */
	const currentlyDisplayedErrorNoticeCodes = useSelect( ( select ) => {
		return select( 'core/notices' )
			.getNotices( 'wc/cart' )
			.filter(
				( notice ) =>
					notice.status === 'error' && notice.type === 'default'
			)
			.map( ( notice ) => notice.id );
	} );

	useEffect( () => {
		// Clear errors out of the store before adding the new ones from the response.
		currentlyDisplayedErrorNoticeCodes.forEach( ( id ) => {
			removeNotice( id, 'wc/cart' );
		} );

		// Ensures any cart errors listed in the API response get shown.
		cartItemErrors.forEach( ( error ) => {
			createErrorNotice( decodeEntities( error.message ), {
				isDismissible: true,
				id: error.code,
				context: 'wc/cart',
			} );
		} );
	}, [
		createErrorNotice,
		cartItemErrors,
		currentlyDisplayedErrorNoticeCodes,
		removeNotice,
	] );

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
