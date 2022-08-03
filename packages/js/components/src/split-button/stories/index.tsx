/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SplitButton } from '../';

export const Primary: React.FC = () => {
	return (
		<SplitButton variant="primary" text="Default Action">
			<Button text="Secondary Action"></Button>
			<Button text="Tertiary Action" isDestructive={ true }></Button>
		</SplitButton>
	);
};

export const Default: React.FC = () => {
	return (
		<SplitButton text="Default Action">
			<Button text="Secondary Action"></Button>
			<Button text="Tertiary Action" isDestructive={ true }></Button>
		</SplitButton>
	);
};

export default {
	title: 'WooCommerce Admin/components/SplitButton',
	component: Primary,
};
