/**
 * External dependencies
 */
import React from 'react';
import { Gridicon } from '@automattic/components';
import { Card, CardHeader, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

export const MainPaymentMethods = () => {
	return (
		<Card size="medium" className="main-payment-providers">
			<CardHeader>
				<div className="settings-payment-providers__header-title">
					{ __( 'Payment providers', 'woocommerce' ) }
				</div>
				<div className="settings-payment-providers__header-select-container">
					<SelectControl
						className="woocommerce-profiler-select-control__country"
						prefix={ 'Business location :' }
						placeholder={ '' }
						label={ '' }
						options={ [
							{ label: 'United States', value: 'US' },
							{ label: 'Canada', value: 'Canada' },
						] }
						onChange={ () => {} }
					/>
				</div>
			</CardHeader>
			<List items={ pluginsList } />
		</Card>
	);
};
