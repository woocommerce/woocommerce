/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { ValidatedTextInput } from '@woocommerce/blocks-components';
import { useDispatch, useSelect } from '@wordpress/data';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import PasswordStrengthMeter from '../../password-strength-meter';

const CreatePassword = () => {
	const [ passwordStrength, setPasswordStrength ] = useState( 0 );
	const { customerPassword } = useSelect( ( select ) => {
		const store = select( CHECKOUT_STORE_KEY );
		return {
			customerPassword: store.getCustomerPassword(),
		};
	} );
	const { __internalSetCustomerPassword } = useDispatch( CHECKOUT_STORE_KEY );

	return (
		<ValidatedTextInput
			type="password"
			label={ __( 'Create a password', 'woocommerce' ) }
			className={ `wc-block-components-address-form__password` }
			value={ customerPassword }
			required={ true }
			errorId={ 'account-password' }
			customValidityMessage={ (
				validity: ValidityState
			): string | undefined => {
				if (
					validity.valueMissing ||
					validity.badInput ||
					validity.typeMismatch
				) {
					return __( 'Please enter a valid password', 'woocommerce' );
				}
			} }
			customValidation={ ( inputObject ) => {
				if ( passwordStrength < 2 ) {
					inputObject.setCustomValidity(
						__( 'Please create a stronger password', 'woocommerce' )
					);
					return false;
				}
				return true;
			} }
			onChange={ ( value: string ) =>
				__internalSetCustomerPassword( value )
			}
			feedback={
				<PasswordStrengthMeter
					password={ customerPassword }
					onChange={ ( strength: number ) =>
						setPasswordStrength( strength )
					}
				/>
			}
		/>
	);
};

export default CreatePassword;
