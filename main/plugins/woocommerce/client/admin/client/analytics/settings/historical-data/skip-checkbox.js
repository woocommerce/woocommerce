/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { importStore } from '@woocommerce/data';
import { withDispatch } from '@wordpress/data';

function HistoricalDataSkipCheckbox( { checked, disabled, setSkipPrevious } ) {
	const skipChange = ( value ) => {
		setSkipPrevious( value );
	};
	return (
		<CheckboxControl
			className="woocommerce-settings-historical-data__skip-checkbox"
			checked={ checked }
			disabled={ disabled }
			label={ __(
				'Skip previously imported customers and orders',
				'woocommerce'
			) }
			onChange={ skipChange }
		/>
	);
}

export default withDispatch( ( dispatch ) => {
	const { setSkipPrevious } = dispatch( importStore );
	return { setSkipPrevious };
} )( HistoricalDataSkipCheckbox );
