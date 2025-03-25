/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { recordEvent } from '../../events';

/**
 * This component is simplified version of the original one from @wordpress/editor package.
 * The original component can be found here: https://github.com/WordPress/gutenberg/blob/46446b853d740c309c0675c7bf2ca4170a618c42/packages/editor/src/components/autosave-monitor/index.js
 * The main reason for the own solution is that the original component needs to initialize the @wordpress/editor store.
 * We could use the action `setEditedPost` from the editor package, but it is only in a newer version of the editor package.
 */
export function AutosaveMonitor() {
	const { hasEdits, autosaveInterval } = useSelect(
		( select ) => ( {
			hasEdits: select( storeName ).hasEdits(),
			autosaveInterval: select( storeName ).getAutosaveInterval(),
		} ),
		[]
	);

	const { saveEditedEmail } = useDispatch( storeName );

	useEffect( () => {
		let autosaveTimer: NodeJS.Timeout | undefined;

		if ( hasEdits && autosaveInterval > 0 ) {
			autosaveTimer = setTimeout( () => {
				void saveEditedEmail();
				recordEvent( 'editor_content_auto_saved' );
			}, autosaveInterval * 1000 );
		}

		return () => {
			if ( autosaveTimer ) {
				clearTimeout( autosaveTimer );
			}
		};
	}, [ hasEdits, autosaveInterval, saveEditedEmail ] );

	return null;
}
