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
const ToggleField: React.FC< ToggleFieldProps > = ( {
	label,
	value,
	onChange,
	tooltip,
	disabled = false,
} ) => {
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
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore disabled prop exists
			disabled={ disabled }
		/>
	);
};

export default ToggleField;
