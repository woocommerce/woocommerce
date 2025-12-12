/**
 * External dependencies
 */
import { createRegistrySelector, createSelector } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { store as preferencesStore } from '@wordpress/preferences';
import { serialize, parse, BlockInstance } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { storeName, PERSONALIZATION_TAG_ENTITY } from './constants';
import {
	State,
	EmailTemplate,
	EmailEditorPostType,
	Feature,
	PersonalizationTag,
	GlobalEmailStylesPost,
} from './types';

function getContentFromEntity( entity ): string {
	if ( entity?.content && typeof entity.content === 'function' ) {
		return entity.content( entity ) as string;
	}
	if ( entity?.blocks ) {
		return serialize( entity.blocks );
	}
	if ( entity?.content ) {
		return entity.content as string;
	}
	return '';
}

const patternsWithParsedBlocks = new WeakMap();
function enhancePatternWithParsedBlocks( pattern ) {
	let enhancedPattern = patternsWithParsedBlocks.get( pattern );
	if ( ! enhancedPattern ) {
		enhancedPattern = {
			...pattern,
			get blocks() {
				return parse( pattern.content );
			},
		};
		patternsWithParsedBlocks.set( pattern, enhancedPattern );
	}
	return enhancedPattern;
}

function regularizedGetEntityRecord( template ) {
	if ( ! template ) {
		return null;
	}
	return {
		...template,
		title: template?.title?.raw || template?.title || '',
		content: template?.content?.raw || template?.content || '',
	};
}

export const isFeatureActive = createRegistrySelector(
	( select ) =>
		( _, feature: Feature ): boolean =>
			!! select( preferencesStore ).get( storeName, feature )
);

export const hasEdits = createRegistrySelector( ( select ) => (): boolean => {
	const postId = select( storeName ).getEmailPostId();
	const postType = select( storeName ).getEmailPostType();
	return !! select( coreDataStore ).hasEditsForEntityRecord(
		'postType',
		postType,
		postId
	);
} );

export const hasEmptyContent = createRegistrySelector(
	( select ) => (): boolean => {
		const postId = select( storeName ).getEmailPostId();
		const postType = select( storeName ).getEmailPostType();

		const post = select( coreDataStore ).getEntityRecord(
			'postType',
			postType,
			postId
		);
		if ( ! post ) {
			return true;
		}

		// @ts-expect-error Missing property in type
		const { content } = post;
		return ! content.raw;
	}
);

export const isEmailSent = createRegistrySelector(
	( select ) => (): boolean => {
		const postId = select( storeName ).getEmailPostId();
		const postType = select( storeName ).getEmailPostType();

		const post = select( coreDataStore ).getEntityRecord(
			'postType',
			postType,
			postId
		);
		if ( ! post ) {
			return false;
		}

		// @ts-expect-error Missing property in type
		const status = post.status;
		return status === 'sent';
	}
);

/**
 * Returns the content of the email being edited.
 *
 * @param {Object} state Global application state.
 * @return {string} Post content.
 */
export const getEditedEmailContent = createRegistrySelector(
	( select ) => (): string => {
		const postId = select( storeName ).getEmailPostId();
		const postType = select( storeName ).getEmailPostType();
		const record = select( coreDataStore ).getEditedEntityRecord(
			'postType',
			postType,
			postId
		) as unknown as
			| { content: string | unknown; blocks: BlockInstance[] }
			| undefined;

		if ( record ) {
			return getContentFromEntity( record );
		}
		return '';
	}
);

export const getSentEmailEditorPosts = createRegistrySelector(
	( select ) => () => {
		const postType = select( storeName ).getEmailPostType();
		return (
			select( coreDataStore )
				.getEntityRecords( 'postType', postType, {
					per_page: 30, // show a maximum of 30 for now
					status: 'publish,sent', // show only sent emails
				} )
				?.filter(
					( post: EmailEditorPostType ) => post?.content?.raw !== '' // filter out empty content
				) || []
		);
	}
);

