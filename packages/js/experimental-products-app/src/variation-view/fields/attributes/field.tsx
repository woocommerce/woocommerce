/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

type VariationAttribute = { id: number; name: string; option: string };
type ParentAttribute = {
	id: number;
	name: string;
	options: string[];
	variation?: boolean;
};

// Match by ID for taxonomy attributes (id > 0), by name for local attributes (id === 0).
function findVariationAttr(
	variationAttrs: VariationAttribute[],
	parentAttr: ParentAttribute
): VariationAttribute | undefined {
	if ( parentAttr.id !== 0 ) {
		return variationAttrs.find( ( va ) => va.id === parentAttr.id );
	}
	return variationAttrs.find(
		( va ) => va.name.toLowerCase() === parentAttr.name.toLowerCase()
	);
}

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	label: __( 'Attributes', 'woocommerce' ),
	enableSorting: false,
	filterBy: false,
	isVisible: ( item ) => !! item.parent_id,
	getValue: ( { item } ) => item.attributes,
	Edit: ( { data, onChange } ) => {
		const parentProduct = useSelect(
			( select ) => {
				if ( ! data.parent_id ) {
					return null;
				}
				return select( coreStore ).getEditedEntityRecord(
					'root',
					'product',
					data.parent_id
				) as ProductEntityRecord | false | undefined;
			},
			[ data.parent_id ]
		);

		const allParentAttributes = parentProduct
			? ( ( parentProduct.attributes ??
					[] ) as unknown as ParentAttribute[] )
			: [];

		// Only show attributes marked as variation attributes.
		const parentAttributes = allParentAttributes.filter(
			( attr ) => attr.variation !== false
		);

		const variationAttributes = ( data.attributes ??
			[] ) as unknown as VariationAttribute[];

		if ( parentAttributes.length === 0 ) {
			return null;
		}

		const handleChange = (
			attrId: number,
			attrName: string,
			newOption: string
		) => {
			const updated = variationAttributes.map( ( attr ) => {
				const matches =
					attrId !== 0
						? attr.id === attrId
						: attr.name.toLowerCase() === attrName.toLowerCase();
				return matches ? { ...attr, option: newOption } : attr;
			} );
			onChange( {
				attributes:
					updated as unknown as ProductEntityRecord[ 'attributes' ],
			} );
		};

		const isOdd = parentAttributes.length % 2 !== 0;

		return (
			<div
				style={ {
					display: 'grid',
					gridTemplateColumns: '1fr 1fr',
					gap: '16px',
				} }
			>
				{ parentAttributes.map( ( attr, index ) => {
					const selected = findVariationAttr(
						variationAttributes,
						attr
					);
					const isLastOdd =
						isOdd && index === parentAttributes.length - 1;
					const options = attr.options.map( ( opt ) => ( {
						label: opt,
						value: opt,
					} ) );

					return (
						<div
							key={ `${ attr.id }-${ attr.name }` }
							style={
								isLastOdd ? { gridColumn: '1 / -1' } : undefined
							}
						>
							<SelectControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								label={ attr.name }
								value={ selected?.option ?? '' }
								options={ options }
								onChange={ ( value ) =>
									handleChange( attr.id, attr.name, value )
								}
							/>
						</div>
					);
				} ) }
			</div>
		);
	},
};
