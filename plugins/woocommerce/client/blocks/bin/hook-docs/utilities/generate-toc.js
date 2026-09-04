const generateToc = ( hooks ) => {
	const usedHeaders = [];

	return [
		{
			ul: hooks.map( ( hook ) => {
				const hookName = hook.name;
				const hookDocs = hook.doc || {};
				const tags = hookDocs.tags || [];
				const isDeprecated = tags.find(
					( { name: tagName } ) => tagName === 'deprecated'
				);
				const heading = isDeprecated
					? `~~${ hookName }~~`
					: `${ hookName }`;
				let anchor = heading
					.trim()
					.toLowerCase()
					.replace( /\(\)/g, '' )
					.replace( /\{\$(.*?)->(.*?)}/g, '$1-$2' )
					.replace( /\{\$(.*?)}/g, '$1' )
					.replace( /[^\w\- ]+/g, ' ' )
					.trim()
					.replace( /\s+/g, '-' )
					.replace( /\-+$/, '' );
				if ( usedHeaders.indexOf( anchor ) !== -1 ) {
					let i = 1;
					while ( usedHeaders.indexOf( anchor + '-' + i ) !== -1 ) {
						i++;
					}
					anchor = anchor + '-' + i;
				}
				usedHeaders.push( anchor );
				// Match generate-hook-name: dynamic names render as code spans.
				const displayName = hookName.includes( '{$' )
					? `\`${ hookName }\``
					: hookName;
				return `[${ displayName }](#${ anchor })`;
			} ),
		},
	];
};

module.exports = { generateToc };
