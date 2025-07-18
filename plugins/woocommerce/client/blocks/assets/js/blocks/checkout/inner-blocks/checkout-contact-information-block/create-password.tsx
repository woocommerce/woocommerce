/**
 * External dependencies
 */
import PasswordStrengthMeter from '@woocommerce/base-components/cart-checkout/password-strength-meter';
import { checkoutStore, validationStore } from '@woocommerce/block-data';
import { ValidatedTextInput } from '@woocommerce/blocks-components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const CreatePassword = () => {
	const [ passwordStrength, setPasswordStrength ] = useState( 0 );
	const { customerPassword } = useSelect( ( select ) => {
		const store = select( checkoutStore );
		return {
			customerPassword: store.getCustomerPassword(),
		};
	}, [] );
	const { __internalSetCustomerPassword } = useDispatch( checkoutStore );
	const { setValidationErrors, clearValidationError } =
		useDispatch( validationStore );

	useEffect( () => {
		if ( ! customerPassword ) {
			return;
		}
		if ( passwordStrength < 2 ) {
			setValidationErrors( {
				'account-password': {
					message: __(
						'Please create a stronger password',
						'woocommerce'
					),
					hidden: true,
				},
			} );
			return;
		}
		clearValidationError( 'account-password' );
	}, [
		clearValidationError,
		customerPassword,
		passwordStrength,
		setValidationErrors,
	] );

	return (
		<ValidatedTextInput
			type="password"
			label={ __( 'Create a password', 'woocommerce' ) }
			className={ `wc-block-components-address-form__password` }
			value={ customerPassword }
			required={ true }
			errorId={ 'account-password' }
			onChange={ ( value: string ) => {
				__internalSetCustomerPassword( value );

				if ( ! value ) {
					setValidationErrors( {
						'account-password': {
							message: __(
								'Please enter a valid password',
								'woocommerce'
							),
							hidden: true,
						},
					} );
				}
			} }
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
