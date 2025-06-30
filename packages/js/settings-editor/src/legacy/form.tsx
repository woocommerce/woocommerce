/**
 * External dependencies
 */
import {
	createElement,
	useRef,
	useContext,
	useState,
} from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { DataForm } from '@wordpress/dataviews';
import { getNewPath } from '@woocommerce/navigation';
import { useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { useSettingsForm } from '../hooks/use-settings-form';
import { CustomView } from '../components/custom-view';
import { SettingsDataContext } from '../data';

export const Form = ( {
	settings,
	settingsData,
	settingsPage,
	activeSection,
}: {
	settings: SettingsField[];
	settingsData: SettingsData;
	settingsPage: SettingsPage;
	activeSection: string;
} ) => {
	const { data, fields, form, updateField } = useSettingsForm( settings );
	const formRef = useRef< HTMLFormElement >( null );
	const { setSettingsData } = useContext( SettingsDataContext );
	const [ isBusy, setIsBusy ] = useState( false );
	const { createNotice } = useDispatch( 'core/notices' );

	const getFormData = () => {
		if ( ! formRef.current ) {
			return {};
		}
		const formElements = formRef.current.querySelectorAll<
			HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
		>( 'input, select, textarea' );

		const formData: Record< string, string > = {};

		formElements.forEach( ( input ) => {
			const key = input.name || input.id;
			// Avoid generic Gutenberg input ids. This will require upstream fixes.
			if ( ! key || input.id?.startsWith( 'inspector-' ) ) {
				return;
			}

			formData[ key ] = input.value;
		} );

		return formData;
	};

	const handleSubmit = async (
		event: React.FormEvent< HTMLFormElement >
	) => {
		event.preventDefault();
		setIsBusy( true );

		const query: Record< string, string > = {
			page: 'wc-settings',
		};
		if ( settingsPage.slug !== 'general' ) {
			query.tab = settingsPage.slug;
		}
		if ( activeSection !== 'default' ) {
			query.section = activeSection;
		}

		const updatedData = getFormData();
		updatedData.save = 'Save changes';
		updatedData._wpnonce = settingsData._wpnonce;
		updatedData._w_http_referer = '/wp-admin/' + getNewPath( query );

		const payload = new FormData();
		for ( const [ key, value ] of Object.entries( updatedData ) ) {
			payload.append( key, value );
		}

		apiFetch( {
			path: addQueryArgs( '/wc-admin/legacy-settings', query ),
			method: 'POST',
			body: payload,
		} )
			.then( ( response ) => {
				const {
					data: { settingsData: responseSettingsData },
					status,
				} = response as {
					data: { settingsData: SettingsData };
					status: string;
				};

				if ( status === 'success' ) {
					setSettingsData( responseSettingsData );
					createNotice(
						'success',
						__( 'Settings saved successfully', 'woocommerce' )
					);
				} else {
					createNotice(
						'error',
						__( 'Failed to save settings', 'woocommerce' )
					);
				}
			} )
			.catch( ( error ) => {
				createNotice(
					'error',
					__( 'Failed to save settings: ', 'woocommerce' ) +
						error.message
				);
			} )
			.finally( () => {
				setIsBusy( false );
			} );
	};

	return (
		<form ref={ formRef } id="mainform" onSubmit={ handleSubmit }>
			{ settingsData.start && (
				<CustomView html={ settingsData.start.content } />
			) }
			{ settingsPage.start && (
				<CustomView html={ settingsPage.start.content } />
			) }
			<div className="woocommerce-settings-content">
				<DataForm
					fields={ fields }
					form={ form }
					data={ data }
					onChange={ updateField }
				/>
			</div>
			<div className="woocommerce-settings-content-footer">
				<Button
					variant="primary"
					type="submit"
					isBusy={ isBusy }
					disabled={ isBusy }
				>
					{ __( 'Save', 'woocommerce' ) }
				</Button>
			</div>
			{ settingsPage.end && (
				<CustomView html={ settingsPage.end.content } />
			) }
		</form>
	);
};
