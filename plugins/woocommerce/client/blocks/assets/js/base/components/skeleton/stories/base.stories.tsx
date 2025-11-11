/**
 * External dependencies
 */
import { Meta, StoryObj } from '@storybook/react';

/**
 * Internal dependencies
 */
import { Skeleton, SkeletonProps } from '../';

export default {
	title: 'Base Components/Skeleton/Base',
	component: Skeleton,
	argTypes: {
		width: { control: 'text' },
		height: { control: 'text' },
		borderRadius: { control: 'text' },
		className: { control: 'text' },
		tag: {
			control: { type: 'select' },
			options: [ 'div' ],
		},
	},
	parameters: {
		docs: {
			description: {
				component:
					'Base skeletons provide foundational building blocks for all skeleton components.',
			},
		},
	},
} as Meta< SkeletonProps >;

const Template = ( args: SkeletonProps ) => <Skeleton { ...args } />;

export const Default: StoryObj< SkeletonProps > = {
	render: Template,
	args: {},
	parameters: {
		docs: {
			description: {
				story: 'The base skeleton component with the default args.',
			},
		},
	},
};

export const TallerSkeleton: StoryObj< SkeletonProps > = {
	render: Template,
	args: {
		height: '48px',
	},
	name: 'Taller',
	parameters: {
		docs: {
			description: {
				story: 'The base skeleton component with a custom height.',
			},
		},
	},
};

export const NarrowerSkeleton: StoryObj< SkeletonProps > = {
	render: Template,
	args: {
		width: '177px',
	},
	name: 'Narrower',
	parameters: {
		docs: {
			description: {
				story: 'The base skeleton component with a custom width.',
			},
		},
	},
};

export const Circular: StoryObj< SkeletonProps > = {
	render: Template,
	args: {
		borderRadius: '100%',
		height: '48px',
		width: '48px',
	},
	parameters: {
		docs: {
			description: {
				story: 'The base skeleton component with a circular shape.',
			},
		},
	},
};
