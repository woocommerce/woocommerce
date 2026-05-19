/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { StepComponentProps } from './types';

const ImportStep: React.FC< StepComponentProps > = () => (
	<div className="woocommerce-fulfillment-importer-step woocommerce-fulfillment-importer-step--import">
		<p>{ __( 'Import step placeholder.', 'woocommerce' ) }</p>
	</div>
);

export default ImportStep;
