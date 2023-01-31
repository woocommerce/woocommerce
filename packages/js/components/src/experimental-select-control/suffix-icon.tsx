/**
 * External dependencies
 */
import { createElement } from 'react';
import { Icon } from '@wordpress/icons';

type SuffixIconProps = {
	icon: JSX.Element;
};

export const SuffixIcon = ( { icon }: SuffixIconProps ) => {
	return (
		<div className="woocommerce-experimental-select-control__suffix-icon">
			<Icon icon={ icon } size={ 24 } />
		</div>
	);
};
