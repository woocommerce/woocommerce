/**
 * External dependencies
 */
import type { Meta, StoryFn } from '@storybook/react';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { RemovableChip, RemovableChipProps } from '../removable-chip';

const availableElements = [ 'li', 'div', 'span' ];

export default {
	title: 'External Components/RemovableChip',
	component: RemovableChip,
	argTypes: {
		element: {
			control: 'radio',
			options: availableElements,
		},
	},
} as Meta< RemovableChipProps >;

const Template: StoryFn< RemovableChipProps > = ( args ) => {
	const [ isVisible, setIsVisible ] = useState( true );
	const handleRemove = () => {
		setIsVisible( false );
	};

	if ( ! isVisible ) {
		return (
			<button onClick={ () => setIsVisible( true ) }>
				<em>Chip was removed, click to reset</em>
			</button>
		);
	}

	return <RemovableChip { ...args } onRemove={ handleRemove } />;
};

export const Default: StoryFn< RemovableChipProps > = Template.bind( {} );
Default.args = {
	element: 'li',
	text: 'Take me to the casino',
	screenReaderText: "I'm a removable chip, me",
};

export const Disabled: StoryFn< RemovableChipProps > = Template.bind( {} );
Disabled.args = {
	element: 'li',
	text: 'Disabled chip',
	disabled: true,
};

export const RemoveOnAnyClick: StoryFn< RemovableChipProps > = Template.bind(
	{}
);
RemoveOnAnyClick.args = {
	element: 'li',
	text: 'Click anywhere to remove',
	removeOnAnyClick: true,
};
