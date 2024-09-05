/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ValidatedTextInput } from '@woocommerce/blocks-components';

/**
 * Renders a phone number input.
 */
const PhoneNumber = ( {
	id = 'phone',
	errorId = 'phone',
	isRequired = false,
	value = '',
	onChange,
}: {
	id?: string;
	errorId?: string;
	isRequired: boolean;
	value: string;
	onChange: ( value: string ) => void;
} ): JSX.Element => {
	return (
		<ValidatedTextInput
			id={ id }
			errorId={ errorId }
			type="tel"
			autoComplete="tel"
			required={ isRequired }
			label={
				isRequired
					? __( 'Phone', 'woocommerce' )
					: __( 'Phone (optional)', 'woocommerce' )
			}
			value={ value }
			onChange={ onChange }
		/>
	);
};

export default PhoneNumber;
