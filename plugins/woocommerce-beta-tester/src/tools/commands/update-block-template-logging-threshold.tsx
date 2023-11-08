/**
 * External dependencies
 */

import { SelectControl } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore no types
// eslint-disable-next-line @woocommerce/dependency-group
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore no types
// eslint-disable-next-line @woocommerce/dependency-group
import { STORE_KEY } from '../data/constants';

export const UPDATE_BLOCK_TEMPLATE_LOGGING_THRESHOLD_ACTION_NAME =
	'updateBlockTemplateLoggingThreshold';

interface LoggingLevel {
	label: string;
	value: string;
}

export const UpdateBlockTemplateLoggingThreshold = () => {
	const { loggingLevels, threshold, isLoading } = useSelect(
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore no types
		( select ) => {
			const { getLoggingLevels, getBlockTemplateLoggingThreshold } =
				select( STORE_KEY );

			const retrievedLoggingLevels = getLoggingLevels();
			const retrievedThreshold = getBlockTemplateLoggingThreshold();

			return {
				loggingLevels: retrievedLoggingLevels,
				threshold: retrievedThreshold,
				isLoading: ! retrievedLoggingLevels || ! retrievedThreshold,
			};
		}
	);

	const [ newThreshold, setNewThreshold ] = useState( threshold );

	const { updateCommandParams } = useDispatch( STORE_KEY );

	function onThresholdChange( selectedThreshold: string ) {
		setNewThreshold( selectedThreshold );
		updateCommandParams(
			UPDATE_BLOCK_TEMPLATE_LOGGING_THRESHOLD_ACTION_NAME,
			{
				threshold: selectedThreshold,
			}
		);
	}

	function getOptions() {
		return loggingLevels.map( ( loggingLevel: LoggingLevel ) => {
			return {
				label: loggingLevel.label,
				value: loggingLevel.value,
			};
		} );
	}

	useEffect( () => {
		setNewThreshold( threshold );
	}, [ threshold ] );

	return (
		<div className="select-description">
			{ isLoading ? (
				<p>Loading...</p>
			) : (
				<SelectControl
					label="Threshold"
					onChange={ onThresholdChange }
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-ignore labelPosition prop exists
					labelPosition="side"
					options={ getOptions() }
					value={ newThreshold }
				/>
			) }
		</div>
	);
};
