/**
 * External dependencies
 */
import { spawn } from 'node:child_process';
import { createHash } from 'node:crypto';
import { existsSync } from 'node:fs';
import { copyFile, mkdir, readFile, rm } from 'node:fs/promises';
import path from 'node:path';
import chokidar from 'chokidar';

/**
 * Internal dependencies
 */
import { log } from '../utils/logger.js';

export interface ComposerPackageWatcherOptions {
	composerJson: string;
	ignoredPackages?: string[];
}

interface ComposerAutoload {
	'psr-4'?: Record< string, string | string[] >;
	'psr-0'?: Record< string, string | string[] >;
	classmap?: string[];
	files?: string[];
}

interface ComposerJson {
	name?: string;
	require?: Record< string, string >;
	'require-dev'?: Record< string, string >;
	repositories?: Array< {
		type: string;
		url?: string;
		options?: { symlink?: boolean };
	} >;
	autoload?: ComposerAutoload;
}

interface WatchedPackage {
	name: string;
	sourceDir: string;
	destDir: string;
}

const RESTART_DEBOUNCE_MS = 300;

async function readJson< T >( file: string ): Promise< T > {
	return JSON.parse( await readFile( file, 'utf8' ) ) as T;
}

function isPlatformReq( name: string ): boolean {
	return (
		name === 'php' || name.startsWith( 'ext-' ) || name.startsWith( 'lib-' )
	);
}

function execComposer( args: string[], projectDir: string ): Promise< string > {
	return new Promise( ( resolve, reject ) => {
		const child = spawn( 'composer', args, {
			cwd: projectDir,
			env: { ...process.env, XDEBUG_MODE: 'off' },
			stdio: [ 'ignore', 'pipe', 'pipe' ],
		} );
		const stdoutChunks: Buffer[] = [];
		const stderrChunks: Buffer[] = [];
		child.stdout?.on( 'data', ( chunk: Buffer ) =>
			stdoutChunks.push( chunk )
		);
		child.stderr?.on( 'data', ( chunk: Buffer ) =>
			stderrChunks.push( chunk )
		);
		child.on( 'exit', ( code ) => {
			if ( code === 0 ) {
				resolve( Buffer.concat( stdoutChunks ).toString( 'utf8' ) );
				return;
			}
			const stderr = Buffer.concat( stderrChunks ).toString( 'utf8' );
			reject(
				new Error(
					`composer ${ args.join( ' ' ) } exited ${ code }${
						stderr ? `\n${ stderr }` : ''
					}`
				)
			);
		} );
		child.on( 'error', reject );
	} );
}

interface ComposerShowEntry {
	name: string;
	path: string;
}

interface ComposerShowOutput {
	installed?: ComposerShowEntry[];
}

async function fetchPackageInstallPaths(
	projectDir: string
): Promise< Map< string, string > > {
	const stdout = await execComposer(
		[ 'show', '--path', '--format=json' ],
		projectDir
	);
	const parsed = JSON.parse( stdout ) as ComposerShowOutput;
	const paths = new Map< string, string >();
	for ( const entry of parsed.installed ?? [] ) {
		paths.set( entry.name, entry.path );
	}
	return paths;
}

