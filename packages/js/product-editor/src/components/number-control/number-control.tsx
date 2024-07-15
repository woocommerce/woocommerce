/**
 * External dependencies
 */
import {
	createElement,
	forwardRef,
	Fragment,
	isValidElement,
	useEffect,
	useRef,
	useState,
} from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';
import classNames from 'classnames';
import { plus, reset } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import type { ForwardedRef } from 'react';
import {
	BaseControl,
	Button,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useNumberInputProps } from '../../hooks/use-number-input-props';
import { Label } from '../label/label';

export type NumberProps = {
	id?: string;
	value: string;
	onChange: ( selected: string ) => void;
	label: string | JSX.Element;
	suffix?: string;
	help?: string;
	error?: string;
	placeholder?: string;
	onBlur?: () => void;
	onFocus?: () => void;
	required?: boolean;
	tooltip?: string;
	disabled?: boolean;
	step?: number;
	min?: number;
	max?: number;
};

const MEDIUM_DELAY = 500;
const SHORT_DELAY = 100;

export const NumberControl: React.FC< NumberProps > = forwardRef(
	(
		{
			id,
			value,
			onChange,
			label,
			suffix,
			help,
			error,
			onBlur,
			onFocus,
			required,
			tooltip,
			placeholder,
			disabled,
			step = 1,
			min = -Infinity,
			max = Infinity,
		}: NumberProps,
		ref: ForwardedRef< HTMLInputElement >
	) => {
		const instanceId = useInstanceId(
			BaseControl,
			'product_number_field'
		) as string;
		const identifier = id ?? instanceId;
		const [ isFocused, setIsFocused ] = useState( false );
		const unfocusIfOutside = ( event: React.FocusEvent ) => {
			if (
				! document
					.getElementById( identifier )
					?.parentElement?.contains( event.relatedTarget )
			) {
				setIsFocused( false );
				onBlur?.();
			}
		};

		function handleOnFocus() {
			setIsFocused( true );
			onFocus?.();
		}

		const inputProps = useNumberInputProps( {
			value: value || '',
			onChange,
			onFocus: handleOnFocus,
			min,
			max,
		} );

		const [ increment, setIncrement ] = useState( 0 );

		const timeoutRef = useRef< number | null >( null );

		const isInitialClick = useRef< boolean >( false );

		function incrementValue() {
			const newValue = parseFloat( value || '0' ) + increment;
			if ( newValue >= min && newValue <= max )
				onChange( String( newValue ) );
		}

		useEffect( () => {
			if ( increment !== 0 ) {
				timeoutRef.current = setTimeout(
					incrementValue,
					isInitialClick.current ? MEDIUM_DELAY : SHORT_DELAY
				);
				isInitialClick.current = false;
			} else if ( timeoutRef.current ) {
				clearTimeout( timeoutRef.current );
			}
			return () => {
				if ( timeoutRef.current ) {
					clearTimeout( timeoutRef.current );
				}
			};
		}, [ increment, value ] );

		function resetIncrement() {
			setIncrement( 0 );
		}

		function handleIncrement( thisStep: number ) {
			const newValue = parseFloat( value || '0' ) + thisStep;
			if ( newValue >= min && newValue <= max ) {
				onChange( String( parseFloat( value || '0' ) + thisStep ) );
				setIncrement( thisStep );
				isInitialClick.current = true;
			}
		}

		return (
			<BaseControl
				className={ classNames( {
					'has-error': error,
				} ) }
				id={ identifier }
				label={
					isValidElement( label ) ? (
						label
					) : (
						<Label
							label={ label as string }
							required={ required }
							tooltip={ tooltip }
						/>
					)
				}
				help={ error || help }
			>
				<InputControl
					{ ...inputProps }
					ref={ ref }
					step={ step }
					disabled={ disabled }
					autoComplete="off"
					id={ identifier }
					className="woocommerce-number-control"
					suffix={
						<>
							{ suffix }
							{ isFocused && (
								<>
									<Button
										className="woocommerce-number-control__increment"
										icon={ plus }
										disabled={
											parseFloat( value || '0' ) >= max
										}
										onMouseDown={ () =>
											handleIncrement( step )
										}
										onMouseLeave={ resetIncrement }
										onMouseUp={ resetIncrement }
										onBlur={ unfocusIfOutside }
										isSmall
										aria-hidden="true"
										aria-label={ __(
											'Increment',
											'woocommerce'
										) }
										tabIndex={ -1 }
									/>
									<Button
										icon={ reset }
										disabled={
											parseFloat( value || '0' ) <= min
										}
										className="woocommerce-number-control__decrement"
										onBlur={ unfocusIfOutside }
										onMouseDown={ () =>
											handleIncrement( -step )
										}
										onMouseLeave={ resetIncrement }
										onMouseUp={ resetIncrement }
										isSmall
										aria-hidden="true"
										aria-label={ __(
											'Decrement',
											'woocommerce'
										) }
										tabIndex={ -1 }
									/>
								</>
							) }
						</>
					}
					placeholder={ placeholder }
					onBlur={ unfocusIfOutside }
				/>
			</BaseControl>
		);
	}
);
