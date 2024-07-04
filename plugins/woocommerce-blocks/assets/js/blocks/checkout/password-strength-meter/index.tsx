/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { passwordStrength } from 'check-password-strength';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Renders a password strength meter.
 */
const PasswordStrengthMeter = ( {
	password = '',
}: {
	password: string;
} ): JSX.Element => {
	const strength = passwordStrength( password, [
		{
			id: 0,
			value: '',
			minDiversity: 0,
			minLength: 0,
		},
		{
			id: 1,
			value: 'Too weak',
			minDiversity: 0,
			minLength: 1,
		},
		{
			id: 2,
			value: 'Weak',
			minDiversity: 2,
			minLength: 4,
		},
		{
			id: 3,
			value: 'Fair',
			minDiversity: 3,
			minLength: 8,
		},
		{
			id: 4,
			value: 'Strong',
			minDiversity: 4,
			minLength: 10,
		},
	] );

	return (
		<meter
			className="wc-block-components-address-form__password-strength"
			aria-label={ __( 'Password strength', 'woocommerce' ) }
			min={ 0 }
			max={ 4 }
			value={ strength.id }
			data-textvalue={ strength.value }
		>
			{ strength.value }
		</meter>
	);
};

export default PasswordStrengthMeter;
