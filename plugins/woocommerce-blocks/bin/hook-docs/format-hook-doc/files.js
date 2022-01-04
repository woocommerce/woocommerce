const files = ( sources ) => {
	return sources && sources.length
		? {
				ul: sources.map( ( file ) => {
					return `[${ file }](../src/${ file })`;
				} ),
		  }
		: null;
};

module.exports = { files };
