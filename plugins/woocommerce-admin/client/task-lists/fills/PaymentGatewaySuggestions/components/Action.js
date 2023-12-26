/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import { updateQueryString } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import { useState } from '@wordpress/element';
/**
 * Internal dependencies
 */
import { getPluginTrackKey } from '~/utils';

export const Action = ( {
	hasSetup = false,
	needsSetup = true,
	id,
	isEnabled = false,
	isLoading = false,
	isInstalled = false,
	isRecommended = false,
	hasPlugins,
	manageUrl = null,
	markConfigured,
	onSetUp = () => {},
	onSetupCallback,
	setupButtonText = __( 'Get started', 'woocommerce' ),
	externalLink = null,
} ) => {
	const [ isBusy, setIsBusy ] = useState( false );

	const classes = 'woocommerce-task-payment__action';

	if ( isLoading ) {
		return <Spinner />;
	}

	const handleClick = async () => {
		onSetUp( id );

		recordEvent( 'tasklist_payment_setup', {
			selected: getPluginTrackKey( id ),
		} );

		if ( ! hasPlugins && externalLink ) {
			window.location.href = externalLink;
			return;
		}

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
			role="button"
			href={ manageUrl }
			onClick={ () => recordEvent( 'tasklist_payment_manage', { id } ) }
		>
			{ __( 'Manage', 'woocommerce' ) }
		</Button>
	);

	const SetupButton = () => (
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
	);

	const EnableButton = () => (
		<Button
			className={ classes }
			isSecondary
			onClick={ () => markConfigured( id ) }
		>
			{ __( 'Enable', 'woocommerce' ) }
		</Button>
	);

	if ( ! hasSetup ) {
		if ( ! isEnabled ) {
			return <EnableButton />;
		}

		return <ManageButton />;
	}

	// This isolates core gateways that include setup
	if ( ! hasPlugins ) {
		if ( isEnabled ) {
			return <ManageButton />;
		}

		return <SetupButton />;
	}

	if ( ! needsSetup ) {
		if ( ! isEnabled ) {
			return <EnableButton />;
		}

		return <ManageButton />;
	}

	if ( isInstalled && hasPlugins ) {
		return (
			<Button
				className={ classes }
				isPrimary={ isRecommended }
				isSecondary={ ! isRecommended }
				isBusy={ isBusy }
				disabled={ isBusy }
				onClick={ () => handleClick() }
			>
				{ __( 'Finish setup', 'woocommerce' ) }
			</Button>
		);
	}

	return <SetupButton />;
};
