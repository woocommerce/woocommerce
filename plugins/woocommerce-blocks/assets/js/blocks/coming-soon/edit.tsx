/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';

export default function Edit() {
	const currentYear = new Date().getFullYear().toString();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'copyright-date-block' ) }>
					Testing
				</PanelBody>
			</InspectorControls>
			<p { ...useBlockProps() }>© 200000024</p>
		</>
	);
}
