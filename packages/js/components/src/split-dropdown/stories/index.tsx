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
			<Button onClick={ () => clickHandler( 'Default' ) } disabled>
				Default Action
			</Button>
			<Button onClick={ () => clickHandler( 'Secondary' ) }>
				Secondary Action
			</Button>
			<Button onClick={ () => clickHandler( 'Tertiary' ) } disabled>
				Tertiary Action
			</Button>
		</SplitDropdown>
	);
};

export const SingleAction: React.FC = () => {
	return (
		<SplitDropdown>
			<Button onClick={ () => clickHandler( 'Only' ) }>
				Only Action
			</Button>
		</SplitDropdown>
	);
};

export default {
	title: 'WooCommerce Admin/components/SplitDropdown',
	component: SplitDropdown,
};
