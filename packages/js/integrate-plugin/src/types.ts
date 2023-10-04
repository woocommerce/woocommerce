export type PluginData = {
	name: string;
	version: string | null;
	description: string | null;
	textdomain: string | null;
	namespace: string | null;
};

export type PluginConfig = {
	modules: string[];
};

export type OptionValues = {
	wpScripts: boolean;
	wpEnv: boolean;
	template?: string;
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
	author: string;
	npmDependencies: string[];
	npmDevDependencies?: string[];
	customScripts?: Record< string, string >;
};

export type ComposerJSONData = {
	composerDependencies: string[];
	composerDevDependencies: string[];
};

export type ScriptConfig = PluginData &
	Partial< PluginConfig > &
	Omit< OptionValues, 'namespace' > &
	BlockJSONData &
	PackageJSONData &
	ComposerJSONData & {
		namespaceSnakeCase: string;
		namespacePascalCase: string;
	};
