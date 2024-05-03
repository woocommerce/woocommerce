/**
 * External dependencies
 */
import type { StoryFn, Meta } from '@storybook/react';
import { useArgs } from '@storybook/preview-api';

/**
 * Internal dependencies
 */
import Panel, { type PanelProps } from '..';

export default {
	title: 'External Components/Panel',
	component: Panel,
	argTypes: {
		children: {
			control: 'null',
			description: "The content element to display in the panel's body",
			table: {
				type: {
					summary: 'ReactNode',
				},
			},
		},
		className: {
			description: 'Additional CSS classes to be added to the panel.',
			control: 'text',
			table: {
				type: {
					summary: 'string',
				},
			},
		},
		initialOpen: {
			description: 'Whether the panel body should be visible by default.',
			table: {
				type: {
					summary: 'boolean',
				},
			},
		},
		title: {
			control: 'null',
			description: "The React element to display as the panel's title",
			table: {
				type: {
					summary: 'ReactNode',
				},
			},
		},
		titleTag: {
			control: 'text',
			description:
				'The HTML tag used to display the title element. Defaults to `div`.',
			table: {
				type: {
					summary: 'string',
				},
			},
		},
	},
} as Meta< PanelProps >;

const Template: StoryFn< PanelProps > = ( args ) => {
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	let [ { titleTag }, _ ] = useArgs();

	titleTag = ( titleTag || 'div' ).replace( /\n/g, '' );
	if (
		document.createElement( titleTag.toUpperCase() ).toString() ===
		'[object HTMLUnknownElement]'
	) {
		titleTag = 'div';
	}
	return (
		<Panel { ...args } titleTag={ titleTag }>
			<div style={ { paddingBottom: '.750em' } }>
				This is the content rendered inside the panel.
			</div>
		</Panel>
	);
};

export const Default = Template.bind( {} );
Default.args = {
	title: (
		<div style={ { paddingBottom: '.375em', marginBottom: '.375em' } }>
			Title
		</div>
	),
	hasBorder: true,
	initialOpen: false,
};

export const InitialOpen = Template.bind( {} );
InitialOpen.args = {
	title: (
		<div style={ { paddingBottom: '.375em', marginBottom: '.375em' } }>
			Title
		</div>
	),
	hasBorder: true,
	initialOpen: true,
};
