/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import { updateQueryString } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import { useState } from '@wordpress/element';

export const PaymentAction = ( {
	hasSetup = false,
	needsSetup = true,
	id,
	isEnabled = false,
	isLoading = false,
	isRecommended = false,
	manageUrl = null,
	markConfigured,
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
		onSetUp( id );

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
			id,
		} );
	};

	const ManageButton = () => (
		<Button
			className={ classes }
			isSecondary
			href={ manageUrl }
			onClick={ () => recordEvent( 'tasklist_payment_manage', { id } ) }
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
					onClick={ () => markConfigured( id ) }
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

	if ( needsSetup ) {
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
