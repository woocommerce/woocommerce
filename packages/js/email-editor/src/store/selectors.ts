/**
 * External dependencies
 */
import { createRegistrySelector, createSelector } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { store as interfaceStore } from '@wordpress/interface';
import { store as editorStore } from '@wordpress/editor';
import { store as preferencesStore } from '@wordpress/preferences';
import { serialize, parse } from '@wordpress/blocks';
import { BlockInstance } from '@wordpress/blocks/index';
import { Post } from '@wordpress/core-data/build-types/entity-types/post';

/**
 * Internal dependencies
 */
import { storeName, editorCurrentPostType } from './constants';
import { State, Feature, EmailTemplate, EmailEditorPostType } from './types';

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

export const isSidebarOpened = createRegistrySelector(
	( select ) => (): boolean =>
		!! select( interfaceStore ).getActiveComplementaryArea( storeName )
);

export const hasEdits = createRegistrySelector( ( select ) => (): boolean => {
	const postId = select( storeName ).getEmailPostId();
	return !! select( coreDataStore ).hasEditsForEntityRecord(
		'postType',
		editorCurrentPostType,
		postId
	);
} );

export const isEmailLoaded = createRegistrySelector(
	( select ) => (): boolean => {
		const postId = select( storeName ).getEmailPostId();
		return !! select( coreDataStore ).getEntityRecord(
			'postType',
			editorCurrentPostType,
			postId
		);
	}
);

export const isSaving = createRegistrySelector( ( select ) => (): boolean => {
	const postId = select( storeName ).getEmailPostId();
	return !! select( coreDataStore ).isSavingEntityRecord(
		'postType',
		editorCurrentPostType,
		postId
	);
} );

export const isEmpty = createRegistrySelector( ( select ) => (): boolean => {
	const postId = select( storeName ).getEmailPostId();

	const post: EmailEditorPostType = select( coreDataStore ).getEntityRecord(
		'postType',
		editorCurrentPostType,
		postId
	);
	if ( ! post ) {
		return true;
	}

	const { content, title } = post;
	return ! content.raw && ! title.raw;
} );

export const hasEmptyContent = createRegistrySelector(
	( select ) => (): boolean => {
		const postId = select( storeName ).getEmailPostId();

		const post = select( coreDataStore ).getEntityRecord(
			'postType',
			editorCurrentPostType,
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

		const post = select( coreDataStore ).getEntityRecord(
			'postType',
			editorCurrentPostType,
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
		const record = select( coreDataStore ).getEditedEntityRecord(
			'postType',
			editorCurrentPostType,
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
	( select ) => () =>
		select( coreDataStore )
			.getEntityRecords( 'postType', editorCurrentPostType, {
				per_page: 30, // show a maximum of 30 for now
				status: 'publish,sent', // show only sent emails
			} )
			?.filter(
				( post: EmailEditorPostType ) => post?.content?.raw !== '' // filter out empty content
			) || []
);

export const getBlockPatternsForEmailTemplate = createRegistrySelector(
	( select ) =>
		createSelector(
			() =>
				select( coreDataStore )
					.getBlockPatterns()
					.filter(
						( { templateTypes } ) =>
							Array.isArray( templateTypes ) &&
							templateTypes.includes( 'email-template' )
					)
					.map( enhancePatternWithParsedBlocks ),
			() => [ select( coreDataStore ).getBlockPatterns() ]
		)
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
	( select ) => (): EmailTemplate => {
		const currentTemplate =
			// @ts-expect-error Expected 0 arguments, but got 1.
			select( editorStore ).getEditedPostAttribute( 'template' );

		if ( currentTemplate ) {
			const templateWithSameSlug = select( coreDataStore )
				.getEntityRecords( 'postType', 'wp_template', {
					per_page: -1,
					context: 'view',
				} )
				// @ts-expect-error Missing property in type
				?.find( ( template ) => template.slug === currentTemplate );

			if ( ! templateWithSameSlug ) {
				return regularizedGetEntityRecord(
					templateWithSameSlug
				) as EmailTemplate;
			}

			// @ts-expect-error getEditedPostAttribute
			return getTemplate( select, templateWithSameSlug.id );
		}

		const defaultTemplateId = select( coreDataStore ).getDefaultTemplateId(
			{
				slug: 'email-general',
			}
		);

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
	return getEditedPostTemplate();
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

		if ( postId ) {
			if ( canEdit ) {
				return select( coreDataStore ).getEditedEntityRecord(
					'postType',
					'wp_global_styles',
					postId
				) as unknown as Post;
			}
			return regularizedGetEntityRecord(
				select( coreDataStore ).getEntityRecord(
					'postType',
					'wp_global_styles',
					postId,
					{ context: 'view' }
				)
			) as unknown as Post;
		}
		return getEditedPostTemplate();
	}
);

/**
 * Retrieves the email templates.
 */
export const getEmailTemplates = createRegistrySelector(
	( select ) => () =>
		select( coreDataStore )
			.getEntityRecords( 'postType', 'wp_template', {
				per_page: -1,
				post_type: editorCurrentPostType,
				context: 'view',
			} )
			// We still need to filter the templates because, in some cases, the API also returns custom templates
			// ignoring the post_type filter in the query
			?.filter( ( template ) =>
				// @ts-expect-error Missing property in type
				template.post_types.includes( editorCurrentPostType )
			)
);

export function getEmailPostId( state: State ): number {
	return state.postId;
}

export function getSettingsSidebarActiveTab( state: State ): string {
	return state.settingsSidebar.activeTab;
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
	return state.editorSettings.__experimentalFeatures.color.palette;
}

export function getPreviewState( state: State ): State[ 'preview' ] {
	return state.preview;
}

export function getPersonalizationTagsState(
	state: State
): State[ 'personalizationTags' ] {
	return state.personalizationTags;
}

export function getPersonalizationTagsList(
	state: State
): State[ 'personalizationTags' ][ 'list' ] {
	return state.personalizationTags.list;
}

export const getDeviceType = createRegistrySelector(
	( select ) => () =>
		// @ts-expect-error getDeviceType is missing in types.
		select( editorStore ).getDeviceType() as string
);

export function getStyles( state: State ): State[ 'theme' ][ 'styles' ] {
	return state.theme.styles;
}

export function getAutosaveInterval(
	state: State
): State[ 'autosaveInterval' ] {
	return state.autosaveInterval;
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
