/**
 * External dependencies
 */
import { Fill } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getName } from './utils/get-name';
import { BlockFillProps } from './types';

export function BlockFill( { name, clientId, ...props }: BlockFillProps ) {
	const rootClientId = useSelect(
		( select ) => {
			const { getBlockRootClientId } = select( 'core/block-editor' );
			return getBlockRootClientId( clientId );
		},
		[ clientId ]
	);

	if ( ! rootClientId ) return null;

	return <Fill { ...props } name={ getName( name, rootClientId ) } />;
}
