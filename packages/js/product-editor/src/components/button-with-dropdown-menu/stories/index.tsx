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
	const controls = [
		{
			title: 'First Menu Item Label',
			onClick: function noRefCheck() {},
		},
		{
			onClick: function noRefCheck() {},
			title: 'Second Menu Item Label',
		},
	];

	return <ButtonWithDropdownMenu controls={ controls } { ...args } />;
};

Default.args = {
	label: 'Add to store',
	onButtonClick: console.log, // eslint-disable-line no-console
};
