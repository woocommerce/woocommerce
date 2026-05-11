/**
 * External dependencies
 */
import { BaseControl } from '@wordpress/components';
import { Select } from '@wordpress/ui';

export type SelectFieldOption = {
	value: string;
	label: string;
	disabled?: boolean;
};

type SelectFieldProps = {
	label: string;
	value: string;
	options: SelectFieldOption[];
	onChange: ( value: string ) => void;
};

/**
 * Single-select form control built on the @wordpress/ui Select primitive.
 * Wraps Select.Root / Trigger / Popup / Item with the design-system label
 * styling from BaseControl, so every select in the experimental products
 * app renders consistently.
 */
export function SelectField( {
	label,
	value,
	options,
	onChange,
}: SelectFieldProps ) {
	return (
		// eslint-disable-next-line @wordpress/no-base-control-with-label-without-id
		<BaseControl label={ label }>
			<Select.Root
				value={ value }
				onValueChange={ ( nextValue ) => {
					if ( typeof nextValue === 'string' ) {
						onChange( nextValue );
					}
				} }
				items={ options }
			>
				<Select.Trigger aria-label={ label } />
				<Select.Popup>
					{ options.map( ( option ) => (
						<Select.Item
							key={ option.value }
							value={ option.value }
							label={ option.label }
							disabled={ option.disabled }
						>
							{ option.label }
						</Select.Item>
					) ) }
				</Select.Popup>
			</Select.Root>
		</BaseControl>
	);
}
