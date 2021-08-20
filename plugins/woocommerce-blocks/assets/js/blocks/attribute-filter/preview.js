/**
 * Internal dependencies
 */
import Label from './label';

export const previewOptions = [
	{
		value: 'preview-1',
		name: 'Blue',
		label: <Label name="Blue" count={ 3 } />,
	},
	{
		value: 'preview-2',
		name: 'Green',
		label: <Label name="Green" count={ 3 } />,
	},
	{
		value: 'preview-3',
		name: 'Red',
		label: <Label name="Red" count={ 2 } />,
	},
];

export const previewAttributeObject = {
	id: 0,
	name: 'preview',
	taxonomy: 'preview',
	label: 'Preview',
};
