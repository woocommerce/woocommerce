export interface ParsedArgs {
	watch: boolean;
	format: 'esmodules' | 'commonjs';
	debug: boolean;
}

export function parseBuildArgs(
	argv: readonly string[] = process.argv
): ParsedArgs {
	return {
		watch: argv.includes( '--watch' ),
		format: argv.includes( '--commonjs' ) ? 'commonjs' : 'esmodules',
		debug: argv.includes( '--debug' ),
	};
}
