/**
 * Internal dependencies
 */
import type {
	ProductEntityAttribute,
	ProductEntityDefaultAttribute,
	ProductEntityRecord,
} from '../../fields/types';

export type VariationAttributeRow = {
	attributeId: number;
	defaultValue: string;
	id: string;
	isGlobal: boolean;
	isVisible: boolean;
	name: string;
	position: number;
	slug: string;
	values: string[];
};

type AttributeFilter = 'product' | 'variation';

function getAttributeDefaultValue(
	attribute: ProductEntityAttribute,
	defaultAttributes: ProductEntityDefaultAttribute[]
): string {
	const matchingDefault = defaultAttributes.find( ( defaultAttribute ) => {
		return (
			( attribute.id > 0 && defaultAttribute.id === attribute.id ) ||
			defaultAttribute.name === attribute.name ||
			defaultAttribute.name === attribute.slug
		);
	} );

	return matchingDefault?.option ?? '';
}

function getAttributeRows(
	product:
		| Pick< ProductEntityRecord, 'attributes' | 'default_attributes' >
		| undefined,
	filter: AttributeFilter
): VariationAttributeRow[] {
	if ( ! product?.attributes ) {
		return [];
	}

	const defaultAttributes = product.default_attributes ?? [];
	const isVariationAttribute = filter === 'variation';

	return product.attributes
		.map( ( attribute, index ) => ( {
			attribute,
			index,
		} ) )
		.filter(
			( { attribute } ) =>
				Boolean( attribute.variation ) === isVariationAttribute
		)
		.sort( ( first, second ) => {
			return (
				first.attribute.position - second.attribute.position ||
				first.index - second.index
			);
		} )
		.map( ( { attribute } ) => ( {
			attributeId: attribute.id,
			defaultValue: getAttributeDefaultValue(
				attribute,
				defaultAttributes
			),
			id: attribute.slug,
			isGlobal: attribute.id > 0,
			isVisible: Boolean( attribute.visible ),
			name: attribute.name,
			position: attribute.position,
			slug: attribute.slug,
			values: attribute.options ?? [],
		} ) );
}

export function getProductAttributeRows(
	product?: Pick< ProductEntityRecord, 'attributes' | 'default_attributes' >
): VariationAttributeRow[] {
	return getAttributeRows( product, 'product' );
}

export function getVariationAttributeRows(
	product?: Pick< ProductEntityRecord, 'attributes' | 'default_attributes' >
): VariationAttributeRow[] {
	return getAttributeRows( product, 'variation' );
}
