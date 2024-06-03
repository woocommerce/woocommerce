/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useRef, useEffect } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { SettingsCheckbox, SettingsInput } from './components';

export const Content = ( { data } ) => {
	const { settings } = data;
	const [ formData, setFormData ] = useState( {} );
	const [ isBusy, setIsBusy ] = useState( false );
	const formRef = useRef( null );
	const { createNotice } = useDispatch( 'core/notices' );

	const gatherFormInputs = () => {
		const formElements = formRef.current.querySelectorAll(
			'input' // For now. There will be more.
		);

		const data = {};
		formElements.forEach( ( input ) => {
			const value =
				// Only looking at checkboxes for now.
				input.checked ? 'yes' : 'no';
			data[ input.id || input.name ] = value;
		} );

		setFormData( data );
	};

	const handleFormChange = ( { id, value } ) => {
		setFormData( {
			...formData,
			[ id ]: value,
		} );
	};

	const handleSubmit = async ( event ) => {
		event.preventDefault();

		try {
			setIsBusy( true );
			const response = await fetch( '/wp-json/wc-admin/options', {
				method: 'POST',
				headers: { 'content-type': 'application/json' },
				body: JSON.stringify( formData ),
			} );

			setIsBusy( false );

			if ( response.status === 200 ) {
				createNotice(
					'success',
					__( 'Settings saved successfully', 'woocommerce' )
				);
			} else {
				throw new Error();
			}
		} catch ( error ) {
			createNotice(
				'fail',
				__( 'Error saving settings', 'woocommerce' )
			);
			console.error( 'Error saving settings', error );
		}
	};

	// Run once to gather inputs on initial render
	useEffect( () => {
		gatherFormInputs();
	}, [] );

	if ( ! settings ) {
		return null;
	}

	return (
		<form ref={ formRef } id="mainform" onSubmit={ handleSubmit }>
			{ settings.map( ( setting, idx ) => {
				const key = setting.id || setting.title || idx;
				switch ( setting.type ) {
					case 'title':
						return (
							<div
								key={ key }
								className="woocommerce-settings-element"
							>
								<h3>{ setting.title }</h3>
							</div>
						);
					case 'checkbox':
						return (
							<SettingsCheckbox
								setting={ setting }
								key={ key }
								handleFormChange={ handleFormChange }
							/>
						);
					case 'slotfill_placeholder':
						return (
							<div
								key={ key }
								id={ setting.id }
								className={ setting.class }
							></div>
						);
					case 'text':
					case 'password':
					case 'datetime':
					case 'datetime-local':
					case 'date':
					case 'month':
					case 'time':
					case 'week':
					case 'number':
					case 'email':
					case 'url':
					case 'tel':
						return (
							<SettingsInput
								setting={ setting }
								key={ key }
								handleFormChange={ handleFormChange }
							/>
						);
					default:
						return null;
				}
			} ) }
			<Button variant="primary" type="submit" isBusy={ isBusy }>
				{ __( 'Save changes', 'woocommerce' ) }
			</Button>
		</form>
	);
};