export const getBlockPatternsForEmailTemplate = createRegistrySelector(
	( select ) => {
		const emailPostType = select( storeName ).getEmailPostType();
		return createSelector(
			() =>
				emailPostType
					? select( coreDataStore )
							.getBlockPatterns()
							.filter( ( { templateTypes, postTypes } ) => {
								return (
									// Make sure the template type matches the required one.
									Array.isArray( templateTypes ) &&
									templateTypes.includes(
										'email-template'
									) &&
									// The current post type must be matched when post types are set.
									( postTypes === undefined ||
										postTypes.length === 0 ||
										postTypes.includes( emailPostType ) )
								);
							} )
							.map( enhancePatternWithParsedBlocks )
					: [],
			() => [ select( coreDataStore ).getBlockPatterns(), emailPostType ]
		);
	}
);

export const canUserEditTemplates = createRegistrySelector(
	( select ) => () => {
		// @ts-expect-error Selector is not typed
		return select( coreDataStore ).canUser( 'create', {
			kind: 'postType',
			name: 'wp_template',
		} );
	}
);

function getTemplate( select, templateId: string ): EmailTemplate {
	if ( canUserEditTemplates() ) {
		return select( coreDataStore ).getEditedEntityRecord(
			'postType',
			'wp_template',
			templateId
		) as unknown as EmailTemplate;
	}
	return regularizedGetEntityRecord(
		select( coreDataStore ).getEntityRecord(
			'postType',
			'wp_template',
			templateId,
			{ context: 'view' }
		)
	) as unknown as EmailTemplate;
}

/**
 * COPIED FROM https://github.com/WordPress/gutenberg/blob/9c6d4fe59763b188d27ad937c2f0daa39e4d9341/packages/edit-post/src/store/selectors.js
 * Retrieves the template of the currently edited post.
 *
 * @return {Object?} Post Template.
 */
export const getEditedPostTemplate = createRegistrySelector(
	( select ) =>
		( _state, templateSlug?: string ): EmailTemplate | null => {
			const currentTemplate =
				templateSlug ||
				select( editorStore ).getEditedPostAttribute( 'template' );

			if ( currentTemplate ) {
				const query: Record< string, string | number > = {
					context: 'view',
					per_page: -1,
					_woocommerce_email_editor: 'fetch-all-templates', // Unused parameter to avoid using cached response.
				};

				const templateWithSameSlug = select( coreDataStore )
					.getEntityRecords( 'postType', 'wp_template', query )
					// @ts-expect-error Missing property in type
					?.find( ( template ) => template.slug === currentTemplate );

				if ( ! templateWithSameSlug ) {
					return null;
				}

				// @ts-expect-error getEditedPostAttribute
				return getTemplate( select, templateWithSameSlug.id );
			}

			const defaultTemplateId = select(
				coreDataStore
			).getDefaultTemplateId( {
				slug: 'email-general',
			} );

			return getTemplate( select, defaultTemplateId );
		}
);

export const getCurrentTemplate = createRegistrySelector( ( select ) => () => {
	const isEditingTemplate =
		select( editorStore ).getCurrentPostType() === 'wp_template';

	if ( isEditingTemplate ) {
		const templateId = select( editorStore ).getCurrentPostId();

		return select( coreDataStore ).getEditedEntityRecord(
			'postType',
			'wp_template',
			// eslint-disable-next-line @typescript-eslint/no-unsafe-argument
			templateId
		) as unknown as EmailTemplate;
	}
	return select( storeName ).getEditedPostTemplate();
} );

export const getCurrentTemplateContent = () => {
	const template = getCurrentTemplate();
	if ( template ) {
		return getContentFromEntity( template );
	}
	return '';
};

