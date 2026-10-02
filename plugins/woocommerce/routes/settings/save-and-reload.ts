/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export interface SaveNotice {
	status: 'success' | 'error';
	message: string;
}

/**
 * Save, then reload the record whether or not the save worked.
 *
 * The endpoint may store only part of a failed save, or change values on success, so the page
 * shows what the server stored rather than what was typed. Unsaved edits are kept.
 */
export async function saveAndReload( {
	save,
	reload,
}: {
	save: () => Promise< unknown >;
	reload: () => Promise< unknown >;
} ): Promise< SaveNotice > {
	let notice: SaveNotice;
	try {
		await save();
		notice = {
			status: 'success',
			message: __( 'Settings saved.', 'woocommerce' ),
		};
	} catch ( error ) {
		notice = {
			status: 'error',
			message:
				( error instanceof Error && error.message ) ||
				__( 'Unable to save settings.', 'woocommerce' ),
		};
	}
	await reload().catch( () => undefined );
	return notice;
}
