/**
 * External dependencies
 */
import type { StoryFn, Meta } from '@storybook/react';

/**
 * Internal dependencies
 */
import Title, { type TitleProps } from '..';
import '../style.scss';

export default {
	title: 'External Components/Title',
	component: Title,
	argTypes: {
		className: {
			control: 'text',
			table: {
				type: {
					summary: 'string',
				},
			},
			description:
				'Additional CSS classes to apply to the title element.',
		},
		headingLevel: {
			control: 'select',
			options: [ '1', '2', '3', '4', '5', '6' ],
			table: {
				type: {
					summary: "'1' | '2' | '3' | '4' | '5' | '6'",
				},
			},
			description:
				'What level of heading tag should be used, e.g. h1, h2 etc.',
		},
		children: {
			control: 'text',
			table: {
				type: {
					summary: 'ReactNode',
				},
			},
			description: 'The text/children to render in the title element.',
		},
	},
} as Meta< TitleProps >;

const Template: StoryFn< TitleProps > = ( args ) => {
	const { children, ...rest } = args;
	return <Title { ...rest }>{ children }</Title>;
};

export const Default = Template.bind( {} );

Default.args = {
	headingLevel: '1',
	children: 'Title',
};