export const canUserEditGlobalEmailStyles = createRegistrySelector(
	( select ) => () => {
		const postId = select( storeName ).getGlobalStylesPostId();
		// @ts-expect-error Selector is not typed
		const canEdit = select( coreDataStore ).canUser( 'update', {
			kind: 'root',
			name: 'globalStyles',
			id: postId,
		} );
		return { postId, canEdit };
	}
);
export const getGlobalEmailStylesPost = createRegistrySelector(
	( select ) => () => {
		const { postId, canEdit } = canUserEditGlobalEmailStyles();
		if ( ! postId || canEdit === undefined ) {
			return null;
		}
		if ( postId ) {
			if ( canEdit ) {
				return select( coreDataStore ).getEditedEntityRecord(
					'root',
					'globalStyles',
					postId
				) as GlobalEmailStylesPost;
			}
			return regularizedGetEntityRecord(
				select( coreDataStore ).getEntityRecord(
					'root',
					'globalStyles',
					postId,
					{ context: 'view' }
				)
			) as GlobalEmailStylesPost;
		}
		return null;
	}
);

/**
 * Retrieves the email templates.
 */
export const getEmailTemplates = createRegistrySelector( ( select ) => () => {
	const postType = select( storeName ).getEmailPostType();
	return (
		select( coreDataStore )
			.getEntityRecords( 'postType', 'wp_template', {
				per_page: -1,
				post_type: postType,
				context: 'view',
			} )
			// We still need to filter the templates because, in some cases, the API also returns custom templates
			// ignoring the post_type filter in the query
			?.filter( ( template ) =>
				// @ts-expect-error Missing property in type
				template.post_types.includes( postType )
			)
	);
} );

export function getEmailPostId( state: State ): number | string {
	return state.postId;
}

export function getEmailPostType( state: State ): string {
	return state.postType;
}

export function getInitialEditorSettings(
	state: State
): State[ 'editorSettings' ] {
	return state.editorSettings;
}

export function getPaletteColors(
	state: State
): State[ 'editorSettings' ][ '__experimentalFeatures' ][ 'color' ][ 'palette' ] {
	// eslint-disable-next-line no-underscore-dangle
	return state.editorSettings?.__experimentalFeatures?.color?.palette;
}

export function getPreviewState( state: State ): State[ 'preview' ] {
	return state.preview;
}

export const getPersonalizationTagsList = createRegistrySelector(
	( select ) => () => {
		const tags = ( select( coreDataStore ).getEntityRecords(
			PERSONALIZATION_TAG_ENTITY.kind,
			PERSONALIZATION_TAG_ENTITY.name,
			{
				context: 'view',
				per_page: -1,
			}
		) || [] ) as PersonalizationTag[];

		const postType = select( storeName ).getEmailPostType();

		if ( ! postType ) {
			return tags;
		}

		// When postType is template, we filter tags by registered template postTypes.
		if ( postType === 'wp_template' ) {
			const postTemplate = select( storeName ).getCurrentTemplate();
			return tags.filter( ( tag ) => {
				return (
					tag.postTypes === undefined ||
					tag.postTypes.length === 0 ||
					( Array.isArray( postTemplate.post_types ) &&
						postTemplate.post_types.some( ( pt ) =>
							tag.postTypes.includes( pt )
						) )
				);
			} );
		}

		return tags.filter( ( tag ) => {
			return (
				tag.postTypes === undefined ||
				tag.postTypes.length === 0 ||
				tag.postTypes.includes( postType )
			);
		} );
	}
);

export function getStyles( state: State ): State[ 'theme' ][ 'styles' ] {
	return state.theme?.styles;
}

export function getTheme( state: State ): State[ 'theme' ] {
	return state.theme;
}

export function getGlobalStylesPostId( state: State ): number | null {
	return state.styles.globalStylesPostId;
}

export function getUrls( state: State ): State[ 'urls' ] {
	return state.urls;
}

export function getContentValidation(
	state: State
): State[ 'contentValidation' ] {
	return state.contentValidation;
}
