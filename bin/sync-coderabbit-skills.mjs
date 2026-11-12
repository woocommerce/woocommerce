/**
 * Copies the agent skills that pnpm links from dependencies (see `skills.dirs` in
 * `pnpm-workspace.yaml`) into `.github/coderabbit/skills`, so CodeRabbit can read them from git.
 *
 * pnpm links skills as symlinks into `node_modules`, which are not in the repository.
 * Run after `pnpm install`: `pnpm sync:coderabbit-skills`.
 */
import { cpSync, existsSync, lstatSync, readdirSync, rmSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const source = join( root, '.ai', 'skills' );
const destination = join( root, '.github', 'coderabbit', 'skills' );

// Skills authored in this repo are real directories; the ones pnpm links are symlinks.
const linkedSkills = existsSync( source )
	? readdirSync( source ).filter( ( name ) =>
			lstatSync( join( source, name ) ).isSymbolicLink()
	  )
	: [];

if ( ! linkedSkills.length ) {
	console.log( 'No linked skills found in .ai/skills. Run `pnpm install` first.' );
	process.exit( 0 );
}

rmSync( destination, { recursive: true, force: true } );
for ( const name of linkedSkills ) {
	cpSync( join( source, name ), join( destination, name ), {
		recursive: true,
		dereference: true,
	} );
}

console.log(
	`Copied ${ linkedSkills.length } skills to .github/coderabbit/skills: ${ linkedSkills.join( ', ' ) }`
);
