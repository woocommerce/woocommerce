/**
 * External dependencies
 */
import { Block } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { BlockIconProps } from './types';

export function BlockIcon( { clientId }: BlockIconProps ) {
	const icon = useSelect(
		( select ) => {
			const { getBlockName } = select( 'core/block-editor' );
			const { getBlockType } = select( 'core/blocks' );

			const blockName = getBlockName( clientId );
			const block = getBlockType< Block >( blockName );

			return block?.icon;
		},
		[ clientId ]
	);

	return icon?.src as JSX.Element;
}
