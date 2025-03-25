/**
 * External dependencies
 */
import { EditorSettings, EditorColor } from '@wordpress/block-editor/index';
import { BlockInstance } from '@wordpress/blocks/index';
import { Post } from '@wordpress/core-data/build-types/entity-types/post';

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

export type EmailEditorSettings = EditorSettings & ExperimentalSettings;

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
	elements?: {
		heading: {
			color: {
				text: string;
			};
			typography: TypographyProperties;
		};
	};
};

export type EmailEditorLayout = {
	type: string;
	contentSize: string;
};

export type EmailEditorUrls = {
	send?: string;
	listings: string;
};

export type PersonalizationTag = {
	name: string;
	token: string;
	category: string;
	attributes: string[];
	valueToInsert: string;
};

export type State = {
	inserterSidebar: {
		isOpened: boolean;
	};
	listviewSidebar: {
		isOpened: boolean;
	};
	settingsSidebar: {
		activeTab: string;
	};
	postId: number;
	editorSettings: EmailEditorSettings;
	theme: EmailTheme;
	styles: {
		globalStylesPostId: number | null;
	};
	autosaveInterval: number;
	urls: EmailEditorUrls;
	preview: {
		deviceType: string;
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
};

export type EmailTemplate = {
	id: string;
	slug: string;
	content: string;
	title: string;
	type: string;
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
