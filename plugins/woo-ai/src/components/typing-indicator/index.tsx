/**
 * External dependencies
 */

import React from 'react';

/**
 * Internal dependencies
 */
import './index.scss';

const TypingIndicator = () => {
	return (
		<div className="message assistant">
			<div className="typing__dot"></div>
			<div className="typing__dot"></div>
			<div className="typing__dot"></div>
		</div>
	);
};

export default TypingIndicator;
