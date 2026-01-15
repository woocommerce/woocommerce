/**
 * External dependencies
 */
import { Icon, chevronDown } from '@wordpress/icons';
import { useCallback, useId, useMemo, useEffect } from '@wordpress/element';
import { sprintf, __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import clsx from 'clsx';
import { validationStore } from '@woocommerce/block-data';
import { ValidationInputError } from '@woocommerce/blocks-components';
import { getFieldLabel } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import './style.scss';

export type SelectOption = {
	value: string;
	label: string;
	disabled?: boolean;
};

export type SelectProps = Omit<
	React.SelectHTMLAttributes< HTMLSelectElement >,
	'onChange'
> & {
	options: SelectOption[];
	label: string;
	onChange: ( newVal: string ) => void;
	errorId?: string;
	required?: boolean | undefined;
	errorMessage?: string | undefined;
	placeholder?: string | undefined;
};

export const Select = ( props: SelectProps ) => {
	const {
		onChange,
		options,
		label,
		value = '',
		className,
		size,
		errorId: incomingErrorId,
		required,
		errorMessage = __( 'Please select a valid option', 'woocommerce' ),
		placeholder,
		...restOfProps
	} = props;
	const selectOnChange = useCallback(
		( event: React.ChangeEvent< HTMLSelectElement > ) => {
			onChange( event.target.value );
		},
		[ onChange ]
	);
	const fieldLabel = getFieldLabel( label );
	const emptyOption: SelectOption = useMemo(
		() => ( {
			value: '',
			label:
				placeholder ??
				sprintf(
					// translators: %s will be label of the field. For example "country/region".
					__( 'Select a %s', 'woocommerce' ),
					fieldLabel
				),
			disabled: !! required,
		} ),
		[ placeholder, required, fieldLabel ]
	);

	const generatedId = useId();
	const inputId =
		restOfProps.id || `wc-blocks-components-select-${ generatedId }`;
	const errorId = incomingErrorId || inputId;

	const optionsWithEmpty = useMemo< SelectOption[] >( () => {
		return [ emptyOption ].concat( options );
	}, [ emptyOption, options ] );

	const { setValidationErrors, clearValidationError } =
		useDispatch( validationStore );

	const { error, validationErrorId } = useSelect(
		( select ) => {
			const store = select( validationStore );
			return {
				error: store.getValidationError( errorId ),
				validationErrorId: store.getValidationErrorId( errorId ),
			};
		},
		[ errorId ]
	);

	useEffect( () => {
		if ( ! required || value ) {
			clearValidationError( errorId );
		} else {
			setValidationErrors( {
				[ errorId ]: {
					message: errorMessage,
					hidden: true,
				},
			} );
		}
		return () => {
			clearValidationError( errorId );
		};
	}, [
		clearValidationError,
		value,
		errorId,
		errorMessage,
		required,
		setValidationErrors,
	] );

	const validationError = useSelect(
		( select ) => {
			const store = select( validationStore );
			return (
				store.getValidationError( errorId || '' ) || {
					hidden: true,
				}
			);
		},
		[ errorId ]
	);

	return (
		<div
			className={ clsx( className, {
				'has-error': ! validationError.hidden,
			} ) }
		>
			<div className="wc-blocks-components-select">
				<div className="wc-blocks-components-select__container">
					<label
						htmlFor={ inputId }
						className="wc-blocks-components-select__label"
					>
						{ label }
					</label>
					<select
						className="wc-blocks-components-select__select"
						id={ inputId }
						size={ size !== undefined ? size : 1 }
						onChange={ selectOnChange }
						value={ value }
						aria-invalid={
							error?.message && ! error?.hidden ? true : false
						}
						aria-errormessage={ validationErrorId }
						{ ...restOfProps }
					>
						{ optionsWithEmpty.map( ( option ) => (
							<option
								key={ option.value }
								value={ option.value }
								data-alternate-values={ `[${ option.label }]` }
								disabled={
									option.disabled !== undefined
										? option.disabled
										: false
								}
							>
								{ option.label }
							</option>
						) ) }
					</select>
					<Icon
						className="wc-blocks-components-select__expand"
						icon={ chevronDown }
					/>
				</div>
			</div>
			<ValidationInputError propertyName={ errorId } />
		</div>
	);
};
