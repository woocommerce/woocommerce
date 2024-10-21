/**
 * External dependencies
 */
import { createSlotFill, Button, Notice } from '@wordpress/components';
import { getAdminLink } from '@woocommerce/settings';

import apiFetch from '@wordpress/api-fetch';

import { useState, createElement, useEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SETTINGS_SLOT_FILL_CONSTANT } from '../../settings/settings-slots';
import './style.scss';

const { Fill } = createSlotFill( SETTINGS_SLOT_FILL_CONSTANT );

const Blueprint = () => {
	const [ exportEnabled, setExportEnabled ] = useState( true );
	const [ exportAsZip, setExportAsZip ] = useState( false );
	const [ error, setError ] = useState( null );

	const steps = {
		Settings: 'setWCSettings',
		'Core Profiler Options': 'setWCCoreProfilerOptions',
		'Payment Gateways': 'setWCPaymentGateways',
		Shipping: 'setWCShipping',
		'Tax rates': 'setWCTaxRates',
		Plugins: 'installPlugin',
		Themes: 'installTheme',
		'Task Options': 'setWCTaskOptions',
	};

	const instructions = {
		Settings:
			'It includes all the settings in General, Accounts & Privacy, Emails, Integration, Site Visibility, Advanced.',
		'Core Profiler Options': 'It in includes the setup wizard options.',
		'Payment Gateways':
			'It includes all the settings in WooCommerce | Settings | Payments.',
		Shipping:
			'It includes all the settings in WooCommerce | Settings | Shipping.',
		'Tax rates':
			'It includes all the settings in WooCommerce | Settings | Tax.',
		Plugins: 'It includes the active plugins on the site.',
		Themes: 'It includes the active theme on the site.',
		'Task Options': 'It includes the state of the setup task list.',
	};
	// Initialize state to keep track of checkbox values
	const [ checkedState, setCheckedState ] = useState(
		Object.keys( steps ).reduce( ( acc, key ) => {
			acc[ key ] = true;
			return acc;
		}, {} )
	);

	const exportBlueprint = async ( _steps ) => {
		setExportEnabled( false );

		const linkContainer = document.getElementById(
			'download-link-container'
		);
		linkContainer.innerHTML = '';

		try {
			const response = await apiFetch( {
				path: '/blueprint/export',
				method: 'POST',
				data: {
					steps: _steps,
					export_as_zip: exportAsZip,
				},
			} );
			const link = document.createElement( 'a' );
			link.innerHTML =
				'Click here in case download does not start automatically';

			let url = null;

			if ( response.type === 'zip' ) {
				link.href = response.data;
				link.target = '_blank';
			} else {
				// Create a link element and trigger the download
				url = window.URL.createObjectURL(
					new Blob( [ JSON.stringify( response.data, null, 2 ) ] )
				);
				link.href = url;
				link.setAttribute( 'download', 'woo-blueprint.json' );
			}

			linkContainer.appendChild( link );

			link.click();
			if ( url ) {
				window.URL.revokeObjectURL( url );
			}
		} catch ( e ) {
			setError( e.message );
		}

		setExportEnabled( true );
	};

	// Handle checkbox change
	const handleOnChange = ( key ) => {
		setCheckedState( ( prevState ) => ( {
			...prevState,
			[ key ]: ! prevState[ key ],
		} ) );
	};

	useEffect( () => {
		const saveButton = document.getElementsByClassName(
			'woocommerce-save-button'
		)[ 0 ];
		if ( saveButton ) {
			saveButton.style.display = 'none';
		}
	} );
	return (
		<div className="blueprint-settings-slotfill">
			{ error && (
				<Notice
					status="error"
					onRemove={ () => {
						setError( null );
					} }
					isDismissible
				>
					{ error }
				</Notice>
			) }
			<p className="blueprint-settings-slotfill-description">
				{ __(
					'Blueprints are setup files that contain all the installation instructions. including plugins, themes and settings. Ease the setup process, allow teams to apply each others’ changes and much more.',
					'woocommerce'
				) }
			</p>
			<p>
				<strong>
					Please{ ' ' }
					<a
						href="https://automattic.survey.fm/woocommerce-blueprint-survey"
						target="_blank"
						rel="noreferrer"
					>
						complete the survey
					</a>{ ' ' }
					to help shape the direction of this feature!
				</strong>
			</p>
			<h3>Import</h3>
			<p>
				You can import the schema on the{ ' ' }
				<a
					href={ getAdminLink(
						'admin.php?page=wc-admin&path=%2Fsetup-wizard&step=intro-builder'
					) }
				>
					builder setup page
				</a>
				{ ', or use the import WP CLI command ' }
				<br />
				<code>wp wc blueprint import path-to-woo-blueprint.json</code>.
			</p>
			<p></p>
			<h3>Export</h3>
			<p>
				Choose the items to include in your blueprint file, or use the
				export WP CLI command <br />
				<code>wp wc blueprint export save-to-path.json</code>{ ' ' }
			</p>
			{ Object.entries( steps ).map( ( [ key, value ] ) => (
				<div key={ key } className="woo-blueprint-export-step">
					<input
						type="checkbox"
						id={ key }
						name={ key }
						value={ value }
						checked={ checkedState[ key ] }
						onChange={ () => handleOnChange( key ) }
					/>
					<label htmlFor={ key }>{ key }</label>
					<p className="woo-blueprint-export-step-desc">
						{ instructions[ key ] }
					</p>
				</div>
			) ) }
			<Button
				isLink
				onClick={ () => {
					setCheckedState( ( prevState ) => {
						const newState = {};
						Object.entries( prevState ).forEach(
							( [ key, value ] ) => {
								newState[ key ] = ! value;
							}
						);
						return newState;
					} );
				} }
			>
				<p>Toggle selections</p>
			</Button>
			<div id="download-link-container"></div>
			<h4>Options</h4>
			<div>
				<input
					type="checkbox"
					id="export-as-zip"
					name={ 'export-as-zip' }
					value={ 'yes' }
					checked={ exportAsZip }
					onChange={ () => {
						setExportAsZip( ! exportAsZip );
					} }
				/>
				<label htmlFor="export-as-zip">
					Export as a zip (Experimental)
				</label>
			</div>
			<br></br>
			<Button
				isPrimary
				onClick={ () => {
					const selectedSteps = [];
					Object.entries( checkedState ).forEach(
						( [ key, value ] ) => {
							if ( value ) {
								selectedSteps.push( steps[ key ] );
							}
						}
					);
					exportBlueprint( selectedSteps );
				} }
				disabled={ ! exportEnabled }
				isBusy={ ! exportEnabled }
			>
				{ __( 'Export', 'woocommerce' ) }
			</Button>
			<p>Export may take some time depending on your network speed.</p>
		</div>
	);
};

const BlueprintSlotfill = () => {
	return (
		<Fill>
			<Blueprint />
		</Fill>
	);
};

export const registerBlueprintSlotfill = () => {
	registerPlugin( 'woocommerce-admin-blueprint-settings-slotfill', {
		scope: 'woocommerce-blueprint-settings',
		render: BlueprintSlotfill,
	} );
};
