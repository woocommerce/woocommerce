/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */

interface GatewaySettingsFormProps {
	children: React.ReactNode;
}

export const GatewaySettingsForm: React.FC< GatewaySettingsFormProps > = ( {
	children,
} ) => {
	return <>{ children }</>;
};
