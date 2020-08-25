/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { RemovableChip } from '@woocommerce/base-components/chip';

const DropdownSelectorSelectedChip = ( { onRemoveItem, option } ) => {
	return (
		<RemovableChip
			className="wc-block-dropdown-selector__selected-chip wc-block-components-dropdown-selector__selected-chip"
			removeOnAnyClick={ true }
			onRemove={ () => {
				onRemoveItem( option.value );
			} }
			ariaLabel={ sprintf(
				__( 'Remove %s filter', 'woocommerce' ),
				option.name
			) }
			text={ option.label }
			radius="large"
		/>
	);
};

export default DropdownSelectorSelectedChip;
