/**
 * External dependencies
 */
import classnames from 'classnames';
import { SidebarLayout } from '@woocommerce/base-components/sidebar-layout';
import { useStoreCart, useStoreNotices } from '@woocommerce/base-context/hooks';
import { useEffect } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

const FrontendBlock = ( {
	children,
}: {
	children: JSX.Element;
} ): JSX.Element | null => {
	const { cartItems, cartIsLoading, cartItemErrors } = useStoreCart();
	const { addErrorNotice } = useStoreNotices();

	// Ensures any cart errors listed in the API response get shown.
	useEffect( () => {
		cartItemErrors.forEach( ( error ) => {
			addErrorNotice( decodeEntities( error.message ), {
				isDismissible: true,
				id: error.code,
			} );
		} );
	}, [ addErrorNotice, cartItemErrors ] );
	// @todo pass attributes to inner most blocks.
	const hasDarkControls = false;
	if ( cartIsLoading || cartItems.length >= 1 ) {
		return (
			<SidebarLayout
				className={ classnames( 'wc-block-cart', {
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
