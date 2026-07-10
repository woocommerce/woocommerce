/* eslint-disable no-console -- This module is the build logger; console output is its purpose. */

const PRETTY =
	Boolean( process.stdout.isTTY ) &&
	! process.env.NO_COLOR &&
	! process.env.CI;

const COLOR = {
	reset: PRETTY ? '\x1b[0m' : '',
	dim: PRETTY ? '\x1b[2m' : '',
	red: PRETTY ? '\x1b[31m' : '',
	green: PRETTY ? '\x1b[32m' : '',
	yellow: PRETTY ? '\x1b[33m' : '',
	blue: PRETTY ? '\x1b[34m' : '',
	cyan: PRETTY ? '\x1b[36m' : '',
};

const PREFIXES = {
	build: { icon: '📦', color: COLOR.cyan },
	watch: { icon: '👀', color: COLOR.blue },
	ok: { icon: '✓', color: COLOR.green },
	warn: { icon: '⚠', color: COLOR.yellow },
	error: { icon: '✗', color: COLOR.red },
	debug: { icon: '·', color: COLOR.dim },
};

export type LoggerPrefix = keyof typeof PREFIXES;

let debugEnabled = false;

export function setDebugEnabled( enabled: boolean ): void {
	debugEnabled = enabled;
}

function format( prefix: LoggerPrefix, message: string ): string {
	const style = PREFIXES[ prefix ];
	return PRETTY
		? `${ style.color }${ style.icon } ${ prefix }${ COLOR.reset }  ${ message }`
		: `[${ prefix }] ${ message }`;
}

export const log = {
	info( prefix: LoggerPrefix, message: string ): void {
		console.log( format( prefix, message ) );
	},
	debug( context: string, message: string ): void {
		if ( debugEnabled )
			console.log( format( 'debug', `${ context }: ${ message }` ) );
	},
	warn( prefix: LoggerPrefix, message: string ): void {
		console.warn( format( prefix, message ) );
	},
	error( prefix: LoggerPrefix, message: string ): void {
		console.error( format( prefix, message ) );
	},
	dim( message: string ): string {
		return `${ COLOR.dim }${ message }${ COLOR.reset }`;
	},
};
