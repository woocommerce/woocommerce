/**
 * External dependencies
 */
import { dispatch, select } from '@wordpress/data';
import { store as interfaceStore } from '@wordpress/interface';
import {
	store as coreStore,
	store as coreDataStore,
} from '@wordpress/core-data';
import { store as preferencesStore } from '@wordpress/preferences';
import { store as noticesStore } from '@wordpress/notices';
import { store as editorStore } from '@wordpress/editor';
import { __, sprintf } from '@wordpress/i18n';
import { apiFetch } from '@wordpress/data-controls';
import wpApiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { decodeEntities } from '@wordpress/html-entities';
import {
	// @ts-expect-error No types for __unstableSerializeAndClean
	__unstableSerializeAndClean,
	parse,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	storeName,
	mainSidebarDocumentTab,
	editorCurrentPostType,
} from './constants';
import {
	SendingPreviewStatus,
	State,
	Feature,
	PersonalizationTag,
} from './types';
import { recordEvent } from '../events';

export const toggleFeature =
	( feature: Feature ) =>
	( { registry } ): unknown =>
		registry.dispatch( preferencesStore ).toggle( storeName, feature );

export const changePreviewDeviceType =
	( deviceType: string ) =>
	( { registry } ) =>
		void registry.dispatch( editorStore ).setDeviceType( deviceType );

export function togglePreviewModal( isOpen: boolean ) {
	return {
		type: 'CHANGE_PREVIEW_STATE',
		state: { isModalOpened: isOpen } as Partial< State[ 'preview' ] >,
	} as const;
}

export function togglePersonalizationTagsModal(
	isOpen: boolean,
	payload: Partial< State[ 'personalizationTags' ] > = {}
) {
	return {
		type: 'CHANGE_PERSONALIZATION_TAGS_STATE',
		state: {
			isModalOpened: isOpen,
			...payload,
		} as Partial< State[ 'personalizationTags' ] >,
	} as const;
}

export function updateSendPreviewEmail( toEmail: string ) {
	return {
		type: 'CHANGE_PREVIEW_STATE',
		state: { toEmail } as Partial< State[ 'preview' ] >,
	} as const;
}

export const openSidebar =
	( key = mainSidebarDocumentTab ) =>
	( { registry } ): unknown => {
		return registry
			.dispatch( interfaceStore )
			.enableComplementaryArea( storeName, key );
	};

export const closeSidebar =
	() =>
	( { registry } ): unknown => {
		return registry
			.dispatch( interfaceStore )
			.disableComplementaryArea( storeName );
	};

export function toggleSettingsSidebarActiveTab( activeTab: string ) {
	return {
		type: 'TOGGLE_SETTINGS_SIDEBAR_ACTIVE_TAB',
		state: { activeTab } as Partial< State[ 'settingsSidebar' ] >,
	} as const;
}

export function* saveEditedEmail() {
	const postId = select( storeName ).getEmailPostId();
	// This returns a promise

	const result = yield dispatch( coreDataStore ).saveEditedEntityRecord(
		'postType',
		editorCurrentPostType,
		postId,
		{ throwOnError: true }
	);

	result.then( () => {
		void dispatch( noticesStore ).createErrorNotice(
			__( 'Email saved!', 'woocommerce' ),
			{
				type: 'snackbar',
				isDismissible: true,
				context: 'email-editor',
			}
		);
	} );

	result.catch( () => {
		void dispatch( noticesStore ).createErrorNotice(
			__(
				'The email could not be saved. Please, clear browser cache and reload the page. If the problem persists, duplicate the email and try again.',
				'woocommerce'
			),
			{
				type: 'default',
				isDismissible: true,
				context: 'email-editor',
			}
		);
	} );
}

export const setTemplateToPost =
	( templateSlug ) =>
	async ( { registry } ) => {
		const postId = registry.select( storeName ).getEmailPostId();
		registry
			.dispatch( coreDataStore )
			.editEntityRecord( 'postType', editorCurrentPostType, postId, {
				template: templateSlug,
			} );
	};

