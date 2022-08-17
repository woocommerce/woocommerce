/**
 * External dependencies
 */
import { createElement } from 'react';
import { Icon, search } from '@wordpress/icons';

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
			className="woocommerce-experimental-select-control__combox-box"
		>
			<input { ...inputProps } />
			<Icon
				className="woocommerce-experimental-select-control__combox-box-icon"
				icon={ search }
			/>
		</div>
	);
};
