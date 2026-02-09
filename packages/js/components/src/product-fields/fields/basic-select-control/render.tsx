/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { BaseProductFieldProps } from '../types';

type SelectControlProps = React.ComponentProps< typeof SelectControl >;
type SelectControlFieldProps = BaseProductFieldProps< string | string[] > & {
	multiple?: boolean;
	options: SelectControlProps[ 'options' ];
};
const SelectControlField = ( {
	label,
	value,
	onChange,
	multiple,
	options = [],
}: SelectControlFieldProps ) => {
	return (
		<>
			{ /* @ts-expect-error wrong type for multiple, should be boolean but explicitly set to true/false */ }
			<SelectControl
				multiple={ multiple }
				label={ label }
				options={ options }
				onChange={ onChange }
				value={ value }
			/>
		</>
	);
};

export default SelectControlField;
