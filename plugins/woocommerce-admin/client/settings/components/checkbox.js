/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';

export const SettingsCheckbox = ( { setting } ) => {
	return (
		<div className="woocommerce-settings-element">
			<h4>{ setting.title }</h4>
			<CheckboxControl
				label={ setting.desc }
				onChange={ () => console.log( 'change' ) }
				checked={ 'yes' === setting.value }
			/>
		</div>
	);
};
