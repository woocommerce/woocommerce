/**
 * External dependencies
 */
import { withA11y } from '@storybook/addon-a11y';
import { withKnobs } from '@storybook/addon-knobs';
import { addDecorator } from '@storybook/react';

/**
 * Internal dependencies
 */
import './style.scss';

addDecorator( withA11y );
addDecorator( withKnobs );

export const parameters = {
	actions: { argTypesRegex: '^on[A-Z].*' },
	controls: {
		matchers: {
			color: /(background|color)$/i,
			date: /Date$/,
		},
	},
};
