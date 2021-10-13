const generateToc = ( hooks ) => {
	const usedHeaders = [];

	return [
		{
			ul: hooks.map( ( hook ) => {
				const hookName = hook.name;
				const tags = hook.doc.tags || [];
				const isDeprecated = tags.find(
					( { name: tagName } ) => tagName === 'deprecated'
				);
				const heading = isDeprecated
					? `~~${ hookName }~~`
					: `${ hookName }`;

				let anchor = heading
					.trim()
					.toLowerCase()
					.replace( /[^\w\- ]+/g, ' ' )
					.replace( /\s+/g, '-' )
					.replace( /\-+$/, '' );
				if ( usedHeaders.indexOf( anchor ) !== -1 ) {
					let i = 1;
					while (
						usedHeaders.indexOf( anchor + '-' + i ) !== -1 &&
						i++ <= 10
					);
					anchor = anchor + '-' + i;
				}
				usedHeaders.push( anchor );

				return `[${ hook.name }](#${ anchor })`;
			} ),
		},
	];
};

module.exports = { generateToc };
