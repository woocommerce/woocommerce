/**
 * External dependencies
 */
import { createElement, Fragment, useState } from '@wordpress/element';
import { withConsole } from '@storybook/addon-console';
import { Meta, Story } from '@storybook/react';

/**
 * Internal dependencies
 */
import {
	VerticalCSSTransition,
	VerticalCSSTransitionProps,
} from '../vertical-css-transition';
import './style.scss';

export default {
	title: 'WooCommerce Admin/experimental/VerticalCSSTransition',
	component: VerticalCSSTransition,
	decorators: [ ( storyFn, context ) => withConsole()( storyFn )( context ) ],
} as Meta;

const Parent: React.FC< VerticalCSSTransitionProps > = ( args ) => {
	const [ expanded, setExpanded ] = useState( true );
	return (
		<>
			<button onClick={ () => setExpanded( ! expanded ) }>
				{ expanded ? 'collapse' : 'expand' }
			</button>
			<VerticalCSSTransition { ...args } in={ expanded }>
				<div>some content</div>
				<div>
					some more content <br /> line 2 <br /> line 3
				</div>
			</VerticalCSSTransition>
		</>
	);
};

const Template: Story< VerticalCSSTransitionProps > = ( args ) => (
	<Parent { ...args } />
);

export const Primary = Template.bind( { onClick: () => {} } );

Primary.args = {
	appear: true,
	timeout: 500,
	classNames: 'collapsible-content',
	defaultStyle: {
		transitionProperty: 'max-height, opacity',
	},
};
