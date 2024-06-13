/**
 * External dependencies
 */
import { Icon, chevronDown } from '@wordpress/icons';
import { useCallback, useId } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';

type SelectProps = Omit<
	React.SelectHTMLAttributes< HTMLSelectElement >,
	'onChange'
> & {
	options: { value: string; label: string }[];
	label: string;
	onChange: ( newVal: string ) => void;
};

export const Select = ( props: SelectProps ) => {
	const { onChange, options, label, value, className, size, ...restOfProps } =
		props;

	const selectOnChange = useCallback(
		( event: React.ChangeEvent< HTMLSelectElement > ) => {
			onChange( event.target.value );
		},
		[ onChange ]
	);

	const generatedId = useId();

	const inputId =
		restOfProps.id || `wc-blocks-components-select-${ generatedId }`;

	return (
		<div className="wc-blocks-components-select">
			<div className="wc-blocks-components-select__container">
				<label
					htmlFor={ inputId }
					className="wc-blocks-components-select__label"
				>
					{ label }
				</label>
				<select
					className={ `wc-blocks-components-select__select ${
						className || ''
					}` }
					id={ inputId }
					size={ size !== undefined ? size : 1 }
					onChange={ selectOnChange }
					value={ value }
					{ ...restOfProps }
				>
					{ options.map( ( option ) => (
						<option
							key={ option.value }
							value={ option.value }
							data-alternate-values={ `[${ option.label }]` }
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
	);
};
