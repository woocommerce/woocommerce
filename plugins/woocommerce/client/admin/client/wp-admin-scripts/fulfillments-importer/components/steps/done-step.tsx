/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';
import { Button, Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ImporterSummaryPanel from '../importer-summary';
import type { StepComponentProps } from './types';

const DoneStep: React.FC< StepComponentProps > = ( {
	state,
	dispatch,
	onClose,
} ) => (
	<div className="woocommerce-fulfillment-importer-step woocommerce-fulfillment-importer-step--done">
		<h2>{ __( 'Import complete', 'woocommerce' ) }</h2>
		{ state.summary && <ImporterSummaryPanel summary={ state.summary } /> }
		<footer className="woocommerce-fulfillment-importer-step__footer">
			<Flex justify="flex-end">
				<Button
					variant="tertiary"
					onClick={ () => dispatch( { type: 'RESET' } ) }
				>
					{ __( 'Import another file', 'woocommerce' ) }
				</Button>
				<Button variant="primary" onClick={ onClose }>
					{ __( 'Done', 'woocommerce' ) }
				</Button>
			</Flex>
		</footer>
	</div>
);

export default DoneStep;
