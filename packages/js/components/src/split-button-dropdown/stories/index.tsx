/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Button } from '@wordpress/components';
import React from 'react';

/**
 * Internal dependencies
 */
import { SplitButtonDropdown } from '..';

export const Primary: React.FC = () => {
	return (
		<SplitButtonDropdown>
			<Button>Default Action</Button>
			<Button>Secondary Action</Button>
			<Button>Tertiary Action</Button>
		</SplitButtonDropdown>
	);
};

export const Secondary: React.FC = () => {
	return (
		<SplitButtonDropdown variant="secondary">
			<Button>Default Action</Button>
			<Button>Secondary Action</Button>
			<Button>Tertiary Action</Button>
		</SplitButtonDropdown>
	);
};

export const PrimaryDisabled: React.FC = () => {
	return (
		<SplitButtonDropdown disabled>
			<Button text="Default Action"></Button>
			<Button text="Secondary Action"></Button>
			<Button text="Tertiary Action"></Button>
		</SplitButtonDropdown>
	);
};

export const SomeDisabled: React.FC = () => {
	return (
		<SplitButtonDropdown>
			<Button
				text="Default Action"
				onClick={ () => clickHandler( 'Default' ) }
				disabled
			></Button>
			<Button
				text="Secondary Action"
				onClick={ () => clickHandler( 'Secondary' ) }
			></Button>
			<Button
				text="Tertiary Action"
				onClick={ () => clickHandler( 'Tertiary' ) }
				disabled
			></Button>
		</SplitButtonDropdown>
	);
};

export default {
	title: 'WooCommerce Admin/components/SplitButton',
	component: SplitButtonDropdown,
};
