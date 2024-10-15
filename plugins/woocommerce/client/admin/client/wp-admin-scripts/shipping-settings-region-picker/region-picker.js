/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { TreeSelectControl } from '@woocommerce/components';

export const RegionPicker = ( { options, initialValues } ) => {
	const [ selected, setSelected ] = useState( initialValues );
	const onChange = ( value ) => {
		document.body.dispatchEvent(
			new CustomEvent( 'wc_region_picker_update', { detail: value } )
		);
		setSelected( value );
	};

	return (
		<TreeSelectControl
			value={ selected }
			onChange={ onChange }
			options={ options }
			placeholder="Start typing to filter zones"
			selectAllLabel="Select all countries"
			individuallySelectParent
			maxVisibleTags={ 5 }
		/>
	);
};
