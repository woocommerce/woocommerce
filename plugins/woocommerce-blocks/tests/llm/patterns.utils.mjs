import { parse } from 'node-html-parser';

export const parsePattern = ( html ) => {
	const parsedHTML = parse( html );
	const p = parsedHTML.getElementsByTagName( 'p' );
	const h = parsedHTML.querySelectorAll( 'h1, h2, h3, h4, h5, h6' );
	const text = h.reduce( ( acc, el ) => acc + '\n' + el.text, '' );
	return text;
};
