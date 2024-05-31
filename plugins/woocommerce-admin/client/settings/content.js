/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useRef, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SettingsCheckbox } from './components';
import { set } from 'lodash';

export const Content = ( { data } ) => {
	const { settings } = data;
	const [ formData, setFormData ] = useState( {} );
	const formRef = useRef( null );

	const gatherFormInputs = () => {
		const formElements = formRef.current.querySelectorAll(
			'input' // For now. There will be more.
		);
		const data = {};
		formElements.forEach( ( input ) => {
			const value =
				// Only looking at checkboxes for now.
				input.type === input.checked ? 'yes' : 'no';
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

	const handleSubmit = ( event ) => {
		event.preventDefault();
		console.log( 'Form submitted:', formData );
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
					default:
						return null;
				}
			} ) }
			<Button variant="primary" type="submit">
				{ __( 'Save changes', 'woocommerce' ) }
			</Button>
		</form>
	);
};
