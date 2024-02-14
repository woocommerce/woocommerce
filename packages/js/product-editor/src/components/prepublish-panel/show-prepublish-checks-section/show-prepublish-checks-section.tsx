/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../../constants';
import { useShowPrepublishChecks } from '../../../hooks/use-show-prepublish-checks';

export function ShowPrepublishChecksSection() {
	const { isResolving, showPrepublishChecks, togglePrepublishChecks } =
		useShowPrepublishChecks();

	if ( isResolving ) {
		return null;
	}

	return (
		<div className="woocommerce-publish-panel-show-checks">
			<CheckboxControl
				label={ __( 'Always show pre-publish checks.', 'woocommerce' ) }
				checked={ showPrepublishChecks }
				onChange={ ( selected: boolean ) => {
					togglePrepublishChecks();
					recordEvent( 'product_prepublish_panel', {
						source: TRACKS_SOURCE,
						action: 'enable_prepublish_checks',
						value: selected,
					} );
				} }
			/>
		</div>
	);
}
