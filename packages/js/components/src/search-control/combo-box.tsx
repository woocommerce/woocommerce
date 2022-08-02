/**
 * External dependencies
 */
import { createElement } from 'react';

/**
 * Internal dependencies
 */
import { Props } from './types';
import './combo-box.scss';

type ComboBoxProps = {
	comboBoxProps: Props;
	inputProps: Props;
};

export const ComboBox = ( { comboBoxProps, inputProps }: ComboBoxProps ) => {
	return (
		<div
			{ ...comboBoxProps }
			className="woocommerce-search-control__combox-box"
		>
			<input { ...inputProps } />
		</div>
	);
};
