/**
 * External dependencies
 */
import { ValidatedTextInput } from '@woocommerce/blocks-components';
import { type AddressFormType } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { ValidatedTextInputProps } from '../../../../../../packages/components/text-input/types';
import './style.scss';
import { useUpdatePreferredAutocompleteProvider } from '../../../hooks/use-update-preferred-autocomplete-provider';

/**
 * Address Autocomplete component.
 *
 * @param props             - Props for the component.
 * @param props.addressType - Type of address ('billing' or 'shipping').
 * @param props.id          - ID for the input field.
 * @return Address Autocomplete component.
 */
export const AddressAutocomplete = ( {
	addressType,
	id,
	...props
}: { addressType: AddressFormType; id: string } & ValidatedTextInputProps ) => {
	// This hook will monitor for changes in country and update the provider accordingly.
	useUpdatePreferredAutocompleteProvider( addressType );

	return (
		<div className="wc-block-components-address-autocomplete-container">
			<ValidatedTextInput { ...props } id={ id } />
		</div>
	);
};
