import fs from 'node:fs';
import { execFileSync } from 'node:child_process';

async function json( url ) {
	const response = await fetch( url );
	if ( ! response.ok ) {
		throw new Error( `${ response.status }: ${ url }` );
	}
	return response.json();
}

async function main() {
	const versions = await json(
		'https://api.wordpress.org/core/version-check/1.7/'
	);
	const latest = versions.offers.find(
		( offer ) => offer.response === 'upgrade'
	);
	if ( ! latest || ! /^\d+\.\d+(\.\d+)?$/.test( latest.version ) ) {
		throw new Error( 'Could not resolve a stable WordPress version.' );
	}
	const themes = await Promise.all(
		[ 'storefront', 'twentytwentytwo' ].map( async ( slug ) => {
			const theme = await json(
				`https://api.wordpress.org/themes/info/1.2/?action=theme_information&request[slug]=${ slug }`
			);
			if ( ! theme.version || ! /^[\d.]+$/.test( theme.version ) ) {
				throw new Error( `Could not resolve ${ slug }` );
			}
			return `https://downloads.wordpress.org/theme/${ slug }.${ theme.version }.zip`;
		} )
	);
	const images = {};
	for ( const name of [
		'wordpress:php8.5',
		'wordpress:cli-php8.5',
		'mariadb:lts',
		'phpmyadmin',
		'moby/buildkit:buildx-stable-1',
	] ) {
		const descriptor = JSON.parse(
			execFileSync(
				'docker',
				[
					'buildx',
					'imagetools',
					'inspect',
					name,
					'--format',
					'{{json .Manifest}}',
				],
				{ encoding: 'utf8' }
			)
		);
		if ( ! /^sha256:[a-f0-9]{64}$/.test( descriptor.digest ) ) {
			throw new Error( `Could not resolve digest for ${ name }` );
		}
		images[ name ] = `${ name }@${ descriptor.digest }`;
	}
	fs.writeFileSync(
		process.argv[ 2 ],
		JSON.stringify(
			{
				wordpress: `https://wordpress.org/wordpress-${ latest.version }.zip`,
				themes,
				images,
			},
			null,
			2
		)
	);
}
main().catch( ( error ) => {
	process.stderr.write( `${ error.stack }\n` );
	process.exitCode = 1;
} );
