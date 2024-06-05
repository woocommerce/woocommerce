/**
 * External dependencies
 */
import { Icon, chevronDown } from '@wordpress/icons';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';

type SelectProps = React.SelectHTMLAttributes< HTMLSelectElement > & {
	options: { value: string; label: string }[];
	label: string;
	onChange: ( newVal: string ) => void;
};

export const Select = ( props: SelectProps ) => {
	const { onChange, options, label, value, className, size, ...restOfProps } =
		props;

	const selectOnChange = useCallback(
		( event: React.ChangeEvent< HTMLSelectElement > ) => {
			console.log( 'change happen', event.target.value );
			onChange( event.target.value );
		},
		[ onChange ]
	);

	return (
		<div className="wc-blocks-components-select">
			<div className="wc-blocks-components-select__container">
				<label className="wc-blocks-components-select__label">
					{ label }
				</label>
				<select
					className={ `wc-blocks-components-select__select ${
						className || ''
					}` }
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
