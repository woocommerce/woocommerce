/**
 * External dependencies
 */
import { useEffect, useState } from 'react';

export const withEditingImage = ( Component ) => ( props ) => {
	const [ isEditingImage, setIsEditingImage ] = useState( false );
	const { isSelected } = props;

	useEffect( () => {
		setIsEditingImage( false );
	}, [ isSelected ] );

	return (
		<Component
			{ ...props }
			useEditingImage={ [ isEditingImage, setIsEditingImage ] }
		/>
	);
};
