/**
 * External dependencies
 */
import { Fill } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { useBlockEditContext } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { getName } from './utils/get-name';
import { BlockFillProps } from './types';

export function BlockFill( {
	name,
	slotContainerBlockName,
	...props
}: BlockFillProps ) {
	const { clientId } = useBlockEditContext();

	const closestAncestorClientId = useSelect(
		( select ) => {
			// @ts-expect-error Outdated type definition.
			const { getBlockParentsByBlockName } =
				select( 'core/block-editor' );

			const [ closestParentClientId ] = getBlockParentsByBlockName(
				clientId,
				slotContainerBlockName,
				true
			);

			return closestParentClientId;
		},
		[ clientId, slotContainerBlockName ]
	);

	if ( ! closestAncestorClientId ) return null;

	return (
		<Fill { ...props } name={ getName( name, closestAncestorClientId ) } />
	);
}
