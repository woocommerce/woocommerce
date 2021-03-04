/**
 * External dependencies.
 */
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

export const TriggerWooCommerceAdminInstall = () => {
	const [ isInstalling, setIsInstalling ] = useState( false );
	const [ hasInstalled, setHasInstalled ] = useState( false );
	const [ errorMessage, setErrorMessage ] = useState( false );

	async function triggerInstall() {
		if ( ! confirm( 'Are you sure you want to trigger a WCA install?' ) ) {
			return;
		}

		setIsInstalling( true );
		setHasInstalled( false );
		setErrorMessage( false );

		try {
			await apiFetch( {
				path: '/wc-admin-test-helper/tools/trigger-wca-install/v1',
				method: 'POST',
			} );
			setHasInstalled( true );
		} catch ( ex ) {
			setErrorMessage( ex.message );
		}

		setIsInstalling( false );
	}

	return (
		<>
			<p><strong>Trigger WooCommerce Admin install</strong></p>
			<p>
				This will trigger a WooCommerce Admin install, which usually
				happens when a new version (or new install) of WooCommerce
				Admin is installed. Triggering the install manually can
				run tasks such as removing obsolete admin notes.
				<br/>
				<Button
					onClick={ triggerInstall }
					disabled={ isInstalling }
					isPrimary
				>
					Trigger WCA install
				</Button>
				<div className="woocommerce-admin-test-helper__action-status">
					{ isInstalling && 'Running install, please wait' }
					{ hasInstalled && 'Install completed' }
					{ errorMessage && (
						<>
							<strong>Error:</strong> { errorMessage }
						</>
					) }
				</div>
			</p>
		</>
	);
};
