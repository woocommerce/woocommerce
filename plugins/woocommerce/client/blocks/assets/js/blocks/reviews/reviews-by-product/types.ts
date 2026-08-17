/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

interface ReviewByProductAttributes {
	editMode: boolean;
	offset: number;
	productId: number;
}

export interface ReviewsByProductEditorProps
	extends BlockEditProps< ReviewByProductAttributes > {
	attributes: ReviewByProductAttributes;
	debouncedSpeak: ( message: string ) => void;
}
