/**
 * Internal dependencies
 */
import PhoneInput from './phone-input';
import { PhoneInputProps } from './props';

export const ShippingPhoneInput = ( props: PhoneInputProps ): JSX.Element => {
	return <PhoneInput { ...props } />;
};

export const BillingPhoneInput = ( props: PhoneInputProps ): JSX.Element => {
	return <PhoneInput { ...props } />;
};

export type { PhoneInputProps } from './props';
export { default as PhoneInput } from './phone-input';
