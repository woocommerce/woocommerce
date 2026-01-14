/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { TreeSelectControl } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

const everywhere = '__WC_TREE_SELECT_COMPONENT_ROOT__';

export const RegionPicker = ( { options, initialValues } ) => {
	const [ selected, setSelected ] = useState(
		initialValues.length ? initialValues : [ everywhere ]
	);
	const onChange = ( value ) => {
		// Set selection to 'everywhere' when empty or when 'everywhere' is the last selected.
		if ( value.length === 0 || value[ value.length - 1 ] === everywhere ) {
			value = [ everywhere ];
		} else {
			// Remove 'everywhere' from selection when other regions are chosen.
			value = value.filter( ( item ) => item !== everywhere );
		}
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
			alwaysShowPlaceholder
			selectAllLabel={ __( 'Everywhere', 'woocommerce' ) }
			individuallySelectParent
			maxVisibleTags={ 5 }
		/>
	);
};
