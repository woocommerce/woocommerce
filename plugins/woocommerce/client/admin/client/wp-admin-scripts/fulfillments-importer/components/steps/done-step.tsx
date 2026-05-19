/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { StepComponentProps } from './types';

const DoneStep: React.FC< StepComponentProps > = () => (
	<div className="woocommerce-fulfillment-importer-step woocommerce-fulfillment-importer-step--done">
		<p>{ __( 'Done step placeholder.', 'woocommerce' ) }</p>
	</div>
);

export default DoneStep;
