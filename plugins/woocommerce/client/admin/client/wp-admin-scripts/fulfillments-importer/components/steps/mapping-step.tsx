/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { StepComponentProps } from './types';

const MappingStep: React.FC< StepComponentProps > = () => (
	<div className="woocommerce-fulfillment-importer-step woocommerce-fulfillment-importer-step--mapping">
		<p>{ __( 'Mapping step placeholder.', 'woocommerce' ) }</p>
	</div>
);

export default MappingStep;
