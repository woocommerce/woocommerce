/**
 * External dependencies
 */
import { createElement, Fragment, useState } from '@wordpress/element';
import { Meta, StoryFn } from '@storybook/react-webpack5';

/**
 * Internal dependencies
 */
import {
	VerticalCSSTransition,
	VerticalCSSTransitionProps,
} from '../vertical-css-transition';
import './style.scss';

export default {
	title: 'Experimental/VerticalCSSTransition',
	component: VerticalCSSTransition,
} as Meta;

const Parent = ( args: VerticalCSSTransitionProps ) => {
	const [ expanded, setExpanded ] = useState( true );
	return (
		<>
			<button onClick={ () => setExpanded( ! expanded ) }>
				{ expanded ? 'collapse' : 'expand' }
			</button>
			<VerticalCSSTransition { ...args } in={ expanded }>
				<>
					<div>some content</div>
					<div>
						some more content <br /> line 2 <br /> line 3
					</div>
				</>
			</VerticalCSSTransition>
		</>
	);
};

const Template: StoryFn< VerticalCSSTransitionProps > = ( args ) => (
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
