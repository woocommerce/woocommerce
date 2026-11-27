/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	getTemplateAreaClientIds,
	isTemplateAreaEditingEnabled,
} from './template-areas';

type BlockEditingModeActions = {
	setBlockEditingMode: (
		clientId: string,
		mode: 'disabled' | 'contentOnly'
	) => void;
	unsetBlockEditingMode: ( clientId: string ) => void;
};

/**
 * Lets people select the template blocks around the email content, the way core
 * treats template parts in a locked template: the area itself becomes
 * selectable, while the blocks inside it stay locked.
 */
export function TemplateAreaEditingMode() {
	// Joined into strings so the effect only runs when the IDs change.
	const { areaIds, lockedIds } = useSelect( ( select ) => {
		if ( ! isTemplateAreaEditingEnabled( select ) ) {
			return { areaIds: '', lockedIds: '' };
		}
		const areas = getTemplateAreaClientIds( select );
		return {
			areaIds: areas.join( ',' ),
			lockedIds: select( blockEditorStore )
				.getClientIdsOfDescendants( areas )
				.join( ',' ),
		};
	}, [] );
	// The editing mode actions are missing from the block editor store types.
	const { setBlockEditingMode, unsetBlockEditingMode } = useDispatch(
		blockEditorStore
	) as unknown as BlockEditingModeActions;

	useEffect( () => {
		const areas = areaIds ? areaIds.split( ',' ) : [];
		const locked = lockedIds ? lockedIds.split( ',' ) : [];

		areas.forEach( ( id ) => setBlockEditingMode( id, 'contentOnly' ) );
		locked.forEach( ( id ) => setBlockEditingMode( id, 'disabled' ) );

		return () => {
			[ ...areas, ...locked ].forEach( ( id ) =>
				unsetBlockEditingMode( id )
			);
		};
	}, [ areaIds, lockedIds, setBlockEditingMode, unsetBlockEditingMode ] );

	return null;
}
