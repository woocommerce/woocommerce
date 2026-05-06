const fs = require( 'fs' );
const path = require( 'path' );

const repoRoot = path.resolve( __dirname, '../../../../../' );
const ordersListTable = fs.readFileSync(
	path.join( repoRoot, 'src/Internal/Admin/Orders/ListTable.php' ),
	'utf8'
);
const productsListTable = fs.readFileSync(
	path.join(
		repoRoot,
		'includes/admin/list-tables/class-wc-admin-list-table-products.php'
	),
	'utf8'
);
const couponsListTable = fs.readFileSync(
	path.join(
		repoRoot,
		'includes/admin/list-tables/class-wc-admin-list-table-coupons.php'
	),
	'utf8'
);
const apiKeys = fs.readFileSync(
	path.join( repoRoot, 'includes/admin/class-wc-admin-api-keys.php' ),
	'utf8'
);
const webhooks = fs.readFileSync(
	path.join( repoRoot, 'includes/admin/class-wc-admin-webhooks.php' ),
	'utf8'
);
const stockNotifications = fs.readFileSync(
	path.join(
		repoRoot,
		'src/Internal/StockNotifications/Admin/Templates/html-admin-notifications.php'
	),
	'utf8'
);
const adminStyles = fs.readFileSync(
	path.join( repoRoot, 'client/legacy/css/admin.scss' ),
	'utf8'
);
const blankStateSources = [
	ordersListTable,
	productsListTable,
	couponsListTable,
	apiKeys,
	webhooks,
	stockNotifications,
];
const blankStateModifiersStart = adminStyles.indexOf(
	'.woocommerce-BlankState--orders,'
);
const baseBlankStateStart = adminStyles.lastIndexOf(
	'.woocommerce-BlankState {',
	blankStateModifiersStart
);
const baseBlankStateStyles = adminStyles.slice(
	baseBlankStateStart,
	blankStateModifiersStart
);

const getBlankStateCtaClasses = ( source ) =>
	[
		...source.matchAll(
			// Matches a class attribute containing the blank-state CTA token
			// and captures its full class list.
			/class="(?<classList>[^"]*\bwoocommerce-BlankState-cta\b[^"]*)"/g
		),
	].map( ( match ) => match.groups.classList.split( /\s+/ ) );

const getBlankStateTargetBlankLinks = ( source ) =>
	[
		...source.matchAll(
			// Matches CTA links that open in a new tab, regardless of attribute order.
			/<a\b(?=[^>]*\bwoocommerce-BlankState-cta\b)(?=[^>]*\btarget="_blank")[^>]*>/g
		),
	].map( ( match ) => match[ 0 ] );

describe( 'legacy blank state styles', () => {
	test( 'uses modifier classes for each legacy blank state', () => {
		expect( ordersListTable ).toContain(
			'woocommerce-BlankState woocommerce-BlankState--orders'
		);
		expect( productsListTable ).toContain(
			'woocommerce-BlankState woocommerce-BlankState--products'
		);
		expect( couponsListTable ).toContain(
			'woocommerce-BlankState woocommerce-BlankState--coupons'
		);
		expect( apiKeys ).toContain(
			'woocommerce-BlankState woocommerce-BlankState--api'
		);
		expect( webhooks ).toContain(
			'woocommerce-BlankState woocommerce-BlankState--webhooks'
		);
		expect( stockNotifications ).toContain(
			'woocommerce-BlankState woocommerce-BlankState--stock-notifications'
		);
		for ( const source of blankStateSources ) {
			const ctaClasses = getBlankStateCtaClasses( source );

			expect( ctaClasses.length ).toBeGreaterThan( 0 );
			for ( const classList of ctaClasses ) {
				expect( classList ).toEqual(
					expect.arrayContaining( [
						'woocommerce-BlankState-cta',
						'button',
						'button-secondary',
					] )
				);
				expect( classList ).not.toContain( 'button-primary' );
			}
		}
	} );

	test( 'protects blank state documentation links that open in a new tab', () => {
		for ( const source of blankStateSources ) {
			for ( const link of getBlankStateTargetBlankLinks( source ) ) {
				expect( link ).toContain( 'rel="noopener noreferrer"' );
			}
		}
	} );

	test( 'finds target-blank blank state links regardless of attribute order', () => {
		const links = getBlankStateTargetBlankLinks(
			'<a target="_blank" class="button woocommerce-BlankState-cta">Docs</a>'
		);

		expect( links ).toHaveLength( 1 );
	} );

	test( 'assigns icons only to known blank state types', () => {
		expect( blankStateModifiersStart ).toBeGreaterThan( -1 );
		expect( baseBlankStateStart ).toBeGreaterThan( -1 );

		for ( const modifier of [
			'orders',
			'products',
			'coupons',
			'api',
			'webhooks',
			'stock-notifications',
		] ) {
			expect( adminStyles ).toContain(
				`.woocommerce-BlankState--${ modifier }`
			);
		}

		expect( baseBlankStateStyles ).not.toContain(
			'--wc-blank-state-icon'
		);
		const stockNotificationsModifierStart = adminStyles.indexOf(
			'.woocommerce-BlankState--stock-notifications',
			blankStateModifiersStart
		);
		const beforeSelectorIndex = adminStyles.indexOf(
			'.woocommerce-BlankState-message::before',
			stockNotificationsModifierStart
		);

		expect( stockNotificationsModifierStart ).toBeGreaterThan(
			blankStateModifiersStart
		);
		expect( beforeSelectorIndex ).toBeGreaterThan(
			stockNotificationsModifierStart
		);
	} );

	test( 'uses Core-grid vertical spacing for the empty state block', () => {
		expect( adminStyles ).toContain( 'padding: $grid-unit-60 0;' );
	} );
} );
