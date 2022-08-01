const { exec, spawn } = require( 'child_process' );

const args = process.argv.slice( 2 );

const command = args[ 0 ];
const turboFlags = args.filter( ( arg ) => arg.includes( 'turbo-' ) );
const otherFlags = args
	.slice( 1 )
	.filter( ( arg ) => ! arg.includes( 'turbo-' ) );

spawn(
	'pnpm',
	[
		'-w',
		'exec',
		'--',
		'turbo',
		'run',
		command,
		...turboFlags.map( ( f ) => f.replace( 'turbo-', '' ) ),
		'--',
		'--',
		otherFlags,
	],
	{
		stdio: [ process.stdin, process.stdout, 'pipe' ],
	}
);
