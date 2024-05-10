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
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useNumberInputProps } from '../../hooks/use-number-input-props';
import { Label } from '../label/label';

export type NumberProps = {
	value: string;
	onChange: ( selected: string ) => void;
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
	min?: number;
	max?: number;
};

const MEDIUM_DELAY = 500;

const SHORT_DELAY = 100;

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
	min = -Infinity,
	max = +Infinity,
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
		min,
		max,
	} );

	const [ increment, setIncrement ] = useState( 0 );

	const timeoutRef = useRef< number | null >( null );

	const isInitialClick = useRef< boolean >( false );

	const incrementValue = () => {
		const newValue = parseFloat( value || '0' ) + increment;
		if ( newValue >= min && newValue <= max )
			onChange( String( newValue ) );
	};

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

	const resetIncrement = () => setIncrement( 0 );

	const handleIncrement = ( thisStep: number ) => {
		const newValue = parseFloat( value || '0' ) + thisStep;
		if ( newValue >= min && newValue <= max ) {
			onChange( String( parseFloat( value || '0' ) + thisStep ) );
			setIncrement( thisStep );
			isInitialClick.current = true;
		}
	};

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
};
