/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';

function HistoricalDataSkipCheckbox( { checked, disabled, onChange } ) {
	return (
		<CheckboxControl
			className="woocommerce-settings-historical-data__skip-checkbox"
			checked={ checked }
			disabled={ disabled }
			label={ __( 'Skip previously imported customers and orders', 'woocommerce-admin' ) }
			onChange={ onChange }
		/>
	);
}

export default HistoricalDataSkipCheckbox;
