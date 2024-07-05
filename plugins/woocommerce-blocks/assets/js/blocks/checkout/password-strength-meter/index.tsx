/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { passwordStrength } from 'check-password-strength';
import { usePrevious } from '@woocommerce/base-hooks';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';

declare global {
	interface Window {
		zxcvbn: ( password: string ) => {
			score: number;
		};
	}
}

const scoreDescriptions = [
	__( 'Too weak', 'woocommerce' ),
	__( 'Weak', 'woocommerce' ),
	__( 'Medium', 'woocommerce' ),
	__( 'Strong', 'woocommerce' ),
	__( 'Very strong', 'woocommerce' ),
];

/**
 * Renders a password strength meter.
 *
 * Uses zxcvbn to calculate the password strength if available, otherwise falls back to check-password-strength which
 * does not include dictionaries of common passwords.
 */
const PasswordStrengthMeter = ( {
	password = '',
	onChange,
}: {
	password: string;
	onChange?: ( strength: number ) => void;
} ): JSX.Element | null => {
	let strength = -1;

	if ( password.length > 0 ) {
		if ( typeof window.zxcvbn === 'undefined' ) {
			const result = passwordStrength( password );
			strength = result.id;
		} else {
			const result = window.zxcvbn( password );
			strength = result.score;
		}
	}

	const previousStrength = usePrevious( strength );

	useEffect( () => {
		if ( strength !== previousStrength && onChange ) {
			onChange( strength );
		}
	}, [ strength, previousStrength, onChange ] );

	if ( strength === -1 ) {
		return null;
	}

	return (
		<meter
			className="wc-block-components-address-form__password-strength"
			aria-label={ __( 'Password strength', 'woocommerce' ) }
			min={ 0 }
			max={ 4 }
			value={ strength > -1 ? strength : 0 }
			data-textvalue={ scoreDescriptions[ strength ] || '' }
		>
			{ strength }
		</meter>
	);
};

export default PasswordStrengthMeter;
