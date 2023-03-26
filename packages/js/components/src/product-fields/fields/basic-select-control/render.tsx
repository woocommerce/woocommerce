/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { BaseProductFieldProps } from '../types';

type SelectControlFieldProps = BaseProductFieldProps< string | string[] > & {
	multiple?: boolean;
	options: SelectControl.Option[];
};
const SelectControlField: React.FC< SelectControlFieldProps > = ( {
	label,
	value,
	onChange,
	multiple,
	options = [],
} ) => {
	return (
		<SelectControl
			multiple={ multiple }
			label={ label }
			options={ options }
			onChange={ onChange }
			value={ value }
		/>
	);
};

export default SelectControlField;
