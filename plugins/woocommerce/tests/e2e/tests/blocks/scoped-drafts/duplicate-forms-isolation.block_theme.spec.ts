/**
 * External dependencies
 */
import {
	test as base,
	expect,
	getPostIdBySlug,
	wpCLI,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { cartLineRows, readCartLineQuantities } from '../cart-store/utils';
import ScopedDraftsPage from './scoped-drafts.page';

const test = base.extend< { scopedDraftsPage: ScopedDraftsPage } >( {
	scopedDraftsPage: async ( { page, admin, editor }, use ) => {
		await use( new ScopedDraftsPage( { page, admin, editor } ) );
	},
} );

test.describe( 'Scoped drafts: duplicate forms stay isolated', () => {
	test( 'two grouped products sharing a child keep independent drafts', async ( {
		page,
		scopedDraftsPage,
		frontendUtils,
	} ) => {
		// The fixtures ship exactly one grouped product ("Logo Collection",
		// children Hoodie with Logo / T-Shirt / Beanie), so a second grouped
		// product sharing one of its children ("Beanie") is created here.
		const beanieId = await getPostIdBySlug( 'beanie' );
		const albumId = await getPostIdBySlug( 'album' );
		await wpCLI(
			`wc product create --user=1 --slug="second-collection" --name="Second Collection" --type="grouped" --grouped_products='[${ beanieId },${ albumId }]'`
		);

		await scopedDraftsPage.createPostWithSingleProductBlocks( [
			{ product: 'logo-collection' },
			{
				product: 'second-collection',
				searchTerm: 'Second Collection',
			},
		] );

		const logoCollectionForm = scopedDraftsPage.singleProductBlockAt( 0 );
		const secondCollectionForm = scopedDraftsPage.singleProductBlockAt( 1 );

		// Beanie is a child of both grouped products, so its quantity
		// spinbutton is scoped by its row rather than by accessible name —
		// the +/- buttons stay unambiguous by name within each form, but two
		// same-named child rows on one page is exactly the scenario this
		// test drives.
		const secondCollectionBeanieQuantity = secondCollectionForm
			.getByRole( 'listitem' )
			.filter( { hasText: 'Beanie' } )
			.getByRole( 'spinbutton' );

		// Second Collection's own Beanie input starts untouched.
		await expect( secondCollectionBeanieQuantity ).toHaveValue( '0' );

		await test.step( "editing Logo Collection's Beanie leaves Second Collection's Beanie input unchanged", async () => {
			await logoCollectionForm
				.getByLabel( 'Increase quantity of Beanie' )
				.click();
			await logoCollectionForm
				.getByLabel( 'Increase quantity of Beanie' )
				.click();
			await logoCollectionForm
				.getByLabel( 'Increase quantity of T-Shirt' )
				.click();

			await expect( secondCollectionBeanieQuantity ).toHaveValue( '0' );
		} );

		await test.step( 'adding each form yields that form’s own lines', async () => {
			const logoCollectionAddToCartButton = logoCollectionForm.getByRole(
				'button',
				{ name: 'Add to cart' }
			);
			await expect( logoCollectionAddToCartButton ).not.toHaveClass(
				/\bdisabled\b/
			);

			// Adds are optimistic: the UI reacts before the batched request
			// lands, so navigating too early can abort the in-flight
			// mutation. Arm the response wait before each click and hold
			// the next action until the server has the line.
			const logoCollectionBatchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await logoCollectionAddToCartButton.click();
			await logoCollectionBatchPromise;

			await secondCollectionForm
				.getByLabel( 'Increase quantity of Beanie' )
				.click();
			await secondCollectionForm
				.getByLabel( 'Increase quantity of Beanie' )
				.click();
			await secondCollectionForm
				.getByLabel( 'Increase quantity of Beanie' )
				.click();
			await secondCollectionForm
				.getByLabel( 'Increase quantity of Album' )
				.click();

			const secondCollectionAddToCartButton =
				secondCollectionForm.getByRole( 'button', {
					name: 'Add to cart',
				} );
			await expect( secondCollectionAddToCartButton ).not.toHaveClass(
				/\bdisabled\b/
			);

			const secondCollectionBatchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await secondCollectionAddToCartButton.click();
			await secondCollectionBatchPromise;

			await frontendUtils.goToCart();
			// Beanie is one product, shared by both forms: their
			// contributions add up on a single cart line — 2 (Logo
			// Collection) + 3 (Second Collection) = 5.
			await expect(
				readCartLineQuantities( page, 'Beanie' )
			).resolves.toEqual( [ 5 ] );
			// T-Shirt only came from Logo Collection's form.
			await expect(
				readCartLineQuantities( page, 'T-Shirt' )
			).resolves.toEqual( [ 1 ] );
			// Album only came from Second Collection's form.
			await expect(
				readCartLineQuantities( page, 'Album' )
			).resolves.toEqual( [ 1 ] );
			// Hoodie with Logo, a Logo Collection child never selected in
			// either form, was never added.
			await expect(
				cartLineRows( page, 'Hoodie with Logo' )
			).toHaveCount( 0 );
		} );
	} );

	test( 'two variations of the same parent land as independent cart lines', async ( {
		page,
		scopedDraftsPage,
		frontendUtils,
	} ) => {
		await scopedDraftsPage.createPostWithSingleProductBlocks( [
			{ product: 'hoodie' },
			{ product: 'hoodie' },
		] );

		const blueForm = scopedDraftsPage.singleProductBlockAt( 0 );
		const greenForm = scopedDraftsPage.singleProductBlockAt( 1 );

		await test.step( "configuring the first form's attributes leaves the second form's selection untouched", async () => {
			await blueForm
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { name: 'Blue', exact: true } )
				.click();
			await blueForm
				.getByRole( 'radiogroup', { name: 'Logo' } )
				.getByRole( 'radio', { name: 'No', exact: true } )
				.click();

			await expect(
				greenForm
					.getByRole( 'radiogroup', { name: 'Color' } )
					.getByRole( 'radio', { name: 'Blue', exact: true } )
			).not.toBeChecked();
			await expect(
				greenForm
					.getByRole( 'radiogroup', { name: 'Color' } )
					.getByRole( 'radio', { name: 'Green', exact: true } )
			).not.toBeChecked();
		} );

		await test.step( "changing the first form's quantity leaves the second form's quantity untouched", async () => {
			const blueQuantity = blueForm.getByLabel( 'Product quantity' );
			await blueQuantity.fill( '2' );
			await blueQuantity.blur();

			await expect(
				greenForm.getByLabel( 'Product quantity' )
			).toHaveValue( '1' );
		} );

		await test.step( 'each variation adds as its own independent cart line, with the correct attributes', async () => {
			// Adds are optimistic: hold the next action until each batched
			// mutation lands so a later navigation cannot abort it. Arm the
			// response wait before each click.
			const blueBatchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await blueForm
				.getByRole( 'button', { name: 'Add to cart' } )
				.click();
			await blueBatchPromise;

			await greenForm
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { name: 'Green', exact: true } )
				.click();
			await greenForm
				.getByRole( 'radiogroup', { name: 'Logo' } )
				.getByRole( 'radio', { name: 'No', exact: true } )
				.click();
			const greenQuantity = greenForm.getByLabel( 'Product quantity' );
			await greenQuantity.fill( '3' );
			await greenQuantity.blur();

			const greenBatchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await greenForm
				.getByRole( 'button', { name: 'Add to cart' } )
				.click();
			await greenBatchPromise;

			await frontendUtils.goToCart();
			// Two independent lines for the same parent product, sorted by
			// quantity so the assertion doesn't depend on row order.
			await expect(
				readCartLineQuantities( page, 'Hoodie' )
			).resolves.toEqual( [ 2, 3 ] );

			// Each line carries its own variation's attributes.
			const blueRow = cartLineRows( page, 'Hoodie' ).filter( {
				hasText: 'Blue',
			} );
			const greenRow = cartLineRows( page, 'Hoodie' ).filter( {
				hasText: 'Green',
			} );
			await expect( blueRow ).toHaveCount( 1 );
			await expect( greenRow ).toHaveCount( 1 );
			await expect(
				blueRow.getByLabel( 'Quantity of Hoodie in your cart.' )
			).toHaveValue( '2' );
			await expect(
				greenRow.getByLabel( 'Quantity of Hoodie in your cart.' )
			).toHaveValue( '3' );
		} );
	} );
} );
