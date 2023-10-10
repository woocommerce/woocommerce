/**
 * External dependencies
 */
import { ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

export default function ActivationToggle( props: { checked: boolean } ) {
	const [ value, setValue ] = useState( props.checked );

	return (
		<ToggleControl
			checked={ value }
			onChange={ () => setValue( ( state ) => ! state ) }
			className="woocommerce-marketplace__my-subscriptions__activation"
		/>
	);
}
