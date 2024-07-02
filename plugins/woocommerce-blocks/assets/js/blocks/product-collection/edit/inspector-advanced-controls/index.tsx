/**
 * External dependencies
 */
import { InspectorAdvancedControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import ForcePageReloadControl from './force-page-reload-control';
import type { ProductCollectionEditComponentProps } from '../../types';

export default function ProductCollectionAdvancedInspectorControls(
	props: Omit< ProductCollectionEditComponentProps, 'preview' >
) {
	const { clientId, attributes, setAttributes } = props;
	const { forcePageReload } = attributes;

	return (
		<InspectorAdvancedControls>
			<ForcePageReloadControl
				clientId={ clientId }
				forcePageReload={ forcePageReload }
				setAttributes={ setAttributes }
			/>
		</InspectorAdvancedControls>
	);
}
