const filterContextBlocks = ( contextBlocks, pattern ) => {
	return contextBlocks.filter( ( { elements } ) =>
		elements[ 0 ].text.includes( pattern )
	);
};

module.exports = { filterContextBlocks };
