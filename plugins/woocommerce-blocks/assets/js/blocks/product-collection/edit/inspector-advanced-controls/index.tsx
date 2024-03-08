/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { InspectorAdvancedControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import ForcePageReloadControl from './force-page-reload-control';
import { setQueryAttribute } from '../../utils';
import type { ProductCollectionEditComponentProps } from '../../types';

export default function ToolbarControls(
	props: ProductCollectionEditComponentProps
) {
	const { attributes, openCollectionSelectionModal, setAttributes } = props;
	const { query, displayLayout } = attributes;

	const setQueryAttributeBind = useMemo(
		() => setQueryAttribute.bind( null, props ),
		[ props ]
	);

	console.log( 'it goes', InspectorAdvancedControls );

	return (
		<InspectorAdvancedControls>
			<ForcePageReloadControl { ...props } />
		</InspectorAdvancedControls>
	);
}
