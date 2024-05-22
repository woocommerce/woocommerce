/**
 * External dependencies
 */
import { RequestUtils as CoreRequestUtils } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { createPostFromFile } from './posts';
import {
	getTemplates,
	revertTemplate,
	createTemplateFromFile,
	WPTemplate,
	WPTemplateType,
	TemplateCompiler,
} from './templates';

export class RequestUtils extends CoreRequestUtils {
	// The `setup` override is necessary only until
	// https://github.com/WordPress/gutenberg/pull/59362 is merged.
	static async setup( ...args: Parameters< typeof CoreRequestUtils.setup > ) {
		const { request, user, storageState, storageStatePath, baseURL } =
			await CoreRequestUtils.setup( ...args );

		// We need those checks to satisfy TypeScript.
		if ( ! storageState ) {
			throw new Error( 'Storage state is required' );
		}

		if ( ! storageStatePath ) {
			throw new Error( 'Storage state path is required' );
		}

		if ( ! baseURL ) {
			throw new Error( 'Base URL is required' );
		}

		return new this( request, {
			user,
			storageState,
			storageStatePath,
			baseURL,
		} );
	}

	/** @borrows getTemplates as this.getTemplates */
	getTemplates: typeof getTemplates = getTemplates.bind( this );
	/** @borrows revertTemplate as this.revertTemplate */
	revertTemplate: typeof revertTemplate = revertTemplate.bind( this );
	/** @borrows createPostFromFile as this.createPostFromFile */
	createPostFromFile: typeof createPostFromFile =
		createPostFromFile.bind( this );
	/** @borrows createTemplateFromFile as this.createTemplateFromFile */
	createTemplateFromFile: typeof createTemplateFromFile =
		createTemplateFromFile.bind( this );
}

export { WPTemplate, WPTemplateType, TemplateCompiler };
