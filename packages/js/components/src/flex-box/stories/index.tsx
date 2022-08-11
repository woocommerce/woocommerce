/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Icon, check, grid } from '@wordpress/icons';
import React from 'react';

/**
 * Internal dependencies
 */
import { FlexBox } from '..';
import './style.scss';

export const Column: React.FC = () => {
	return (
		<FlexBox
			flexDirection="column"
			height={ 500 }
			className="storybook__flex-box"
		>
			<span className="red">
				<strong>(flex-grow: 1 [default])</strong>
			</span>
			<span className="yellow">
				<strong>(flex-grow: 1 [default])</strong>
			</span>
			<span className="green grow-big">
				<strong>(flex-grow: 5)</strong>
			</span>
		</FlexBox>
	);
};

export const Row: React.FC = () => {
	return (
		<FlexBox
			flexDirection="row"
			width="100%"
			className="storybook__flex-box"
		>
			<span className="red">
				<strong>(flex-grow: 1 [default])</strong>
			</span>
			<span className="yellow">
				<strong>(flex-grow: 1 [default])</strong>
			</span>
			<span className="green grow-big">
				<strong>(flex-grow: 5)</strong>
			</span>
		</FlexBox>
	);
};

export const WithIcons: React.FC = () => {
	return (
		<FlexBox
			flexDirection="row"
			alignItems="center"
			columnGap={ 5 }
			className="storybook__flex-box"
		>
			<span className="icon">
				<Icon icon={ grid } />
			</span>
			<span className="red">
				<strong>(flex-grow: 1 [default])</strong>
			</span>
			<span className="yellow">
				<strong>(flex-grow: 1 [default])</strong>
			</span>
			<span className="green grow-big">
				<strong>(flex-grow: 5)</strong>
			</span>
			<span className="icon">
				<Icon icon={ check } />
			</span>
		</FlexBox>
	);
};

export default {
	title: 'WooCommerce Admin/components/FlexBox',
	component: FlexBox,
};
