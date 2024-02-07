/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import { ButtonWithDropdownMenu } from '../';
import type { ButtonWithDropdownMenuProps } from '../types';

export default {
	title: 'Product Editor/components/ButtonWithDropdownMenu',
	component: ButtonWithDropdownMenu,
};

export const Default = ( args: ButtonWithDropdownMenuProps ) => {
	return (
		<div
			style={ {
				display: 'flex',
				justifyContent: 'center',
				minHeight: '300px',
			} }
		>
			<ButtonWithDropdownMenu { ...args } />
		</div>
	);
};

Default.args = {
	text: 'Add to store',
	dropdownButtonLabel: 'More options',
	variant: 'secondary',
	defaultOpen: false,
	popoverProps: {
		placement: 'bottom-end',
		position: 'bottom left left',
		offset: 0,
	},
	controls: [
		{
			title: 'First Menu Item Label',
			onClick: function noRefCheck() {},
		},
		{
			onClick: function noRefCheck() {},
			title: 'Second Menu Item Label',
		},
	],
	className: 'my-custom-classname',
	onButtonClick: console.log, // eslint-disable-line no-console
};
