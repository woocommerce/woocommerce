/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SplitButtonDropdown } from '..';

export const Primary: React.FC = () => {
	return (
		<SplitButtonDropdown>
			<Button text="Default Action"></Button>
			<Button text="Secondary Action"></Button>
			<Button text="Tertiary Action"></Button>
		</SplitButtonDropdown>
	);
};

export const Default: React.FC = () => {
	return (
		<SplitButtonDropdown variant="secondary">
			<Button text="Default Action"></Button>
			<Button text="Secondary Action"></Button>
			<Button text="Tertiary Action"></Button>
		</SplitButtonDropdown>
	);
};

export default {
	title: 'WooCommerce Admin/components/SplitButton',
	component: Primary,
};
