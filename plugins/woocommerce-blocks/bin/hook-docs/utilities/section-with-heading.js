const sectionWithHeading = ( content, heading, headingLevel = 'h3' ) => {
	return content ? [ { [ headingLevel ]: heading }, content ] : [];
};

module.exports = { sectionWithHeading };
