/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { InspectorAdvancedControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import ForcePageReloadControl from './force-page-reload-control';
import type { ProductCollectionEditComponentProps } from '../../types';

export default function ToolbarControls(
	props: ProductCollectionEditComponentProps
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
