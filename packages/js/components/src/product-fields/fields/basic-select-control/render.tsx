/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { BaseProductFieldProps } from '../types';

type SelectControlProps = typeof SelectControl extends React.FC< infer P >
	? P
	: never;

type SelectControlFieldProps = BaseProductFieldProps< string | string[] > &
	SelectControlProps;
const SelectControlField: React.FC< SelectControlFieldProps > = ( {
	label,
	value,
	onChange,
	multiple,
	options = [],
} ) => {
	return (
		// @ts-expect-error - The upstream type for `multiple` seems a bit odd, it says `false | undefined`.
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
