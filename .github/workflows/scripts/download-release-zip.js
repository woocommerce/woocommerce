module.exports = async ( { github, context, core } ) => {
	const { ASSET_ID: asset_id } = process.env;
	const { owner, repo } = context.repo;
	const fs = require( 'fs' );
	const path = require( 'path' );

	const response = await github.rest.repos.getReleaseAsset( {
		owner,
		repo,
		asset_id,
		headers: { accept: 'application/octet-stream' },
	} );

	const zipPath = path.resolve( 'tmp', 'woocommerce.zip' );
	fs.mkdirSync( 'tmp' );
	fs.writeFileSync( zipPath, Buffer.from( response.data ) );

	core.setOutput( 'zip-path', zipPath );
};
