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
import { resetFeatureFlag, setFeatureFlag } from './feature-flag';

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
	/** @borrows setFeatureFlag as this.setFeatureFlag */
	setFeatureFlag: typeof setFeatureFlag = setFeatureFlag.bind( this );
	/** @borrows resetFeatureFlag as this.resetFeatureFlag */
	resetFeatureFlag: typeof resetFeatureFlag = resetFeatureFlag.bind( this );
}

export { TemplateCompiler, PostCompiler };
