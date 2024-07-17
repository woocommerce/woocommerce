/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { useInstanceId } from '@wordpress/compose';
import { passwordStrength } from 'check-password-strength';
import { usePrevious } from '@woocommerce/base-hooks';
import { useEffect } from '@wordpress/element';
import clsx from 'clsx';

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
} ): React.ReactElement | null => {
	const instanceId = useInstanceId(
		PasswordStrengthMeter,
		'woocommerce-password-strength-meter'
	) as string;

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

	return (
		<div
			id={ instanceId }
			className={ clsx( 'wc-block-components-password-strength', {
				hidden: strength === -1,
			} ) }
		>
			<label
				htmlFor={ instanceId + '-meter' }
				className="screen-reader-text"
			>
				{ __( 'Password strength', 'woocommerce' ) }
			</label>
			<meter
				id={ instanceId + '-meter' }
				className="wc-block-components-password-strength__meter"
				min={ 0 }
				max={ 4 }
				value={ strength > -1 ? strength : 0 }
			>
				{ scoreDescriptions[ strength ] ?? '' }
			</meter>
			<div
				id={ instanceId + '-result' }
				className="wc-block-components-password-strength__result"
			>
				{ !! scoreDescriptions[ strength ] && (
					<>
						<span className="screen-reader-text" aria-live="polite">
							{ sprintf(
								/* translators: %s: Password strength */
								__(
									'Password strength: %1$s (%2$d characters long)',
									'woocommerce'
								),
								scoreDescriptions[ strength ],
								password.length
							) }
						</span>{ ' ' }
						<span aria-hidden={ true }>
							{ scoreDescriptions[ strength ] }
						</span>
					</>
				) }
			</div>
		</div>
	);
};

export default PasswordStrengthMeter;
