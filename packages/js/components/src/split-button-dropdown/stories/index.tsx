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

export default {
	title: 'WooCommerce Admin/components/SplitButton',
	component: SplitButtonDropdown,
};
