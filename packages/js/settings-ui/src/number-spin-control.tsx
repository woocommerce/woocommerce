/**
 * External dependencies
 */
import { speak } from '@wordpress/a11y';
import { BaseControl, Button } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import type { ReactNode } from 'react';

export type NumberSpinControlProps = {
	id: string;
	label?: string;
	help?: ReactNode;
	value: string;
	placeholder?: string;
	disabled?: boolean;
	onChange: ( next: string ) => void;
	inputAttributes?: Record< string, string | number | boolean >;
};

const plusIcon = (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		width="24"
		height="24"
		aria-hidden="true"
		focusable="false"
	>
		<path d="M11 12.5V17.5H12.5V12.5H17.5V11H12.5V6H11V11H6V12.5H11Z" />
	</svg>
);

const minusIcon = (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		width="24"
		height="24"
		aria-hidden="true"
		focusable="false"
	>
		<path d="M7 11.25h10v1.5H7z" />
	</svg>
);

const toFiniteNumber = ( raw: unknown ): number | undefined => {
	if ( typeof raw === 'number' && Number.isFinite( raw ) ) {
		return raw;
	}

	if ( typeof raw === 'string' && raw.trim() !== '' ) {
		const parsed = Number( raw );

		if ( Number.isFinite( parsed ) ) {
			return parsed;
		}
	}

	return undefined;
};

const decimalPlaces = ( value: number ) => {
	const normalized = String( value ).toLowerCase();

	if ( normalized.includes( 'e-' ) ) {
		const [ coefficient, exponent ] = normalized.split( 'e-' );
		const coefficientDecimals = coefficient.split( '.' )[ 1 ]?.length ?? 0;
		return Number( exponent ) + coefficientDecimals;
	}

	const fraction = normalized.split( '.' )[ 1 ];
	return fraction ? fraction.length : 0;
};

const stepDecimals = ( ...values: number[] ) =>
	Math.max( ...values.map( decimalPlaces ) );

const MAX_TO_FIXED_PRECISION = 100;

/**
 * A number input with explicit +/- spin buttons, per the settings designs.
 *
 * Composed from stable @wordpress/components APIs only; the native browser
 * spinner is hidden via CSS and stepping is handled by the buttons, while
 * typing and keyboard arrows keep the native input behavior.
 */
export const NumberSpinControl = ( {
	id,
	label,
	help,
	value,
	placeholder,
	disabled,
	onChange,
	inputAttributes,
}: NumberSpinControlProps ) => {
	const min = toFiniteNumber( inputAttributes?.min );
	const max = toFiniteNumber( inputAttributes?.max );
	const parsedStep = toFiniteNumber( inputAttributes?.step );
	// A zero or negative step would make the buttons no-ops or invert them;
	// fall back to 1 like the native number input does for an invalid step.
	const step =
		typeof parsedStep === 'number' && parsedStep > 0 ? parsedStep : 1;
	const current = toFiniteNumber( value );

	const stepBy = ( direction: 1 | -1 ) => {
		let next = ( current ?? 0 ) + direction * step;

		if ( typeof min !== 'undefined' ) {
			next = Math.max( min, next );
		}

		if ( typeof max !== 'undefined' ) {
			next = Math.min( max, next );
		}

		const requiredPrecision = stepDecimals(
			step,
			current ?? 0,
			min ?? 0,
			max ?? 0
		);
		const precision = Math.min(
			Math.max( requiredPrecision, 0 ),
			MAX_TO_FIXED_PRECISION
		);
		const nextValue =
			requiredPrecision > MAX_TO_FIXED_PRECISION
				? String( next )
				: String( Number( next.toFixed( precision ) ) );

		onChange( nextValue );
		// Focus stays on the spin button while the input updates, so the
		// new value must be announced to assistive technology explicitly.
		speak( nextValue );
	};

	const incrementDisabled =
		disabled ||
		( typeof max !== 'undefined' &&
			typeof current !== 'undefined' &&
			current >= max );
	const decrementDisabled =
		disabled ||
		( typeof min !== 'undefined' &&
			typeof current !== 'undefined' &&
			current <= min );

	const incrementLabel = label
		? sprintf(
				// translators: %s: the label of the number field being stepped.
				__( 'Increment %s', 'woocommerce' ),
				label
		  )
		: __( 'Increment', 'woocommerce' );
	const decrementLabel = label
		? sprintf(
				// translators: %s: the label of the number field being stepped.
				__( 'Decrement %s', 'woocommerce' ),
				label
		  )
		: __( 'Decrement', 'woocommerce' );

	return (
		<BaseControl
			className="wc-settings-ui__control"
			id={ id }
			label={ label }
			help={ help }
			__nextHasNoMarginBottom
		>
			<div className="wc-settings-ui__number-control">
				{ /* Schema-provided attributes are spread first so they can
				     never override the controlled props below. */ }
				<input
					{ ...inputAttributes }
					className="wc-settings-ui__number-control-input"
					type="number"
					id={ id }
					value={ value }
					placeholder={ placeholder }
					disabled={ disabled }
					aria-describedby={ help ? `${ id }__help` : undefined }
					onChange={ ( event ) =>
						onChange( event.currentTarget.value )
					}
				/>
				<div className="wc-settings-ui__number-control-spin-buttons">
					<Button
						size="small"
						icon={ plusIcon }
						label={ incrementLabel }
						disabled={ incrementDisabled }
						accessibleWhenDisabled
						onClick={ () => stepBy( 1 ) }
					/>
					<Button
						size="small"
						icon={ minusIcon }
						label={ decrementLabel }
						disabled={ decrementDisabled }
						accessibleWhenDisabled
						onClick={ () => stepBy( -1 ) }
					/>
				</div>
			</div>
		</BaseControl>
	);
};
