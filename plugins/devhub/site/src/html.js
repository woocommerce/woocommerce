import React from 'react';
import PropTypes from 'prop-types';

export default function HTML( props ) {
	return (
		<div>
			{ props.preBodyComponents }
			<div
				key={ `body` }
				id="___gatsby"
				dangerouslySetInnerHTML={ { __html: props.body } }
			/>
			{ props.postBodyComponents }
		</div>
	);
}

HTML.propTypes = {
	htmlAttributes: PropTypes.object,
	headComponents: PropTypes.array,
	bodyAttributes: PropTypes.object,
	preBodyComponents: PropTypes.array,
	body: PropTypes.string,
	postBodyComponents: PropTypes.array,
};
