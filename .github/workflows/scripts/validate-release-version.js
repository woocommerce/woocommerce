module.exports = async ( { github, context, core } ) => {
	const { RELEASE_VERSION, GITHUB_EVENT_NAME } = process.env;

	async function findRelease() {
		const { owner, repo } = context.repo;
		const list = await github.rest.repos.listReleases( {
			owner,
			repo,
			per_page: 100,
		} );
		const match = list.data.find( ( { tag_name, name } ) =>
			[ tag_name, name ].includes( RELEASE_VERSION )
		);

		return match;
	}

	async function handleWorkflowDispatch() {
		const match = await findRelease();

		if ( match ) {
			return match;
		}

		throw new Error(
			`"${ RELEASE_VERSION }" is not a valid release version!`
		);
	}

	function findWooCommerceZipAsset() {
		const match = release.assets.find(
			( { name } ) => name === 'woocommerce.zip'
		);
		if ( ! match ) {
			throw new Error(
				`Release ${ RELEASE_VERSION } does not contain a woocommerce.zip asset!`
			);
		}

		return match;
	}

	const release =
		GITHUB_EVENT_NAME === 'release'
			? await findRelease()
			: await handleWorkflowDispatch();
	const asset = findWooCommerceZipAsset();
	core.setOutput( 'version', RELEASE_VERSION );
	core.setOutput( 'created', release.created_at );
	core.setOutput( 'asset-id', asset.id );
};
