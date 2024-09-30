/**
 * External dependencies
 */
import { RequestUtils as CoreRequestUtils } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { createPostFromFile, PostCompiler } from './posts';
import {
	getTemplates,
	revertTemplate,
	createTemplateFromFile,
	TemplateCompiler,
} from './templates';

export class RequestUtils extends CoreRequestUtils {
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

export { TemplateCompiler, PostCompiler };
