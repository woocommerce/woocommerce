/**
 * External dependencies
 */
import { Block } from '@wordpress/blocks';

export type PluginData = {
	name: string;
	version?: string;
	description?: string;
	textdomain?: string;
	namespace?: string;
};

export type PluginConfig = {
	modules: string[];
};

export type OptionValues = {
	wpScripts: boolean;
	wpEnv: boolean;
	template: string;
	variant?: string;
	includesDir?: string;
	srcDir?: string;
	namespace?: string;
};

export type BlockJSONData = {
	$schema: string;
	apiVersion: number;
};

export type PackageJSONData = {
	license: string;
	licenseURI: string;
	author: string;
	npmDependencies: string[];
	npmDevDependencies?: string[];
	customScripts?: Record< string, string >;
};

export type ComposerJSONData = {
	composerDependencies: string[];
	composerDevDependencies: string[];
};

export type TemplateVariant = {
	slug: string;
	title: string;
	render: string;
};

export type PluginTemplateProject = {
	wpScripts?: boolean;
	wpEnv?: boolean;
	customScripts?: Record< string, string >;
	npmDependencies?: string[];
	npmDevDependencies?: string[];
	customPackageJSON?: Record< string, string >;
};

export type PluginTemplateHeaderFields = {
	pluginURI?: string;
	version?: string;
	author?: string;
	description?: string;
	license?: string;
	licenseURI?: string;
	domainPath?: string;
	textdomain?: string;
	updateURI?: string;
};

export type PluginTemplateDefaults = Partial<
	Omit< Block, 'editorScript' | 'editorStyle' | 'style' >
> &
	PluginTemplateProject &
	PluginTemplateHeaderFields & {
		$schema?: string;
		slug: string;
		namespace?: string;
		dashicon?: string;
		viewScript?: string;
		editorScript?: null | string;
		editorStyle?: null | string;
		style?: null | string;
		variantVars?: Record< string, boolean >;
		includesDir?: string;
		srcDir?: string;
		modules?: string[];
		template?: string;
		namespacePascalCase?: string;
	};

export type PluginTemplate = {
	pluginTemplatesPath?: string;
	blockTemplatesPath?: string;
	assetsPath?: string;
	includesTemplatesPath?: string;
	srcTemplatesPath?: string;
	defaultValues: PluginTemplateDefaults;
	templatesPath?: string;
	variants: Record< string, TemplateVariant | Record< string, void > >;
	includesOutputTemplates?: Record< string, string >;
	pluginOutputTemplates?: Record< string, string >;
	srcOutputTemplates?: Record< string, string >;
	onComplete?: () => void;
};

// export type TemplateData = {
// 	pluginTemplatesPath: string;
// 	blockTemplatesPath: string;
// 	includesTemplatesPath: string;
// 	srcTemplatesPath: string;
// 	assetsPath: string;
// };

export type ScriptConfig = PluginData &
	PluginConfig &
	Omit< OptionValues, 'namespace' > &
	BlockJSONData &
	PackageJSONData &
	ComposerJSONData & {
		namespaceSnakeCase: string;
		namespacePascalCase: string;
	} & Omit< PluginTemplate, 'variants' > &
	TemplateVariant;
