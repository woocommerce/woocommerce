/**
 * External dependencies
 */
import React, { createElement } from 'react';

/**
 * Internal dependencies
 */
import { Loader } from '../';

/** Simple straightforward example of how to use the <Loader> compound component */
export const ExampleSimpleLoader = () => (
	<Loader>
		<Loader.Layout>
			<Loader.Illustration>
				<img
					src="https://placekitten.com/200/200"
					alt="a cute kitteh"
				/>
			</Loader.Illustration>
			<Loader.Title>Very Impressive Title</Loader.Title>
			<Loader.ProgressBar progress={ 30 } />
			<Loader.Sequence interval={ 1000 }>
				<Loader.Subtext>Message 1</Loader.Subtext>
				<Loader.Subtext>Message 2</Loader.Subtext>
				<Loader.Subtext>Message 3</Loader.Subtext>
			</Loader.Sequence>
		</Loader.Layout>
	</Loader>
);

export const ExampleNonLoopingLoader = () => (
	<Loader>
		<Loader.Layout>
			<Loader.Illustration>
				<img
					src="https://placekitten.com/200/200"
					alt="a cute kitteh"
				/>
			</Loader.Illustration>
			<Loader.Title>Very Impressive Title</Loader.Title>
			<Loader.ProgressBar progress={ 30 } />
			<Loader.Sequence interval={ 1000 } shouldLoop={ false }>
				<Loader.Subtext>Message 1</Loader.Subtext>
				<Loader.Subtext>Message 2</Loader.Subtext>
				<Loader.Subtext>Message 3</Loader.Subtext>
			</Loader.Sequence>
		</Loader.Layout>
	</Loader>
);

/** <Loader> component story with controls */
const Template = ( { progress, title, messages, shouldLoop } ) => (
	<Loader>
		<Loader.Layout>
			<Loader.Illustration>
				<img
					src="https://placekitten.com/200/200"
					alt="a cute kitteh"
				/>
			</Loader.Illustration>
			<Loader.Title>{ title }</Loader.Title>
			<Loader.ProgressBar progress={ progress } />
			<Loader.Sequence interval={ 1000 } shouldLoop={ shouldLoop }>
				{ messages.map( ( message, index ) => (
					<Loader.Subtext key={ index }>{ message }</Loader.Subtext>
				) ) }
			</Loader.Sequence>
		</Loader.Layout>
	</Loader>
);

export const ExampleLoaderWithControls = Template.bind( {} );
ExampleLoaderWithControls.args = {
	title: 'Very Impressive Title',
	progress: 30,
	shouldLoop: true,
	messages: [ 'Message 1', 'Message 2', 'Message 3' ],
};

export default {
	title: 'WooCommerce Admin/Onboarding/Loader',
	component: ExampleLoaderWithControls,
	argTypes: {
		title: {
			control: 'text',
		},
		progress: {
			control: {
				type: 'range',
				min: 0,
				max: 100,
			},
		},
		shouldLoop: {
			control: 'boolean',
		},
		messages: {
			control: 'object',
		},
	},
};
