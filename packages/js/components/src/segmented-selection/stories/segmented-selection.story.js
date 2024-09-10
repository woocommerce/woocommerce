/**
 * External dependencies
 */
import { SegmentedSelection } from '@woocommerce/components';
import { useState } from '@wordpress/element';

const name = 'number';

const SegmentedSelectionExample = () => {
	const [ selected, setSelected ] = useState( 'two' );

	return (
		<SegmentedSelection
			options={ [
				{ value: 'one', label: 'One' },
				{ value: 'two', label: 'Two' },
				{ value: 'three', label: 'Three' },
				{ value: 'four', label: 'Four' },
			] }
			selected={ selected }
			legend="Select a number"
			onSelect={ ( data ) => setSelected( data[ name ] ) }
			name={ name }
		/>
	);
};

export const Basic = () => <SegmentedSelectionExample />;

export default {
	title: 'WooCommerce Admin/components/SegmentedSelection',
	component: SegmentedSelection,
};
