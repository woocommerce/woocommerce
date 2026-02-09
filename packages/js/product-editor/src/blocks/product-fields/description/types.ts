/**
 * Internal dependencies
 */
import {
	ProductEditorBlockAttributes,
	ProductEditorBlockEditProps,
} from '../../../types';

export type DescriptionBlockEditProps =
	ProductEditorBlockEditProps< ProductEditorBlockAttributes >;

export type DescriptionBlockEditComponent =
	React.ComponentType< DescriptionBlockEditProps > & {
		attributes: Record< string, unknown >;
	};
