/**
 * External dependencies
 */
import { StoreNoticesContainer } from '@woocommerce/blocks-checkout';
import { useStoreCart } from '@woocommerce/base-context/hooks';

import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

type FilledMiniCartContentsBlockProps = {
	children: JSX.Element;
	className: string;
};

const FilledMiniCartContentsBlock = ( {
	children,
	className,
}: FilledMiniCartContentsBlockProps ): JSX.Element | null => {
	const { cartItems, cartItemErrors } = useStoreCart();

	const { createErrorNotice, removeNotice } = useDispatch( 'core/notices' );

	/*
	 * The code for removing old notices is also present in the filled-cart-block/frontend.tsx file and will take care
	 * of removing outdated errors in the Cart block.
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

	// Ensures any cart errors listed in the API response get shown.
	useEffect( () => {
		// Clear errors out of the store before adding the new ones from the response.
		currentlyDisplayedErrorNoticeCodes.forEach( ( id ) => {
			removeNotice( id, 'wc/cart' );
		} );

		cartItemErrors.forEach( ( error ) => {
			createErrorNotice( decodeEntities( error.message ), {
				isDismissible: false,
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

	if ( cartItems.length === 0 ) {
		return null;
	}

	return (
		<div className={ className }>
			<StoreNoticesContainer context="wc/cart" />
			{ children }
		</div>
	);
};

export default FilledMiniCartContentsBlock;
