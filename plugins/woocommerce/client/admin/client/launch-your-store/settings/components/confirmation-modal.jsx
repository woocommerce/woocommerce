/**
 * External dependencies
 */
import React from 'react';
import { Button, Modal } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './confirmation-modal.scss';

/**
 * Confirmation modal for the site visibility settings.
 *
 * @return {React.ReactNode} The confirmation modal component.
 */
export const ConfirmationModal = ( {
	formRef,
	saveButtonRef,
	currentSetting,
} ) => {
	const [ pendingSubmitEvent, setPendingSubmitEvent ] = useState( null );
	const [ isConfirmModalOpen, setIsConfirmModalOpen ] = useState( false );
	const currentComingSoon = currentSetting?.woocommerce_coming_soon ?? 'no';

	// Hooks into settings' "mainform" to show a confirmation modal when the form is submitted.
	useEffect( () => {
		const form = formRef.current;
		const handleFormSubmit = ( event ) => {
			const formData = new FormData( form );

			// Only block submission when switching to coming soon mode from live.
			if (
				currentComingSoon === 'no' &&
				formData.get( 'woocommerce_coming_soon' ) === 'yes'
			) {
				event.preventDefault();
				setIsConfirmModalOpen( true );
				setPendingSubmitEvent( event );
			}
		};
		if ( form ) {
			form.addEventListener( 'submit', handleFormSubmit );
		}

		return () => {
			if ( form ) {
				form.removeEventListener( 'submit', handleFormSubmit );
			}
		};
	}, [ currentSetting, formRef ] );

	const cancelSubmit = () => {
		setPendingSubmitEvent( null ); // Clear the pending submit
		setIsConfirmModalOpen( false ); // Close the modal

		if ( saveButtonRef.current ) {
			saveButtonRef.current.classList.remove( 'is-busy' );
		}
	};

	const confirmSubmit = () => {
		if ( pendingSubmitEvent ) {
			// WooCommerce checks for the "save" input.
			if ( saveButtonRef.current && formRef.current ) {
				const hiddenInput = document.createElement( 'input' );
				hiddenInput.type = 'hidden';
				hiddenInput.name = saveButtonRef.current.name || 'save';
				hiddenInput.value =
					saveButtonRef.current.value ||
					__( 'Save changes', 'woocommerce' );
				formRef.current.appendChild( hiddenInput );
			}

			pendingSubmitEvent.target.submit();
			setPendingSubmitEvent( null );
		}
		setIsConfirmModalOpen( false ); // Close the modal
	};

	return isConfirmModalOpen ? (
		<Modal
			title={ __(
				'Confirm switch to ‘Coming soon’ mode',
				'woocommerce'
			) }
			onRequestClose={ cancelSubmit }
			size="medium"
			className="site-visibility-settings-confirmation-modal"
		>
			<div className="site-visibility-settings-confirmation-modal__content">
				{ __(
					'Are you sure you want to switch from live to coming soon mode? Your site will not be visible, and customers won’t be able to make purchases during this time.',
					'woocommerce'
				) }
			</div>
			<div className="divider-container">
				<hr />
			</div>
			<div className="site-visibility-settings-confirmation-modal__buttons">
				<Button
					variant="primary"
					isBusy={ false }
					onClick={ confirmSubmit }
				>
					{ __( 'Switch', 'woocommerce' ) }
				</Button>
				<Button variant="tertiary" onClick={ cancelSubmit }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	) : null;
};
