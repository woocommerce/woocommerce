/**
 * External dependencies
 */
import { EditorSettings, EditorColor } from '@wordpress/block-editor/index';
import { BlockInstance } from '@wordpress/blocks/index';
import { Post } from '@wordpress/core-data/build-types/entity-types/post';
import type { WpTemplate } from '@wordpress/core-data';

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
	};

export type EmailTheme = {
	version?: number;
	styles?: EmailStyles;
	// Ref: https://github.com/WordPress/gutenberg/blob/38d0a4351105e6ba4b72c4dcb90985305aacf921/packages/block-editor/src/components/global-styles/hooks.js#L24C7-L24C21
	settings?: {
		appearanceTools?: boolean;
		useRootPaddingAwareAlignments?: boolean;
		background?: {
			backgroundImage?: boolean;
			backgroundRepeat?: boolean;
			backgroundSize?: boolean;
			backgroundPosition?: boolean;
		};
		border?: {
			radius?: boolean;
			width?: boolean;
			style?: boolean;
			color?: boolean;
		};
		shadow?: {
			presets?: boolean;
			defaultPresets?: boolean;
		};
		color?: {
			background?: boolean;
			button?: boolean;
			caption?: boolean;
			custom?: boolean;
			customDuotone?: boolean;
			customGradient?: boolean;
			defaultDuotone?: boolean;
			defaultGradients?: boolean;
			defaultPalette?: boolean;
			duotone?: boolean;
			gradients?: {
				default?: boolean;
				theme?: boolean;
				custom?: boolean;
			};
			heading?: boolean;
			link?: boolean;
			palette?: boolean;
			text?: boolean;
		};
		dimensions?: {
			aspectRatio?: boolean;
			minHeight?: boolean;
		};
		layout?: {
			contentSize?: string;
			wideSize?: string;
		};
		spacing?: {
			customSpacingSize?: number;
			blockGap?: number;
			margin?: boolean;
			padding?: boolean;
			spacingSizes?: number[];
			spacingScale?: number;
			units?: string[];
		};
		position?: {
			fixed?: boolean;
			sticky?: boolean;
		};
		typography?: {
			customFontSize?: boolean;
			defaultFontSizes?: boolean;
			dropCap?: boolean;
			fontFamilies?: boolean;
			fontSizes?: boolean;
			fontStyle?: boolean;
			fontWeight?: boolean;
			letterSpacing?: boolean;
			lineHeight?: boolean;
			textColumns?: boolean;
			textDecoration?: boolean;
			textTransform?: boolean;
			writingMode?: boolean;
		};
		lightbox?: {
			enabled?: boolean;
			allowEditing?: boolean;
		};
	};
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
	editorSettings: EmailEditorSettings;
	theme: EmailTheme;
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
	personalizationTags: {
		list: PersonalizationTag[];
		isFetching: boolean;
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

export type TemplateCategory = 'recent' | 'basic';

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
