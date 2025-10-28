/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { storeName } from './constants';
import {
	SendingPreviewStatus,
	State,
	ContentValidation,
	EmailEditorSettings,
	EmailTheme,
	EmailEditorUrls,
} from './types';
import { recordEvent } from '../events';

export function togglePreviewModal( isOpen: boolean ) {
	return {
		type: 'CHANGE_PREVIEW_STATE',
		state: { isModalOpened: isOpen } as Partial< State[ 'preview' ] >,
	} as const;
}

export function updateSendPreviewEmail( toEmail: string ) {
	return {
		type: 'CHANGE_PREVIEW_STATE',
		state: { toEmail } as Partial< State[ 'preview' ] >,
	} as const;
}

export function setEmailPost( postId: number | string, postType: string ) {
	if ( ! postId || ! postType ) {
		throw new Error(
			'setEmailPost requires valid postId and postType parameters'
		);
	}

	return {
		type: 'SET_EMAIL_POST',
		state: { postId, postType } as Partial< State >,
	} as const;
}

export const setTemplateToPost =
	( templateSlug ) =>
	async ( { registry } ) => {
		const postId = registry.select( storeName ).getEmailPostId();
		const postType = registry.select( storeName ).getEmailPostType();
		registry
			.dispatch( coreDataStore )
			.editEntityRecord( 'postType', postType, postId, {
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

export function setContentValidation(
	validation: ContentValidation | undefined
) {
	return {
		type: 'SET_CONTENT_VALIDATION',
		validation,
	} as const;
}

export function setEditorSettings( editorSettings: EmailEditorSettings ) {
	return {
		type: 'SET_EDITOR_SETTINGS',
		editorSettings,
	} as const;
}

export function setEditorTheme( theme: EmailTheme ) {
	return {
		type: 'SET_EDITOR_THEME',
		theme,
	} as const;
}

export function setEditorUrls( urls: EmailEditorUrls ) {
	return {
		type: 'SET_EDITOR_URLS',
		urls,
	} as const;
}

export function setEditorConfig( config: {
	editorSettings: EmailEditorSettings;
	theme: EmailTheme;
	urls: EmailEditorUrls;
	userEmail: string;
	globalStylesPostId?: number | null;
} ) {
	return {
		type: 'SET_EDITOR_CONFIG',
		config,
	} as const;
}
