/**
 * External dependencies
 */
import React, { createElement } from 'react';

/**
 * Internal dependencies
 */
import { ButtonWithDropdownMenu } from '../';
import type { ButtonWithDropdownMenuProps } from '../';

export default {
	title: 'Product Editor/components/ButtonWithDropdownMenu',
	component: ButtonWithDropdownMenu,
	args: {
		label: 'Woo',
	},
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

	return <ButtonWithDropdownMenu { ...args } controls={ controls } />;
};

Default.args = {
	onMainButtonClick: console.log, // eslint-disable-line no-console
};
