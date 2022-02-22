/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { defaultBillingData, defaultShippingAddress } from './constants';
import {
	useCustomerData,
	CustomerDataType,
} from '../../../hooks/use-customer-data';

const CustomerDataContext = createContext< CustomerDataType >( {
	isInitialized: false,
	billingData: defaultBillingData,
	shippingAddress: defaultShippingAddress,
	setBillingData: () => void 0,
	setShippingAddress: () => void 0,
} );

export const useCustomerDataContext = (): CustomerDataType => {
	return useContext( CustomerDataContext );
};

/**
 * Customer Data context provider.
 */
export const CustomerDataProvider = ( {
	children,
}: {
	children: JSX.Element | JSX.Element[];
} ): JSX.Element => {
	const contextValue = useCustomerData();

	return (
		<CustomerDataContext.Provider value={ contextValue }>
			{ children }
		</CustomerDataContext.Provider>
	);
};
