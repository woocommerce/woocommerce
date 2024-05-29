/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';

export const SettingsCheckbox = ( { setting } ) => {
	return (
		<div>
			<h3>{ setting.title }</h3>
			<CheckboxControl
				label={ setting.desc }
				onChange={ () => console.log( 'change' ) }
				selected={ true }
			/>
		</div>
	);
};
