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

	return (
		<div
			style={ {
				display: 'flex',
				justifyContent: 'center',
				minHeight: '300px',
			} }
		>
			<ButtonWithDropdownMenu controls={ controls } { ...args } />
		</div>
	);
};

Default.args = {
	label: 'Add to store',
	onButtonClick: console.log, // eslint-disable-line no-console
};
