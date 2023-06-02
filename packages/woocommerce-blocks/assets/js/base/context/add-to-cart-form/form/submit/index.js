/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import triggerFetch from '@wordpress/api-fetch';
import { useEffect, useCallback, useState } from '@wordpress/element';
import { useStoreCart, useStoreNotices } from '@woocommerce/base-hooks';
import { decodeEntities } from '@wordpress/html-entities';
import { triggerFragmentRefresh } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import { useAddToCartFormContext } from '../../form-state';
import { useValidationContext } from '../../../shared';

/**
 * FormSubmit.
 *
 * Subscribes to add to cart form context and triggers processing via the API.
 */
const FormSubmit = () => {
	const {
		dispatchActions,
		product,
		quantity,
		eventRegistration,
		hasError,
		isProcessing,
		requestParams,
	} = useAddToCartFormContext();
	const {
		hasValidationErrors,
		showAllValidationErrors,
	} = useValidationContext();
	const { addErrorNotice, removeNotice } = useStoreNotices();
	const { receiveCart } = useStoreCart();
	const [ isSubmitting, setIsSubmitting ] = useState( false );
	const doSubmit = ! hasError && isProcessing;

	const checkValidationContext = useCallback( () => {
		if ( hasValidationErrors ) {
			showAllValidationErrors();
			return {
				type: 'error',
			};
		}
		return true;
	}, [ hasValidationErrors, showAllValidationErrors ] );

	// Subscribe to emitter before processing.
	useEffect( () => {
		const unsubscribeProcessing = eventRegistration.onAddToCartBeforeProcessing(
			checkValidationContext,
			0
		);
		return () => {
			unsubscribeProcessing();
		};
	}, [ eventRegistration, checkValidationContext ] );

	// Triggers form submission to the API.
	const submitFormCallback = useCallback( () => {
		setIsSubmitting( true );
		removeNotice( 'add-to-cart' );

		const fetchData = {
			id: product.id || 0,
			quantity,
			...requestParams,
		};

		triggerFetch( {
			path: '/wc/store/cart/add-item',
			method: 'POST',
			data: fetchData,
			cache: 'no-store',
			parse: false,
		} )
			.then( ( fetchResponse ) => {
				// Update nonce.
				triggerFetch.setNonce( fetchResponse.headers );

				// Handle response.
				fetchResponse.json().then( function ( response ) {
					if ( ! fetchResponse.ok ) {
						// We received an error response.
						if ( response.body && response.body.message ) {
							addErrorNotice(
								decodeEntities( response.body.message ),
								{
									id: 'add-to-cart',
								}
							);
						} else {
							addErrorNotice(
								__(
									'Something went wrong. Please contact us to get assistance.',
									'woocommerce'
								),
								{
									id: 'add-to-cart',
								}
							);
						}
						dispatchActions.setHasError();
					} else {
						receiveCart( response );
					}
					dispatchActions.setAfterProcessing( response );
					setIsSubmitting( false );
					triggerFragmentRefresh();
				} );
			} )
			.catch( ( error ) => {
				error.json().then( function ( response ) {
					// If updated cart state was returned, also update that.
					if ( response.data?.cart ) {
						receiveCart( response.data.cart );
					}
					dispatchActions.setHasError();
					dispatchActions.setAfterProcessing( response );
					setIsSubmitting( false );
				} );
			} );
	}, [
		product,
		addErrorNotice,
		removeNotice,
		receiveCart,
		dispatchActions,
		quantity,
		requestParams,
	] );

	useEffect( () => {
		if ( doSubmit && ! isSubmitting ) {
			submitFormCallback();
		}
	}, [ doSubmit, submitFormCallback, isSubmitting ] );

	return null;
};

export default FormSubmit;
