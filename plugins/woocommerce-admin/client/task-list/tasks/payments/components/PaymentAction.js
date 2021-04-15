/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import { updateQueryString } from '@woocommerce/navigation';
import { useState } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

export const PaymentAction = ( {
	hasSetup = false,
	isConfigured = false,
	isEnabled = false,
	isLoading = false,
	isRecommended = false,
	manageUrl = null,
	markConfigured,
	methodKey,
	onSetUp = () => {},
	onSetupCallback,
	setupButtonText = __( 'Set up', 'woocommerce-admin' ),
} ) => {
	const [ isBusy, setIsBusy ] = useState( false );

	const classes = 'woocommerce-task-payment__action';

	if ( isLoading ) {
		return <Spinner />;
	}

	const handleClick = async () => {
		onSetUp( methodKey );

		if ( onSetupCallback ) {
			setIsBusy( true );
			await new Promise( onSetupCallback )
				.then( () => {
					setIsBusy( false );
				} )
				.catch( () => {
					setIsBusy( false );
				} );

			return;
		}

		updateQueryString( {
			method: methodKey,
		} );
	};

	const ManageButton = () => (
		<Button
			className={ classes }
			isSecondary
			href={ manageUrl }
			onClick={
				methodKey === 'wcpay'
					? () => recordEvent( 'tasklist_payment_manage' )
					: () => {}
			}
		>
			{ __( 'Manage', 'woocommerce-admin' ) }
		</Button>
	);

	if ( ! hasSetup ) {
		if ( ! isEnabled ) {
			return (
				<Button
					className={ classes }
					isSecondary
					onClick={ () => markConfigured( methodKey ) }
				>
					{ __( 'Enable', 'woocommerce-admin' ) }
				</Button>
			);
		}

		return <ManageButton />;
	}

	if ( ! isEnabled ) {
		return (
			<div>
				<Button
					className={ classes }
					isPrimary={ isRecommended }
					isSecondary={ ! isRecommended }
					isBusy={ isBusy }
					disabled={ isBusy }
					onClick={ () => handleClick() }
				>
					{ setupButtonText }
				</Button>
			</div>
		);
	}

	if ( ! isConfigured ) {
		return (
			<div>
				<Button
					className={ classes }
					isPrimary={ isRecommended }
					isSecondary={ ! isRecommended }
					isBusy={ isBusy }
					disabled={ isBusy }
					onClick={ () => handleClick() }
				>
					{ __( 'Finish setup', 'woocommerce-admin' ) }
				</Button>
			</div>
		);
	}

	return <ManageButton />;
};