async function resolvePackages(
	composerJsonPath: string,
	ignoredPackages: Set< string >,
	packageInstallPaths: Map< string, string >
): Promise< WatchedPackage[] > {
	const projectDir = path.dirname( composerJsonPath );
	const composer = await readJson< ComposerJson >( composerJsonPath );

	const wanted = new Set< string >();
	for ( const key of Object.keys( composer.require ?? {} ) ) {
		if ( ! isPlatformReq( key ) ) wanted.add( key );
	}
	for ( const key of Object.keys( composer[ 'require-dev' ] ?? {} ) ) {
		if ( ! isPlatformReq( key ) ) wanted.add( key );
	}

	const packages: WatchedPackage[] = [];

	for ( const repo of composer.repositories ?? [] ) {
		if ( repo.type !== 'path' || ! repo.url ) continue;

		const sourceDir = path.resolve( projectDir, repo.url );
		const pkgJsonPath = path.join( sourceDir, 'composer.json' );
		if ( ! existsSync( pkgJsonPath ) ) continue;

		const pkgJson = await readJson< ComposerJson >( pkgJsonPath );
		if ( ! pkgJson.name || ! wanted.has( pkgJson.name ) ) continue;

		// Composer's default for path repositories is "symlink if possible,
		// copy otherwise" — so the only safe signal that Composer will copy
		// (and therefore that we should mirror) is an explicit
		// `symlink: false`.
		//
		// Explicit `symlink: true` is treated as a configuration error: the
		// consuming project depends on the package, so an unmirrored symlink
		// breaks environments that can't resolve it (wp-env mounts only the
		// plugin dir). Throw so the failure surfaces at startup. The
		// `ignoredPackages` allowlist opts a package out if the symlink is
		// intentional for that consumer.
		//
		// Omitted `symlink` is ambiguous: Composer's behavior depends on the
		// host filesystem. Warn and skip — mirroring on top of a symlink
		// would just duplicate state.
		if ( repo.options?.symlink === true ) {
			if ( ignoredPackages.has( pkgJson.name ) ) continue;
			throw new Error(
				`path repository '${ pkgJson.name }' has symlink: true but the consuming project depends on it. ` +
					`Set 'options: { symlink: false }' so Composer copies the package, or add it to ignoredPackages if the symlink is intentional.`
			);
		}
		if ( repo.options?.symlink !== false ) {
			if ( ignoredPackages.has( pkgJson.name ) ) continue;
			log.warn(
				'warn',
				`path repository '${ pkgJson.name }' has no explicit symlink option; skipping. Set 'options: { symlink: false }' to enable mirroring, or add it to ignoredPackages to silence this warning.`
			);
			continue;
		}

		const destDir = packageInstallPaths.get( pkgJson.name );
		if ( ! destDir ) {
			throw new Error(
				`Composer reports no install path for '${ pkgJson.name }'. Run 'composer install' in ${ projectDir } first.`
			);
		}

		packages.push( {
			name: pkgJson.name,
			sourceDir,
			destDir,
		} );
	}

	return packages;
}

function packageFingerprint( pkgs: WatchedPackage[] ): string {
	return createHash( 'sha1' )
		.update(
			JSON.stringify(
				pkgs.map( ( p ) => [ p.name, p.sourceDir, p.destDir ] )
			)
		)
		.digest( 'hex' );
}

function debounce( fn: () => Promise< void >, wait: number ): () => void {
	let timer: NodeJS.Timeout | null = null;
	let pending = false;
	let running = false;

	function schedule(): void {
		if ( timer ) clearTimeout( timer );
		timer = setTimeout( () => {
			if ( running ) {
				pending = true;
				return;
			}
			running = true;
			fn()
				.catch( ( err: Error ) => log.error( 'error', err.message ) )
				.finally( () => {
					running = false;
					if ( pending ) {
						pending = false;
						schedule();
					}
				} );
		}, wait );
	}

	return schedule;
}

function runComposer( args: string[], projectDir: string ): Promise< void > {
	return new Promise( ( resolve, reject ) => {
		const child = spawn( 'composer', args, {
			cwd: projectDir,
			env: { ...process.env, XDEBUG_MODE: 'off' },
			stdio: 'inherit',
		} );
		child.on( 'exit', ( code ) =>
			code === 0
				? resolve()
				: reject(
						new Error(
							`composer ${ args.join( ' ' ) } exited ${ code }`
						)
				  )
		);
		child.on( 'error', reject );
	} );
}

async function mirror( srcAbs: string, pkg: WatchedPackage ): Promise< void > {
	const rel = path.relative( pkg.sourceDir, srcAbs );
	const dest = path.join( pkg.destDir, rel );
	await mkdir( path.dirname( dest ), { recursive: true } );
	await copyFile( srcAbs, dest );
}

async function removeMirror(
	srcAbs: string,
	pkg: WatchedPackage
): Promise< void > {
	const rel = path.relative( pkg.sourceDir, srcAbs );
	const dest = path.join( pkg.destDir, rel );
	await rm( dest, { force: true } );
}

