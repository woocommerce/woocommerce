/**
 * Internal dependencies
 */
import './style.scss';

export const parameters = {
	actions: { argTypesRegex: '^on[A-Z].*' },
	controls: {
		expanded: true,
		matchers: {
			color: /(background|color)$/i,
			date: /Date$/,
		},
	},
	layout: 'padded',
	a11y: {
		element: '#storybook-root',
		config: {},
		options: {},
		manual: true,
	},
	docs: {
		toc: {
			headingSelector: 'h2, h3',
			title: 'Table of Contents',
			disable: false,
		},
	},
};
