/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import { ButtonWithDropdownMenu } from '../';
import type { ButtonWithDropdownMenuProps } from '../';

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
	label: 'Add to store',
	onButtonClick: console.log, // eslint-disable-line no-console
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
};
