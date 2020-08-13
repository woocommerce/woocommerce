/**
 * External dependencies
 */
import { withState } from '@wordpress/compose';
import { SegmentedSelection } from '@woocommerce/components';

const name = 'number';

export default withState( {
	selected: 'two',
} )( ( { selected, setState } ) => (
	<SegmentedSelection
		options={ [
			{ value: 'one', label: 'One' },
			{ value: 'two', label: 'Two' },
			{ value: 'three', label: 'Three' },
			{ value: 'four', label: 'Four' },
		] }
		selected={ selected }
		legend="Select a number"
		onSelect={ ( data ) => setState( { selected: data[ name ] } ) }
		name={ name }
	/>
) );
