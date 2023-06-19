/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';
import { useMemo } from '@wordpress/element';
import { BlockControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { setQueryAttribute } from '../utils';
import { ProductCollectionAttributes } from '../types';
import DisplaySettingsToolbar from './display-settings-toolbar';

export default function ToolbarControls(
	props: BlockEditProps< ProductCollectionAttributes >
) {
	const query = props.attributes.query;

	const setQueryAttributeBind = useMemo(
		() => setQueryAttribute.bind( null, props ),
		[ props ]
	);

	return (
		<BlockControls>
			{ ! query.inherit && (
				<DisplaySettingsToolbar
					query={ query }
					setQueryAttribute={ setQueryAttributeBind }
				/>
			) }
		</BlockControls>
	);
}
