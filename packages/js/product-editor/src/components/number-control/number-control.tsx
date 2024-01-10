/**
 * External dependencies
 */
import {
	createElement,
	Fragment,
	useEffect,
	useRef,
	useState,
} from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';
import classNames from 'classnames';
import { plus, reset } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	Button,
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
import { InputChangeCallback } from '@wordpress/components/build-types/input-control/types';

/**
 * Internal dependencies
 */
import { useNumberInputProps } from '../../hooks/use-number-input-props';
import { Label } from '../label/label';

export type NumberProps = {
	value: string;
	onChange: InputChangeCallback;
	label: string;
	suffix?: string;
	help?: string;
	error?: string;
	placeholder?: string;
	onBlur?: () => void;
	required?: boolean;
	tooltip?: string;
	disabled?: boolean;
	step?: number;
};

export const NumberControl: React.FC< NumberProps > = ( {
	value,
	onChange,
	label,
	suffix,
	help,
	error,
	onBlur,
	required,
	tooltip,
	placeholder,
	disabled,
	step = 1,
}: NumberProps ) => {
	const id = useInstanceId( BaseControl, 'product_number_field' ) as string;
	const [ isFocused, setIsFocused ] = useState( false );
	const unfocusIfOutside = ( event: React.FocusEvent ) => {
		if (
			! document
				.getElementById( id )
				?.parentElement?.contains( event.relatedTarget )
		) {
			setIsFocused( false );
			onBlur?.();
		}
	};
	const inputProps = useNumberInputProps( {
		value: value || '',
		onChange,
		onFocus: () => setIsFocused( true ),
	} );

	const [ increment, setIncrement ] = useState( 0 );

	const timeoutRef = useRef< number | null >( null );

	const incrementValue = () =>
		// @ts-expect-error Fix this, its not type safe.
		onChange( String( parseFloat( value || '0' ) + increment ) );

	useEffect( () => {
		if ( increment !== 0 ) {
			timeoutRef.current = setTimeout( incrementValue, 100 );
		} else if ( timeoutRef.current ) {
			clearTimeout( timeoutRef.current );
		}
		return () => {
			if ( timeoutRef.current ) {
				clearTimeout( timeoutRef.current );
			}
		};
	}, [ increment, value ] );

	return (
		<BaseControl
			className={ classNames( {
				'has-error': error,
			} ) }
			id={ id }
			label={
				<Label
					label={ label }
					required={ required }
					tooltip={ tooltip }
				/>
			}
			help={ error || help }
		>
			<InputControl
				{ ...inputProps }
				step={ step }
				disabled={ disabled }
				autoComplete="off"
				id={ id }
				className="woocommerce-number-control"
				suffix={
					<>
						{ suffix }
						{ isFocused && (
							<>
								<Button
									className="woocommerce-number-control__increment"
									icon={ plus }
									onMouseDown={ () => {
										// @ts-expect-error Fix this, its not type safe.
										onChange(
											String(
												parseFloat( value || '0' ) +
													step
											)
										);
										setIncrement( step );
									} }
									onMouseUp={ () => setIncrement( 0 ) }
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
									className="woocommerce-number-control__decrement"
									onBlur={ unfocusIfOutside }
									onMouseDown={ () => {
										// @ts-expect-error Fix this, its not type safe.
										onChange(
											String(
												parseFloat( value || '0' ) -
													step
											)
										);
										setIncrement( -step );
									} }
									onMouseUp={ () => setIncrement( 0 ) }
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
};
