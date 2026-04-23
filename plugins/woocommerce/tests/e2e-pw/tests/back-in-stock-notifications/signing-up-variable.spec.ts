/**
 * Internal dependencies
 */
import {
	expect,
	request,
	tags,
	test as baseTest,
} from '../../fixtures/fixtures';
import {
	BIS_OPTIONS,
	createOutOfStockVariableProduct,
	setBISOptions,
	signUpOnProductPage,
	uniqueGuestEmail,
} from '../../utils/back-in-stock-notifications';
import { deleteOption } from '../../utils/options';

const test = baseTest.extend( {
	product: async ( { restApi }, use ) => {
		const product = await createOutOfStockVariableProduct( restApi );
		await use( product );
		await product.cleanup();
	},
} );

test.describe(
	'Back in Stock Notifications — signing up on a variable product',
	{ tag: [ tags.SERVICES ] },
	() => {
		test.afterEach( async ( { baseURL } ) => {
			for ( const option of Object.values( BIS_OPTIONS ) ) {
				await deleteOption( request, baseURL!, option );
			}
		} );

		test.describe( 'Guest, single opt-in', () => {
			test.beforeEach( async ( { baseURL } ) => {
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: false,
					requireAccount: false,
				} );
			} );

			test( 'signup form is a real form sibling of the variations form', async ( {
				page,
				product,
			} ) => {
				await page.goto( product.permalink );

				// The BIS form is a real <form> (not a <div>), labelled by the
				// user-visible heading.
				const bisForm = page.locator( 'form.wc_bis_form' );
				await expect( bisForm ).toHaveCount( 1 );
				await expect( bisForm ).toHaveAttribute(
					'aria-labelledby',
					/wc_bis_form_heading_/
				);

				// And it carries a real submit button so keyboard users can
				// submit with Enter.
				await expect(
					bisForm.locator(
						'button[type="submit"][name="wc_bis_register"]'
					)
				).toHaveCount( 1 );

				// Accessibility guarantee: the BIS form must NOT be nested
				// inside the variations form (HTML disallows nested <form>s,
				// and the legacy plugin's nested-div-as-form workaround is
				// what RSM-446 removes).
				const nestedCount = await page
					.locator( '.variations_form form.wc_bis_form' )
					.count();
				expect( nestedCount ).toBe( 0 );
			} );

			test( 'signup succeeds for the out-of-stock variation and a repeat submit shows "already joined"', async ( {
				page,
				product,
			} ) => {
				await page.goto( product.permalink );

				// Before picking a variation the form is hidden.
				const bisForm = page.locator( 'form.wc_bis_form' );
				await expect( bisForm ).toHaveClass( /(^|\s)hidden(\s|$)/ );

				// Select the out-of-stock variation; the form should become
				// visible (variations JS toggles the `hidden` class).
				await page
					.getByRole( 'combobox', { name: 'Size' } )
					.selectOption( product.outOfStockAttribute );
				await expect( bisForm ).not.toHaveClass( /(^|\s)hidden(\s|$)/ );

				const email = uniqueGuestEmail( 'bis-variable-guest' );
				await signUpOnProductPage( page, { email } );
				await expect(
					page.getByText(
						/You have successfully signed up|You have already joined this waitlist/i
					)
				).toBeVisible();

				// Repeat submission on the same variation surfaces the
				// already-joined notice.
				await page.goto( product.permalink );
				await page
					.getByRole( 'combobox', { name: 'Size' } )
					.selectOption( product.outOfStockAttribute );
				await expect( bisForm ).not.toHaveClass( /(^|\s)hidden(\s|$)/ );
				await signUpOnProductPage( page, { email } );
				await expect(
					page.getByText( /You have already joined this waitlist/i )
				).toBeVisible();
			} );

			test( 'signup form stays hidden for the in-stock variation', async ( {
				page,
				product,
			} ) => {
				await page.goto( product.permalink );

				await page
					.getByRole( 'combobox', { name: 'Size' } )
					.selectOption( product.inStockAttribute );

				await expect( page.locator( 'form.wc_bis_form' ) ).toHaveClass(
					/(^|\s)hidden(\s|$)/
				);
			} );
		} );
	}
);
