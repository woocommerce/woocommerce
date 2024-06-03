/**
 * External dependencies
 */
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

export const SettingsInput = ( { setting } ) => {
	const { id, desc } = setting;
	const [ value, setValue ] = useState( setting.value );
	const onChange = ( value ) => {
		setValue( value );
	};
	return (
		<div className="woocommerce-settings-element">
			<h4>{ setting.title }</h4>
			<InputControl
				id={ id }
				label={ desc }
				onChange={ onChange }
				type={ setting.type }
				value={ value }
			/>
		</div>
	);
};
