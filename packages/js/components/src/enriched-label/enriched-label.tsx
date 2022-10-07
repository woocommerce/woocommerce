/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Tooltip } from '../tooltip';

type EnrichedLabelProps = {
	helpDescription: JSX.Element | string;
	label: string;
};

export const EnrichedLabel: React.FC< EnrichedLabelProps > = ( {
	helpDescription,
	label
} ) => {
	return (
		<>
			<span className="woocommerce-enriched-label__text">{ label }</span>
			{ helpDescription && <Tooltip text={ helpDescription } /> }
		</>
	);
};
