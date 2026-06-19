import type { Config as JestConfig } from 'jest';

export type WordPressVersionTarget = 'latest' | 'latest-1' | 'gutenberg';

export interface WordPressDependencyCompatLogger {
	info?: ( message: string ) => void;
}

export interface WordPressDependencyCompatOptions {
	cacheRoot?: string;
	cwd?: string;
	logger?: WordPressDependencyCompatLogger;
	packages?: string | string[];
	wpVersion?: WordPressVersionTarget;
}

export interface WordPressDependencyCompatConfig
	extends Omit< JestConfig, 'moduleNameMapper' | 'transform' > {
	moduleNameMapper?: Record< string, string | string[] >;
	transform?: Record< string, unknown >;
}

export type WordPressDependencyCompatResult<
	Config extends WordPressDependencyCompatConfig
> = Config & {
	moduleNameMapper: NonNullable< Config[ 'moduleNameMapper' ] > &
		Record< string, string >;
	transform?: NonNullable< Config[ 'transform' ] > &
		Record< string, unknown >;
	transformIgnorePatterns?: JestConfig[ 'transformIgnorePatterns' ];
};

export function getCurrentWordPressVersion(): WordPressVersionTarget;
export function isLatestGutenberg(): boolean;
export function isLatestMinusOneWordPress(): boolean;
export function isLatestWordPress(): boolean;
export function withWordPressDependencyCompat<
	Config extends WordPressDependencyCompatConfig = WordPressDependencyCompatConfig
>(
	jestConfig?: Config,
	options?: WordPressDependencyCompatOptions
): WordPressDependencyCompatResult< Config >;
