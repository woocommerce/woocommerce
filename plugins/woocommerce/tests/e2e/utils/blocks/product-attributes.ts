/**
 * Internal dependencies
 */
import { wpCLI } from './wp-cli';

/**
 * Read the ids of the sample-data product attributes.
 *
 * The ids are assigned when the sample data is imported and differ between
 * environments, so a spec that names an attribute in a template or a block
 * attribute has to look them up rather than hard-code 1 and 2.
 */
export const getProductAttributeIds = async (): Promise< {
	colorAttributeId: number;
	sizeAttributeId: number;
} > => {
	const { stdout } = await wpCLI(
		'wc product_attribute list --format=json --user=1'
	);
	const firstBracket = stdout.indexOf( '[' );
	const lastBracket = stdout.lastIndexOf( ']' );

	if ( firstBracket < 0 || lastBracket <= firstBracket ) {
		throw new Error( 'Product attribute CLI output did not contain JSON.' );
	}

	const attributes = JSON.parse(
		stdout.slice( firstBracket, lastBracket + 1 )
	) as Array< { id: number | string; slug: string } >;

	const getAttributeId = ( slug: string ): number => {
		const matchingAttributes = attributes.filter(
			( attribute ) => attribute.slug === slug
		);

		if ( matchingAttributes.length !== 1 ) {
			throw new Error(
				`Expected exactly one "${ slug }" product attribute, found ${ matchingAttributes.length }.`
			);
		}

		const attributeId = Number( matchingAttributes[ 0 ].id );

		if ( ! Number.isSafeInteger( attributeId ) || attributeId < 1 ) {
			throw new Error(
				`The "${ slug }" product attribute has an unusable id: ${ matchingAttributes[ 0 ].id }.`
			);
		}

		return attributeId;
	};

	return {
		colorAttributeId: getAttributeId( 'pa_color' ),
		sizeAttributeId: getAttributeId( 'pa_size' ),
	};
};
