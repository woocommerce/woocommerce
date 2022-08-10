/**
 * External dependencies
 */
import { createElement } from 'react';

/**
 * Internal dependencies
 */
import { Props } from './types';

type ComboBoxProps = {
	comboBoxProps: Props;
	inputProps: Props;
};

export const ComboBox = ( { comboBoxProps, inputProps }: ComboBoxProps ) => {
	return (
		<div
			{ ...comboBoxProps }
			className="woocommerce-select-control__combox-box"
		>
			<input { ...inputProps } />
		</div>
	);
};
