/**
 * External dependencies
 */
import { text, select, boolean } from '@storybook/addon-knobs';

/**
 * Internal dependencies
 */
import * as components from '../';

export default {
	title: 'WooCommerce Blocks/@base-components/Chip',
	component: Chip,
};

const radii = [ 'none', 'small', 'medium', 'large' ];

export const Chip = () => (
	<components.Chip
		text={ text( 'Text', 'example' ) }
		radius={ select( 'Radius', radii ) }
		screenReaderText={ text(
			'Screen reader text',
			'Example screen reader text'
		) }
		element={ select( 'Element', [ 'li', 'div', 'span' ], 'li' ) }
	/>
);

export const RemovableChip = () => (
	<components.RemovableChip
		text={ text( 'Text', 'example' ) }
		radius={ select( 'Radius', radii ) }
		screenReaderText={ text(
			'Screen reader text',
			'Example screen reader text'
		) }
		disabled={ boolean( 'Disabled', false ) }
		removeOnAnyClick={ boolean( 'Remove on any click', false ) }
		element={ select( 'Element', [ 'li', 'div', 'span' ], 'li' ) }
	/>
);
