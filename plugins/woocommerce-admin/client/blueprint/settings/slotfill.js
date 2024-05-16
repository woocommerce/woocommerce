/**
 * External dependencies
 */
import { createSlotFill, Button } from '@wordpress/components';
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
	const steps = {
		Settings: 'configureSettings',
		'Core Profiler Settings': 'configureCoreProfiler',
		'Payment Gateways': 'configurePaymentGateways',
		Shipping: 'configureShipping',
		'Tax rates': 'configureTaxRates',
		Plugins: 'installPlugins',
		Themes: 'installThemes',
		'Task Options': 'configureTaskOptions',
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

		const response = await apiFetch( {
			path: '/blueprint/export',
			method: 'POST',
			data: {
				steps: _steps,
				export_as_zip: exportAsZip,
			},
		} );

		const link = document.createElement( 'a' );

		if ( response.type === 'zip' ) {
			link.href = response.data;
			link.target = '_blank';
		} else {
			// Create a link element and trigger the download
			const url = window.URL.createObjectURL(
				new Blob( [ JSON.stringify( response.data, null, 2 ) ] )
			);
			link.href = url;
			link.setAttribute( 'download', 'woo-blueprint.json' );
		}

		document.body.appendChild( link );
		link.click();
		document.body.removeChild( link );
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
			<p className="blueprint-settings-slotfill-description">
				{ __( 'Import/Export your Blueprint schema.', 'woocommerce' ) }
			</p>
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
				Toggle selections
			</Button>
			<br />
			<br />
			{ Object.entries( steps ).map( ( [ key, value ] ) => (
				<div key={ key }>
					<input
						type="checkbox"
						id={ key }
						name={ key }
						value={ value }
						checked={ checkedState[ key ] }
						onChange={ () => handleOnChange( key ) }
					/>
					<label htmlFor={ key }>{ key }</label>
				</div>
			) ) }
			<br />
			<p>
				Export can take a few seconds depending on your network speed.
			</p>
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
				<label htmlFor="export-as-zip">Export as a zip (Experimental)</label>
			</div>
			<p>
				You can import the schema on the{ ' ' }
				<a
					href={ getAdminLink(
						'admin.php?page=wc-admin&path=%2Fsetup-wizard&step=intro-builder'
					) }
				>
					builder setup page
				</a>{ ' ' }
				or use the import WP CLI command.
			</p>
			<h3>WP CLI Commands</h3>
			<b>Import:</b>{ ' ' }
			<code>wp wc blueprint import path-to-woo-blueprint.json</code>
			<br /> <br />
			<b>Export:</b> <code>wp wc blueprint export save-to-path.json</code>
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
