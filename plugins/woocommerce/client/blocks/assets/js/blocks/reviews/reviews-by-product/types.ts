/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

interface ReviewByProductAttributes {
	editMode: boolean;
	productId: number;
}

export interface ReviewsByProductEditorProps
	extends BlockEditProps< ReviewByProductAttributes > {
	attributes: ReviewByProductAttributes;
	debouncedSpeak: ( message: string ) => void;
}
