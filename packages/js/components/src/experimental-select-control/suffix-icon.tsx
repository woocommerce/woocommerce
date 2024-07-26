/**
 * External dependencies
 */
import { createElement } from 'react';
import { Icon } from '@wordpress/icons';
import classNames from 'classnames';

type SuffixIconProps = {
	icon: JSX.Element;
	className?: string;
};

export const SuffixIcon = ( { className = '', icon }: SuffixIconProps ) => {
	return (
		<div
			className={ classNames(
				'woocommerce-experimental-select-control__suffix-icon',
				className
			) }
		>
			<Icon icon={ icon } size={ 24 } />
		</div>
	);
};
