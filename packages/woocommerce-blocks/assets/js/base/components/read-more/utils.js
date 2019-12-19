/**
 * External dependencies
 */
import trimHtml from 'trim-html';

/**
 * Truncate some HTML content to a given length.
 *
 * @param {string} html HTML that will be truncated.
 * @param {int} length Legth to truncate the string to.
 * @param {string} ellipsis Character to append to truncated content.
 */
export const truncateHtml = ( html, length, ellipsis = '...' ) => {
	const trimmed = trimHtml( html, {
		suffix: ellipsis,
		limit: length,
	} );

	return trimmed.html;
};

/**
 * Clamp lines calculates the height of a line of text and then limits it to the
 * value of the lines prop. Content is updated once limited.
 *
 * @param {string} originalContent Content to be clamped.
 * @param {Object} targetElement Element which will contain the clamped content.
 * @param {integer} maxHeight Max height of the clamped content.
 * @param {string} ellipsis Character to append to clamped content.
 * @return {string} clamped content
 */
export const clampLines = (
	originalContent,
	targetElement,
	maxHeight,
	ellipsis
) => {
	const length = calculateLength( originalContent, targetElement, maxHeight );

	return truncateHtml( originalContent, length - ellipsis.length, ellipsis );
};

/**
 * Calculate how long the content can be based on the maximum number of lines allowed, and client height.
 *
 * @param {string} originalContent Content to be clamped.
 * @param {Object} targetElement Element which will contain the clamped content.
 * @param {integer} maxHeight Max height of the clamped content.
 */
const calculateLength = ( originalContent, targetElement, maxHeight ) => {
	let markers = {
		start: 0,
		middle: 0,
		end: originalContent.length,
	};

	while ( markers.start <= markers.end ) {
		markers.middle = Math.floor( ( markers.start + markers.end ) / 2 );

		// We set the innerHTML directly in the DOM here so we can reliably check the clientHeight later in moveMarkers.
		targetElement.innerHTML = truncateHtml(
			originalContent,
			markers.middle
		);

		markers = moveMarkers( markers, targetElement.clientHeight, maxHeight );
	}

	return markers.middle;
};

/**
 * Move string markers. Used by calculateLength.
 *
 * @param {Object} markers Markers for clamped content.
 * @param {integer} currentHeight Current height of clamped content.
 * @param {integer} maxHeight Max height of the clamped content.
 */
const moveMarkers = ( markers, currentHeight, maxHeight ) => {
	if ( currentHeight <= maxHeight ) {
		markers.start = markers.middle + 1;
	} else {
		markers.end = markers.middle - 1;
	}

	return markers;
};
