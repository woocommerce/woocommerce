/**
 * External dependencies
 */
import { withA11y } from '@storybook/addon-a11y';
import { addDecorator } from '@storybook/react';

/**
 * Internal dependencies
 */
import './style.scss';

addDecorator( withA11y );

export const parameters = {
	actions: { argTypesRegex: '^on[A-Z].*' },
	controls: {
		expanded: true,
		matchers: {
			color: /(background|color)$/i,
			date: /Date$/,
		},
	},
};
