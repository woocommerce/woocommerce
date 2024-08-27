/* eslint-disable @typescript-eslint/ban-types */
/**
 * External dependencies
 */
import type { StoryFn } from '@storybook/react';
import { createElement } from 'react';
import { DataView } from '../../utilites/storybook';

/**
 * Internal dependencies
 */

export default {
	title: 'Data Field Controls/Slug',
	component: DataView,
};

const Template: StoryFn< {} > = () => <DataView />;

export const Default: StoryFn< {} > = Template.bind( {} );
Default.args = {};
