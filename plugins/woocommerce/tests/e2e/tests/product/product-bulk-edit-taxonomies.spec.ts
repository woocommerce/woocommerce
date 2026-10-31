/**
 * External dependencies
 */
import { faker } from '@faker-js/faker';
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { test as baseTest, expect } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

interface Term {
	id: number;
	name: string;
}

interface BulkEditData {
	search: string;
	products: { id: number; name: string }[];
	terms: Record< 'shared' | 'retained' | 'mixed' | 'unassigned', Term >;
}

async function saveAndVerifyAssignments(
	page: Page,
	{ products, terms }: BulkEditData,
	expected: Term[][]
) {
	await page
		.locator( '#bulk-edit' )
		.getByRole( 'button', { name: 'Update' } )
		.click();
	await expect( page.locator( '#message' ) ).toContainText(
		'2 products updated'
	);
	await page.reload();

	for ( const [ index, product ] of products.entries() ) {
		const row = page.locator( `#post-${ product.id }` );
		await expect( row ).toBeVisible();
		for ( const term of Object.values( terms ) ) {
			await expect(
				row.getByRole( 'link', { name: term.name, exact: true } )
			).toHaveCount( expected[ index ].includes( term ) ? 1 : 0 );
		}
	}
}

for ( const { field, taxonomy } of [
	{ field: 'categories', taxonomy: 'product_cat' },
	{ field: 'brands', taxonomy: 'product_brand' },
] ) {
	const test = baseTest.extend< { bulkEditData: BulkEditData } >( {
		storageState: ADMIN_STATE_PATH,
		bulkEditData: async ( { restApi }, provide ) => {
			const search = `bulk-taxonomy-${ faker.string.uuid() }`;
			const products: BulkEditData[ 'products' ] = [];
			const terms = {} as BulkEditData[ 'terms' ];

			try {
				for ( const name of [
					'shared',
					'retained',
					'mixed',
					'unassigned',
				] as const ) {
					const { data } = await restApi.post< Term >(
						`${ WC_API_PATH }/products/${ field }`,
						{ name: `${ search }-${ name }` }
					);
					terms[ name ] = data;
				}

				for ( let i = 0; i < 2; i++ ) {
					const assignments = [ terms.shared, terms.retained ];
					if ( i === 0 ) {
						assignments.push( terms.mixed );
					}
					const { data } = await restApi.post<
						BulkEditData[ 'products' ][ number ]
					>( `${ WC_API_PATH }/products`, {
						name: `${ search } product ${ i + 1 }`,
						type: 'simple',
						regular_price: '10',
						[ field ]: assignments.map( ( { id } ) => ( { id } ) ),
					} );
					products.push( data );
				}

				await provide( { search, products, terms } );
			} finally {
				for ( const product of products ) {
					await restApi.delete(
						`${ WC_API_PATH }/products/${ product.id }`,
						{ force: true }
					);
				}
				for ( const term of Object.values( terms ) ) {
					await restApi.delete(
						`${ WC_API_PATH }/products/${ field }/${ term.id }`,
						{ force: true }
					);
				}
			}
		},
	} );

	test.describe( `Bulk edit product ${ field }`, () => {
		test.beforeEach( async ( { page, bulkEditData } ) => {
			const { search, products, terms } = bulkEditData;
			await page.goto(
				`wp-admin/edit.php?post_type=product&s=${ encodeURIComponent( search ) }`
			);
			for ( const product of products ) {
				await page
					.locator( `#post-${ product.id }` )
					.getByRole( 'checkbox' )
					.check();
			}
			await page
				.locator( '#bulk-action-selector-top' )
				.selectOption( 'edit' );
			await page.locator( '#doaction' ).click();
			await expect( page.locator( '#bulk-titles-list li' ) ).toHaveCount(
				2
			);

			const checklist = page.locator(
				`#bulk-edit .${ taxonomy }-checklist`
			);
			await expect(
				checklist.getByLabel( terms.shared.name, { exact: true } )
			).toBeChecked();
			await expect(
				checklist.getByLabel( terms.retained.name, { exact: true } )
			).toBeChecked();
			await expect(
				checklist.getByLabel( terms.unassigned.name, { exact: true } )
			).not.toBeChecked();
		} );

		test( 'can remove a shared term from both products', async ( {
			page,
			bulkEditData,
		} ) => {
			const { shared, retained, mixed } = bulkEditData.terms;
			const checkbox = page
				.locator( '#bulk-edit' )
				.getByLabel( shared.name, { exact: true } );
			await checkbox.uncheck();
			await expect( checkbox ).not.toBeChecked();
			await saveAndVerifyAssignments( page, bulkEditData, [
				[ retained, mixed ],
				[ retained ],
			] );
		} );

		test( 'preserves mixed assignments when saved without changes', async ( {
			page,
			bulkEditData,
		} ) => {
			const { shared, retained, mixed } = bulkEditData.terms;
			await expect(
				page
					.locator( '#bulk-edit' )
					.getByRole( 'checkbox', { name: mixed.name } )
			).toBeChecked( { indeterminate: true } );
			await saveAndVerifyAssignments( page, bulkEditData, [
				[ shared, retained, mixed ],
				[ shared, retained ],
			] );
		} );

		test( 'can add an unassigned term to both products', async ( {
			page,
			bulkEditData,
		} ) => {
			const { shared, retained, mixed, unassigned } = bulkEditData.terms;
			const checkbox = page
				.locator( '#bulk-edit' )
				.getByLabel( unassigned.name, { exact: true } );
			await checkbox.check();
			await expect( checkbox ).toBeChecked();
			await saveAndVerifyAssignments( page, bulkEditData, [
				[ shared, retained, mixed, unassigned ],
				[ shared, retained, unassigned ],
			] );
		} );
	} );
}
