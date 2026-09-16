/**
 * External dependencies
 */
import type { CSSProperties } from 'react';

export type VisualAttributeTerm = {
	type: 'color' | 'image' | 'none';
	value: string;
};

const getEscapedUrl = ( url: string ) => url.split( "'" ).join( '%27' );

export const isVisualAttributeTermEmpty = ( visual?: VisualAttributeTerm ) =>
	! visual || visual.type === 'none' || ! visual.value;

export const getVisualAttributeTermStyle = (
	visual?: VisualAttributeTerm
): CSSProperties | undefined => {
	if ( ! visual || visual.type === 'none' || ! visual.value ) {
		return undefined;
	}

	if ( visual.type === 'image' ) {
		return {
			backgroundImage: `url('${ getEscapedUrl( visual.value ) }')`,
		};
	}

	return {
		backgroundColor: visual.value,
	};
};

export const getVisualAttributeTermStyleString = (
	visual?: VisualAttributeTerm
): string => {
	if ( ! visual || visual.type === 'none' || ! visual.value ) {
		return '';
	}

	if ( visual.type === 'image' ) {
		return `background-image: url('${ getEscapedUrl( visual.value ) }');`;
	}

	return `background-color: ${ visual.value };`;
};
