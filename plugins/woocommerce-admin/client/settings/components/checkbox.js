/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

export const SettingsCheckbox = ( { setting, handleFormChange } ) => {
	const [ checked, setChecked ] = useState( 'yes' === setting.value );
	const onChange = ( value ) => {
		setChecked( value );
		handleFormChange( { id: setting.id, value: value ? 'yes' : 'no' } );
	};
	return (
		<div className="woocommerce-settings-element">
			<h4>{ setting.title }</h4>
			<CheckboxControl
				id={ setting.id }
				label={ setting.desc }
				onChange={ onChange }
				checked={ checked }
			/>
		</div>
	);
};
