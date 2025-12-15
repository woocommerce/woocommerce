/**
 * External dependencies
 */
import { EditorSettings, EditorColor } from '@wordpress/block-editor/index';
import { BlockInstance } from '@wordpress/blocks/index';
import { Post } from '@wordpress/core-data/build-types/entity-types/post';
import type { WpTemplate } from '@wordpress/core-data';
import type { GlobalStylesConfig } from '@wordpress/global-styles-engine';

export interface EmailTemplate extends Omit< WpTemplate, 'title' > {
	post_types: string[];
	title: string;
}

export enum SendingPreviewStatus {
	SUCCESS = 'success',
	ERROR = 'error',
}

export type ExperimentalSettings = {
	__experimentalFeatures: {
		color: {
			custom: boolean;
			text: boolean;
			background: boolean;
			customGradient: boolean;
			defaultPalette: boolean;
			palette: {
				default: EditorColor[];
				theme: EditorColor[];
			};
			gradients: {
				default: EditorColor[];
			};
		};
	};
};

export type EmailEditorSettings = EditorSettings &
	ExperimentalSettings & {
		isPreviewMode: boolean;
		allowedIframeStyleHandles?: string[];
		styles?: EmailBuiltStyles[];
	};

export type EmailTheme = Omit< GlobalStylesConfig, 'styles' > & {
	styles: EmailStyles;
};

export type GlobalEmailStylesPost = EmailTheme & {
	id: number;
};

export interface TypographyProperties {
	fontSize: string;
	fontFamily: string;
	fontStyle: string;
	fontWeight: string;
	letterSpacing: string;
	lineHeight: string;
	textDecoration: string;
	textTransform:
		| 'none'
		| 'capitalize'
		| 'uppercase'
		| 'lowercase'
		| 'full-width'
		| 'full-size-kana';
}

export type EmailStyles = {
	spacing?: {
		blockGap: string;
		padding: {
			bottom: string;
			left: string;
			right: string;
			top: string;
		};
	};
	color?: {
		background: string;
		text: string;
	};
	typography?: TypographyProperties;
	elements?: Record< string, ElementStyleProperties >;
};

interface ElementStyleProperties {
	typography: TypographyProperties;
	color?: {
		background: string;
		text: string;
	};
}

export type EmailBuiltStyles = {
	css: string;
};

export type EmailEditorLayout = {
	type: string;
	contentSize: string;
};

export type EmailEditorUrls = {
	back: string;
	send?: string;
	listings: string;
	createCoupon?: string;
};

export type PersonalizationTag = {
	name: string;
	token: string;
	category: string;
	attributes: string[];
	valueToInsert: string;
	postTypes: string[];
};

export type ContentValidation = {
	validateContent: () => boolean;
};

export type State = {
	postId?: number | string; // Template use strings
	postType?: string;
	editorSettings?: EmailEditorSettings;
	theme?: EmailTheme;
	styles: {
		globalStylesPostId: number | null;
	};
	urls: EmailEditorUrls;
	preview: {
		toEmail: string;
		isModalOpened: boolean;
		isSendingPreviewEmail: boolean;
		sendingPreviewStatus: SendingPreviewStatus | null;
		errorMessage?: string;
	};
	contentValidation?: ContentValidation;
};

export type EmailTemplatePreview = Omit<
	EmailTemplate,
	'content' | 'title'
> & {
	content: {
		block_version: number;
		raw: string;
	};
	title: {
		raw: string;
		rendered: string;
	};
};

export type TemplatePreview = {
	id: string;
	slug: string;
	displayName: string;
	previewContentParsed: BlockInstance[];
	emailParsed: BlockInstance[];
	template: EmailTemplatePreview;
	category?: TemplateCategory;
	type: string;
};

export type TemplateCategory = string;

export type Feature =
	| 'fullscreenMode'
	| 'showIconLabels'
	| 'fixedToolbar'
	| 'focusMode';

export type EmailEditorPostType = Omit< Post, 'type' > & {
	type: string;
};

export type EmailContentValidationAction = {
	label: string;
	onClick: () => void;
};

export type EmailContentValidationRule = {
	id: string;
	testContent: ( emailContent: string ) => boolean;
	message: string;
	actions: EmailContentValidationAction[];
};

export type CoreDataError = { message?: string; code?: string };

export type PostWithPermissions = Post & {
	permissions: {
		delete: boolean;
		update: boolean;
	};
};

export type EmailEditorConfig = {
	editorSettings: EmailEditorSettings;
	theme: EmailTheme;
	urls: EmailEditorUrls;
	userEmail: string;
	globalStylesPostId?: number | null;
};
