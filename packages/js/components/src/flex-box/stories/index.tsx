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

export const Column: React.FC = () => {
	return (
		<FlexBox flexDirection="column" height={ 500 }>
			<span style={ { padding: '1em', backgroundColor: 'red' } }>
				<strong style={ { color: 'white' } }>
					(flex-grow: 1 [default])
				</strong>
			</span>
			<span style={ { padding: '1em', backgroundColor: 'yellow' } }>
				<strong style={ { color: 'black' } }>
					(flex-grow: 1 [default])
				</strong>
			</span>
			<span
				style={ {
					padding: '1em',
					backgroundColor: 'green',
					flexGrow: 5,
				} }
			>
				<strong style={ { color: 'white' } }>(flex-grow: 5)</strong>
			</span>
		</FlexBox>
	);
};

export const Row: React.FC = () => {
	return (
		<FlexBox flexDirection="row" width="100%">
			<span style={ { padding: '1em', backgroundColor: 'red' } }>
				<strong style={ { color: 'white' } }>
					(flex-grow: 1 [default])
				</strong>
			</span>
			<span style={ { padding: '1em', backgroundColor: 'yellow' } }>
				<strong style={ { color: 'black' } }>
					(flex-grow: 1 [default])
				</strong>
			</span>
			<span
				style={ {
					padding: '1em',
					backgroundColor: 'green',
					flexGrow: 5,
				} }
			>
				<strong style={ { color: 'white' } }>(flex-grow: 5)</strong>
			</span>
		</FlexBox>
	);
};

export const WithIcons: React.FC = () => {
	return (
		<FlexBox flexDirection="row" alignItems="center" columnGap={ 5 }>
			<span
				style={ {
					flexGrow: 0,
					flexShrink: 0,
					cursor: 'pointer',
					backgroundColor: '#9999FF',
					padding: '0 1em',
				} }
			>
				<Icon icon={ grid } />
			</span>
			<span style={ { padding: '1em', backgroundColor: 'red' } }>
				<strong style={ { color: 'white' } }>
					(flex-grow: 1 [default])
				</strong>
			</span>
			<span style={ { padding: '1em', backgroundColor: 'yellow' } }>
				<strong style={ { color: 'black' } }>
					(flex-grow: 1 [default])
				</strong>
			</span>
			<span
				style={ {
					padding: '1em',
					backgroundColor: 'green',
					flexGrow: 5,
				} }
			>
				<strong style={ { color: 'white' } }>(flex-grow: 5)</strong>
			</span>
			<span
				style={ {
					flexGrow: 0,
					flexShrink: 0,
					cursor: 'pointer',
					backgroundColor: '#9999FF',
					padding: '0 1em',
				} }
			>
				<Icon icon={ check } />
			</span>
		</FlexBox>
	);
};

export default {
	title: 'WooCommerce Admin/components/FlexBox',
	component: FlexBox,
};