export function* requestSendingNewsletterPreview( email: string ) {
	// If preview is already sending do nothing
	const previewState = select( storeName ).getPreviewState();
	if ( previewState.isSendingPreviewEmail ) {
		return;
	}
	// Initiate sending
	yield {
		type: 'CHANGE_PREVIEW_STATE',
		state: {
			sendingPreviewStatus: null,
			isSendingPreviewEmail: true,
		} as Partial< State[ 'preview' ] >,
	} as const;
	try {
		const postId = select( storeName ).getEmailPostId();

		yield apiFetch( {
			path: '/woocommerce-email-editor/v1/send_preview_email',
			method: 'POST',
			data: {
				email,
				postId,
			},
		} );

		yield {
			type: 'CHANGE_PREVIEW_STATE',
			state: {
				sendingPreviewStatus: SendingPreviewStatus.SUCCESS,
				isSendingPreviewEmail: false,
			},
		};
		recordEvent( 'sent_preview_email', { postId, email } );
	} catch ( errorResponse ) {
		recordEvent( 'sent_preview_email_error', { email } );
		yield {
			type: 'CHANGE_PREVIEW_STATE',
			state: {
				sendingPreviewStatus: SendingPreviewStatus.ERROR,
				isSendingPreviewEmail: false,
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore
				errorMessage: JSON.stringify( errorResponse?.error ),
			},
		};
	}
}

/**
 * Revert template modifications to defaults
 * Created based on https://github.com/WordPress/gutenberg/blob/4d225cc2ba6f09822227e7a820b8a555be7c4d48/packages/editor/src/store/private-actions.js#L241
 *
 * @param template - Template post object
 */
export function revertAndSaveTemplate( template ) {
	return async ( { registry } ) => {
		try {
			const templateEntityConfig = registry
				.select( coreStore )
				.getEntityConfig( 'postType', template.type as string );

			const fileTemplatePath = addQueryArgs(
				`${ templateEntityConfig.baseURL as string }/${
					template.id as string
				}`,
				{ context: 'edit', source: 'theme' }
			);

			const fileTemplate = await wpApiFetch( { path: fileTemplatePath } );

			const serializeBlocks = ( {
				blocks: blocksForSerialization = [],
			} ) => __unstableSerializeAndClean( blocksForSerialization );

			// @ts-expect-error template type is not defined
			const blocks = parse( fileTemplate?.content?.raw as string );

			await registry.dispatch( coreStore ).editEntityRecord(
				'postType',
				template.type as string,
				// @ts-expect-error template type is not defined
				fileTemplate.id as string,
				{
					content: serializeBlocks,
					blocks,
					source: 'theme',
				}
			);
			await registry
				.dispatch( coreStore )
				.saveEditedEntityRecord(
					'postType',
					template.type,
					template.id,
					{}
				);
			void registry.dispatch( noticesStore ).createSuccessNotice(
				sprintf(
					/* translators: The template/part's name. */
					__( '"%s" reset.', 'woocommerce' ),
					decodeEntities( template.title )
				),
				{
					type: 'snackbar',
					id: 'edit-site-template-reverted',
				}
			);
		} catch ( error ) {
			void registry
				.dispatch( noticesStore )
				.createErrorNotice(
					__(
						'An error occurred while reverting the template.',
						'woocommerce'
					),
					{
						type: 'snackbar',
					}
				);
		}
	};
}

export function setIsFetchingPersonalizationTags( isFetching: boolean ) {
	return {
		type: 'SET_IS_FETCHING_PERSONALIZATION_TAGS',
		state: {
			isFetching,
		} as Partial< State[ 'personalizationTags' ] >,
	} as const;
}

export function setPersonalizationTagsList( list: PersonalizationTag[] ) {
	return {
		type: 'SET_PERSONALIZATION_TAGS_LIST',
		state: {
			list,
		} as Partial< State[ 'personalizationTags' ] >,
	} as const;
}
