/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Button } from '@wordpress/components';
import React from 'react';

/**
 * Internal dependencies
 */
import { SplitDropdown } from '..';

export const Primary: React.FC = () => {
	return (
		<SplitDropdown>
			<Button onClick={ () => clickHandler( 'Default' ) }>
				Default Action
			</Button>
			<Button onClick={ () => clickHandler( 'Secondary' ) }>
				Secondary Action
			</Button>
			<Button onClick={ () => clickHandler( 'Tertiary' ) }>
				Tertiary Action
			</Button>
		</SplitDropdown>
	);
};

export const PrimaryDisabled: React.FC = () => {
	return (
		<SplitDropdown disabled>
			<Button onClick={ () => clickHandler( 'Default' ) }>
				Default Action
			</Button>
			<Button onClick={ () => clickHandler( 'Secondary' ) }>
				Secondary Action
			</Button>
			<Button onClick={ () => clickHandler( 'Tertiary' ) }>
				Tertiary Action
			</Button>
		</SplitDropdown>
	);
};

export const Secondary: React.FC = () => {
	return (
		<SplitDropdown variant="secondary">
			<Button onClick={ () => clickHandler( 'Default' ) }>
				Default Action
			</Button>
			<Button onClick={ () => clickHandler( 'Secondary' ) }>
				Secondary Action
			</Button>
			<Button onClick={ () => clickHandler( 'Tertiary' ) }>
				Tertiary Action
			</Button>
		</SplitDropdown>
	);
};

export const SomeDisabled: React.FC = () => {
	return (
		<SplitDropdown>
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
		</SplitDropdown>
	);
};

export default {
	title: 'WooCommerce Admin/components/SplitDropdown',
	component: SplitDropdown,
};
