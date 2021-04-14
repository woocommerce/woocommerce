/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import { getAdminLink } from '@woocommerce/wc-admin-settings';
import { updateQueryString } from '@woocommerce/navigation';
import { useState } from '@wordpress/element';

export const Action = ( {
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

	if ( hasSetup && ! isConfigured ) {
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
					{ __( 'Set up', 'woocommerce-admin' ) }
				</Button>
			</div>
		);
	}

	if ( ( hasSetup && isConfigured ) || ( ! hasSetup && isEnabled ) ) {
		if ( ! manageUrl ) {
			return null;
		}

		return (
			<div>
				<Button
					className={ classes }
					isSecondary
					href={ getAdminLink(
						'admin.php?page=wc-settings&tab=checkout&section=' +
							methodKey
					) }
				>
					{ __( 'Manage', 'woocommerce-admin' ) }
				</Button>
			</div>
		);
	}

	return (
		<Button
			className={ classes }
			isSecondary
			onClick={ () => markConfigured( methodKey ) }
		>
			{ __( 'Enable', 'woocommerce-admin' ) }
		</Button>
	);
};
