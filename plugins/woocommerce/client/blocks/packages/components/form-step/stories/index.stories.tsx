/**
 * External dependencies
 */
import type { StoryFn, Meta } from '@storybook/react';
import { ValidatedTextInput } from '@woocommerce/blocks-checkout';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import FormStep, { type FormStepProps } from '..';
import '../style.scss';

export default {
	title: 'External Components/FormStep',
	component: FormStep,
	argTypes: {
		className: {
			control: 'text',
			table: {
				type: {
					summary: 'string',
				},
			},
		},
		id: {
			control: 'text',
			table: {
				type: {
					summary: 'string',
				},
			},
		},
		title: {
			control: 'text',
			table: {
				type: {
					summary: 'string',
				},
			},
			description: 'The title of the form step.',
		},
		legend: {
			control: 'text',
			table: {
				type: {
					summary: 'string',
				},
			},
			description:
				'The legend is hidden but is made available to screen readers.',
		},
		description: {
			control: 'text',
			table: {
				type: {
					summary: 'string',
				},
			},
			description:
				'The description of the form step. Appears under the title.',
		},
		children: {
			table: {
				type: {
					summary: 'ReactNode',
				},
			},
			control: 'disabled',
			description: 'The content of the form step.',
		},
		disabled: {
			control: 'boolean',
			table: {
				type: {
					summary: 'boolean',
				},
			},
			description: 'Is the component and all of its children disabled?',
		},
		showStepNumber: {
			table: {
				type: {
					summary: 'boolean',
				},
			},
			control: 'boolean',
			description: 'Should the step number be shown?',
		},
		stepHeadingContent: {
			table: {
				type: {
					summary: '() => JSX.Element | undefined',
				},
			},
			description: 'Content to render in the step heading.',
		},
	},
} as Meta< FormStepProps >;

const InputWithState = () => {
	const [ value, setValue ] = useState( 'John Doe' );
	return (
		<ValidatedTextInput
			label={ 'Name' }
			instanceId={ '1' }
			value={ value }
			onChange={ setValue }
		/>
	);
};
const Template: StoryFn< FormStepProps > = ( args ) => (
	<div className="wc-block-components-form">
		<FormStep { ...args }>
			<InputWithState />
		</FormStep>
	</div>
);

export const Default = Template.bind( {} );

Default.args = {
	stepHeadingContent: () => <span>Step heading content</span>,
	title: 'Personal information',
	description: 'Please enter your personal information.',
};