export async function watchComposerPackages(
	options: ComposerPackageWatcherOptions
): Promise< void > {
	const composerJsonPath = path.resolve( options.composerJson );
	const projectDir = path.dirname( composerJsonPath );
	const ignoredPackages = new Set( options.ignoredPackages ?? [] );

	const startWatching = async (): Promise< void > => {
		const packageInstallPaths =
			await fetchPackageInstallPaths( projectDir );
		const packages = await resolvePackages(
			composerJsonPath,
			ignoredPackages,
			packageInstallPaths
		);
		if ( packages.length === 0 ) {
			log.info(
				'watch',
				'no copy-mode path-repository packages to watch'
			);
			return;
		}

		const fingerprint = packageFingerprint( packages );
		log.info(
			'watch',
			`watching ${ packages.length } package(s): ${ packages
				.map( ( p ) => p.name )
				.join( ', ' ) }`
		);

		const findPackageFor = (
			absPath: string
		): WatchedPackage | undefined =>
			packages.find(
				( pkg ) =>
					absPath === pkg.sourceDir ||
					absPath.startsWith( pkg.sourceDir + path.sep )
			);

		const dumpAutoload = debounce( async () => {
			log.info( 'watch', 'composer dump-autoload' );
			await runComposer(
				[ 'dump-autoload', '--quiet', '--no-scripts' ],
				projectDir
			);
		}, RESTART_DEBOUNCE_MS );

		// Watch each package's source directory whole — Composer copies the
		// directory verbatim on install, so the watcher mirrors any file event
		// to match. Plus the consuming project's own composer.json, so a new
		// path-repo declaration triggers a re-install + restart.
		const allPaths = [
			composerJsonPath,
			...packages.map( ( p ) => p.sourceDir ),
		];

		const watcher = chokidar.watch( allPaths, {
			ignoreInitial: true,
			// Avoid watching directories that won't be used for source files.
			ignored: ( p: string ) =>
				/(\/|^)(node_modules|\.git|vendor|changelog)(\/|$)/.test( p ),
			awaitWriteFinish: { stabilityThreshold: 50, pollInterval: 20 },
		} );

		let composerInstallRunning = false;
		const onComposerJsonChange = async (): Promise< void > => {
			if ( composerInstallRunning ) return;
			composerInstallRunning = true;
			log.info( 'watch', 'composer.json changed; composer install' );
			try {
				await runComposer( [ 'install', '--quiet' ], projectDir );
			} catch ( err ) {
				log.error( 'error', ( err as Error ).message );
				composerInstallRunning = false;
				return;
			}
			composerInstallRunning = false;
			const projectInstallPaths =
				await fetchPackageInstallPaths( projectDir );
			const next = await resolvePackages(
				composerJsonPath,
				ignoredPackages,
				projectInstallPaths
			);
			if ( packageFingerprint( next ) !== fingerprint ) {
				log.info( 'watch', 'package set changed; restarting watcher' );
				await watcher.close();
				startWatching().catch( ( err: Error ) => {
					log.error( 'error', `restart failed: ${ err.message }` );
					process.exit( 1 );
				} );
			}
		};

		const handle = async (
			event: 'add' | 'change' | 'unlink',
			absPath: string
		): Promise< void > => {
			// Consuming project's composer.json is its own path; not in any pkg.
			if ( absPath === composerJsonPath ) {
				if ( event === 'change' || event === 'add' )
					void onComposerJsonChange();
				return;
			}

			const pkg = findPackageFor( absPath );
			if ( ! pkg ) return;

			// A watched package's own composer.json changing means its autoload
			// spec may have changed. Mirror it and treat as a project-level
			// composer.json change (re-install + maybe restart).
			if (
				path.basename( absPath ) === 'composer.json' &&
				absPath === path.join( pkg.sourceDir, 'composer.json' )
			) {
				await mirror( absPath, pkg ).catch( () => undefined );
				void onComposerJsonChange();
				return;
			}

			try {
				if ( event === 'unlink' ) {
					await removeMirror( absPath, pkg );
					log.info(
						'watch',
						`unlink ${ pkg.name }/${ path.relative(
							pkg.sourceDir,
							absPath
						) }`
					);
				} else {
					await mirror( absPath, pkg );
					log.info(
						'watch',
						`${ event } ${ pkg.name }/${ path.relative(
							pkg.sourceDir,
							absPath
						) }`
					);
				}
			} catch ( err ) {
				log.error(
					'error',
					`mirror failed for ${ absPath }: ${
						( err as Error ).message
					}`
				);
				return;
			}

			// Keep the autoloader up-to-date when the files available change.
			if ( event === 'add' || event === 'unlink' ) {
				dumpAutoload();
			}
		};

		watcher
			.on( 'add', ( p: string ) => void handle( 'add', p ) )
			.on( 'change', ( p: string ) => void handle( 'change', p ) )
			.on( 'unlink', ( p: string ) => void handle( 'unlink', p ) )
			.on( 'error', ( err: unknown ) =>
				log.error( 'error', ( err as Error ).message )
			);
	};

	await startWatching();
}
