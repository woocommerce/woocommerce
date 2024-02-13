/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { CheckboxControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../../constants';

export function ShowPrepublishChecksSection() {
	const { disablePublishSidebar, enablePublishSidebar } =
		useDispatch( 'core/editor' );

	const { isPrepublishSidebarEnabled } = useSelect( ( select ) => {
		const { isPublishSidebarEnabled } = select( 'core/editor' );
		return {
			isPrepublishSidebarEnabled: isPublishSidebarEnabled() ?? true,
		};
	}, [] );

	return (
		<div className="woocommerce-publish-panel-show-checks">
			<CheckboxControl
				label={ __( 'Always show pre-publish checks.', 'woocommerce' ) }
				checked={ isPrepublishSidebarEnabled }
				onChange={ ( selected: boolean ) => {
					if ( selected ) {
						enablePublishSidebar();
					} else {
						disablePublishSidebar();
					}
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
