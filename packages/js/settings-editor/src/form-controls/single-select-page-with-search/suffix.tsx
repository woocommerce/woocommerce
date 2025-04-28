/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Spinner, Button } from '@wordpress/components';
import { close } from '@wordpress/icons';

type SuffixProps = {
	value: string;
	isLoading: boolean;
	isFocused: boolean;
	onRemove: () => void;
};

export const Suffix = ( {
	isLoading,
	isFocused,
	value,
	onRemove,
}: SuffixProps ) => {
	if ( isLoading ) {
		return <Spinner />;
	}

	if ( ! isFocused && value ) {
		return (
			<Button
				icon={ close }
				onClick={ onRemove }
				iconSize={ 16 }
				size="compact"
			/>
		);
	}

	return null;
};
