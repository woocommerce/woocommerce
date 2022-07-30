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
	toggleButtonProps: Props;
};

export const ComboBox = ( {
	comboBoxProps,
	inputProps,
	toggleButtonProps,
}: ComboBoxProps ) => {
	return (
		<div { ...comboBoxProps }>
			<input { ...inputProps } />
			<button { ...toggleButtonProps } aria-label={ 'Toggle menu' }>
				&#8595;
			</button>
		</div>
	);
};
