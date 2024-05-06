/**
 * External dependencies
 */
import { ProductTag } from '@woocommerce/data';

export type CreateTagModalProps = {
	initialTagName?: string;
	onCancel: () => void;
	onCreate: ( newTag: ProductTag ) => void;
};
export type ProductTagNodeProps = Pick< ProductTag, 'id' | 'name' >;
export type TagFieldProps = {
	id: string;
	isVisible?: boolean;
	label: string;
	placeholder: string;
	value?: ProductTagNodeProps[];
	onChange: ( value: ProductTagNodeProps[] ) => void;
};
