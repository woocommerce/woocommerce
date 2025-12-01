const contentWithHeading = ( content, heading, headingLevel = 'h3' ) => {
	return content && content.length
		? [ { [ headingLevel ]: heading }, { html: content } ]
		: [];
};

module.exports = { contentWithHeading };
