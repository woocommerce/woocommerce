/**
 * External dependencies
 */
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import React, { createContext, useState } from '@wordpress/element';
import type { ReactNode } from 'react';

export const enum OPTIN_FLOW_STATUS {
	'IDLE' = 'IDLE',
	'LOADING' = 'LOADING',
	'DONE' = 'DONE',
}

export const OptInContext = createContext< {
	optInFlowStatus: OPTIN_FLOW_STATUS;
	setOptInFlowStatus: ( status: OPTIN_FLOW_STATUS ) => void;
} >( {
	optInFlowStatus: OPTIN_FLOW_STATUS.IDLE,
	setOptInFlowStatus: () => {},
} );

export const OptInContextProvider = ( {
	children,
}: {
	children: ReactNode;
} ) => {
	const isAllowTrackingEnabled = useSelect(
		( select ) =>
			select( OPTIONS_STORE_NAME ).getOption(
				'woocommerce_allow_tracking'
			) === 'yes',
		[]
	);

	const [ optInFlowStatus, setOptInFlowStatus ] =
		useState< OPTIN_FLOW_STATUS >(
			isAllowTrackingEnabled
				? OPTIN_FLOW_STATUS.DONE
				: OPTIN_FLOW_STATUS.IDLE
		);

	return (
		<OptInContext.Provider
			value={ {
				optInFlowStatus,
				setOptInFlowStatus,
			} }
		>
			{ children }
		</OptInContext.Provider>
	);
};
