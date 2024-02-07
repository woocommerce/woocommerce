/**
 * Internal dependencies
 */
import registerHideInventoryAdvancedCollapsible from './hide-inventory-advanced-collapsible';
import registerAddMetadataToAttributes from './add-metadata-to-attributes';
import registerAddBindingToSelectOptions from './add-binding-to-select-options';

export default function registerProductEditorHooks() {
	registerHideInventoryAdvancedCollapsible();
	registerAddMetadataToAttributes();
	registerAddBindingToSelectOptions();
}
