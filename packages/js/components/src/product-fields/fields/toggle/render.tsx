/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { BaseProductFieldProps } from '../types';
import { Tooltip } from '../../../tooltip';

type ToggleFieldProps = BaseProductFieldProps< boolean > & {
	tooltip?: string;
};
const ToggleField = ( {
	label,
	value,
	onChange,
	tooltip,
	disabled = false,
}: ToggleFieldProps ) => {
	return (
		<ToggleControl
			label={
				<>
					{ label }
					{ tooltip && <Tooltip text={ tooltip } /> }
				</>
			}
			checked={ value }
			onChange={ onChange }
			disabled={ disabled }
		/>
	);
};

export default ToggleField;
