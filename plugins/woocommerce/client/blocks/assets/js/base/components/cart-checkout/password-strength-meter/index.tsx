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

export const getPasswordStrength = ( password: string ) => {
	if ( typeof window.zxcvbn === 'undefined' ) {
		return passwordStrength( password, [
			{
				id: 0,
				value: scoreDescriptions[ 0 ],
				minDiversity: 0,
				minLength: 0,
			},
			{
				id: 1,
				value: scoreDescriptions[ 1 ],
				minDiversity: 1,
				minLength: 4,
			},
			{
				id: 2,
				value: scoreDescriptions[ 2 ],
				minDiversity: 2,
				minLength: 8,
			},
			{
				id: 3,
				value: scoreDescriptions[ 3 ],
				minDiversity: 4,
				minLength: 12,
			},
			{
				id: 4,
				value: scoreDescriptions[ 4 ],
				minDiversity: 4,
				minLength: 20,
			},
		] ).id;
	}
	return window.zxcvbn( password ).score;
};

/**
 * Renders a password strength meter.
 *
 * Uses zxcvbn to calculate the password strength if available, otherwise falls back to check-password-strength which
 * does not include dictionaries of common passwords.
 */
export const PasswordStrengthMeter = ( {
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
		strength = getPasswordStrength( password );
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
			{ !! scoreDescriptions[ strength ] && (
				<div
					id={ instanceId + '-result' }
					className="wc-block-components-password-strength__result"
				>
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
				</div>
			) }
		</div>
	);
};

export default PasswordStrengthMeter;
