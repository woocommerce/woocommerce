/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { parse } from '@wordpress/blocks';
import { BlockInstance } from '@wordpress/blocks/index';
import { useSelect } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import {
	storeName,
	EmailTemplatePreview,
	TemplatePreview,
	EmailEditorPostType,
} from '../store';

// Shared reference to an empty array for cases where it is important to avoid
// returning a new array reference on every invocation
const EMPTY_ARRAY = [];

/**
 * We need to merge pattern blocks and template blocks for BlockPreview component.
 *
 * @param templateBlocks - Parsed template blocks
 * @param innerBlocks    - Blocks to be set as content blocks for the template preview
 */
function setPostContentInnerBlocks(
	templateBlocks: BlockInstance[],
	innerBlocks: BlockInstance[]
): BlockInstance[] {
	return templateBlocks.map( ( block: BlockInstance ) => {
		if ( block.name === 'core/post-content' ) {
			return {
				...block,
				name: 'core/group', // Change the name to group to render the innerBlocks
				innerBlocks,
			};
		}
		if ( block.innerBlocks?.length ) {
			return {
				...block,
				innerBlocks: setPostContentInnerBlocks(
					block.innerBlocks,
					innerBlocks
				),
			};
		}
		return block;
	} );
}

const InternalTemplateCache = {};

type GenerateTemplateCssThemeType = {
	postTemplateContent?: EmailTemplatePreview;
};
/**
 * @param post
 * @param allTemplates
 */
function generateTemplateContent(
	post: EmailEditorPostType,
	allTemplates: TemplatePreview[] = []
): GenerateTemplateCssThemeType {
	const contentTemplate = post.template;

	const defaultReturnObject = {
		postTemplateContent: null,
	};

	if ( ! contentTemplate ) {
		return defaultReturnObject;
	}

	if ( InternalTemplateCache[ contentTemplate ] ) {
		return InternalTemplateCache[ contentTemplate ];
	}

	const postTemplate = allTemplates.find(
		( template ) => template.slug === contentTemplate
	);

	if ( ! postTemplate ) {
		return defaultReturnObject;
	}

	const templateContent = {
		postTemplateContent: postTemplate?.template,
	};

	InternalTemplateCache[ contentTemplate ] = templateContent;

	return templateContent;
}

export function usePreviewTemplates(
	customEmailContent = ''
): [ TemplatePreview[], TemplatePreview[], boolean ] {
	const { templates, patterns, emailPosts, hasEmailPosts } = useSelect(
		( select ) => {
			const rawEmailPosts = select( storeName ).getSentEmailEditorPosts();

			return {
				templates: select( storeName ).getEmailTemplates(),
				patterns:
					select( storeName ).getBlockPatternsForEmailTemplate(),
				emailPosts: rawEmailPosts,
				hasEmailPosts: !! ( rawEmailPosts && rawEmailPosts?.length ),
			};
		},
		[]
	);

	const allTemplates = useMemo( () => {
		let contentPatterns = [];
		const parsedCustomEmailContent =
			customEmailContent && parse( customEmailContent );

		// If there is a custom email content passed from outside we use it as email content for preview
		// otherwise generate one preview per template and pattern
		if ( parsedCustomEmailContent ) {
			contentPatterns = [ { blocks: parsedCustomEmailContent } ];
		} else {
			contentPatterns = patterns;
		}

		if ( ! contentPatterns || ! templates ) {
			return EMPTY_ARRAY;
		}

		const templateToPreview = [];
		// We don't want to show the blank template in the list
		templates
			?.filter(
				( template: EmailTemplatePreview ) =>
					template.slug !== 'email-general'
			)
			?.forEach( ( template: EmailTemplatePreview ) => {
				contentPatterns?.forEach( ( contentPattern ) => {
					let parsedTemplate = parse( template.content?.raw );
					parsedTemplate = setPostContentInnerBlocks(
						parsedTemplate,
						contentPattern.blocks
					);
					templateToPreview.push( {
						id: template.id,
						slug: template.slug,
						// eslint-disable-next-line @typescript-eslint/no-unsafe-argument
						previewContentParsed: parsedTemplate,
						emailParsed: contentPattern.blocks,
						template,
						category: 'basic', // TODO: This will be updated once template category is implemented
						type: template.type,
						displayName: contentPattern.title
							? `${ template.title.rendered } - ${ contentPattern.title }`
							: template.title.rendered,
					} );
				} );
			} );
		return templateToPreview;
	}, [ templates, patterns, customEmailContent ] );

	const allEmailPosts = useMemo( () => {
		return emailPosts?.map( ( post: EmailEditorPostType ) => {
			const preferredTitle = applyFilters(
				'woocommerce_email_editor_preferred_template_title',
				'',
				post
			);
			const { postTemplateContent } = generateTemplateContent(
				post,
				allTemplates
			);
			const parsedPostContent = parse( post.content?.raw );

			let parsedPostContentWithTemplate = parsedPostContent;

			if ( postTemplateContent?.content?.raw ) {
				parsedPostContentWithTemplate = setPostContentInnerBlocks(
					parse( postTemplateContent?.content?.raw ),
					parsedPostContent
				);
			}
			const template = {
				...post,
				title: {
					raw: post.title.raw,
					rendered: preferredTitle || post.title.rendered,
				},
			};
			return {
				id: post.id,
				slug: post.slug,
				previewContentParsed: parsedPostContentWithTemplate,
				emailParsed: parsedPostContent,
				category: 'recent',
				type: post.type,
				displayName: template.title.rendered,
				template,
			};
		} ) as unknown as TemplatePreview[];
	}, [ emailPosts, allTemplates ] );

	return [
		allTemplates || EMPTY_ARRAY,
		allEmailPosts || EMPTY_ARRAY,
		hasEmailPosts,
	];
}
