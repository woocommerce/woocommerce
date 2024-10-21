/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { TreeSelectControl } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

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
			placeholder={ __( 'Start typing to filter zones', 'woocommerce' ) }
			selectAllLabel={ __( 'Select all countries', 'woocommerce' ) }
			individuallySelectParent
			maxVisibleTags={ 5 }
		/>
	);
};
