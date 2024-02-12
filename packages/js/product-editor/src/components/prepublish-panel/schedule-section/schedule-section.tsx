/**
 * External dependencies
 */
import { PanelBody } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ScheduleSectionProps } from './types';

export function ScheduleSection( {}: ScheduleSectionProps ) {
	return (
		<PanelBody initialOpen={ false } title={ __( 'Add', 'woocommerce' ) }>
			<></>
		</PanelBody>
	);
}
