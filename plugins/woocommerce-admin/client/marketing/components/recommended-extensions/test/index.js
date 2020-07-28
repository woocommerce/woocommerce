/**
 * External dependencies
 */
import { shallow } from 'enzyme';
import { recordEvent } from 'lib/tracks';
import { Spinner } from '@wordpress/components';

/**
 * WooCommerce dependencies
 */
import { Card } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { RecommendedExtensions } from '../index.js';
import RecommendedExtensionsItem from '../item.js';

jest.mock( 'lib/tracks' );

const mockExtensions = [
	{
		title: 'AutomateWoo',
		description: 'Does things.',
		url: 'https://woocommerce.com/products/automatewoo/',
		icon: 'icons/automatewoo.svg',
		product: 'automatewoo',
		plugin: 'automatewoo/automatewoo.php',
	},
	{
		title: 'Mailchimp for WooCommerce',
		description: 'Does things.',
		url: 'https://woocommerce.com/products/mailchimp-for-woocommerce/',
		icon: 'icons/mailchimp.svg',
		product: 'mailchimp-for-woocommerce',
		plugin: 'mailchimp-for-woocommerce/mailchimp-woocommerce.php',
	},
];

describe( 'Recommendations and not loading', () => {
	let recommendedExtensionsWrapper;

	beforeEach( () => {
		recommendedExtensionsWrapper = shallow(
			<RecommendedExtensions
				extensions={ mockExtensions }
				isLoading={ false }
				category={ 'marketing' }
			/>
		);
	} );

	it( 'should not display the spinner', () => {
		const spinner = recommendedExtensionsWrapper.find( Spinner );
		expect( spinner.length ).toBe( 0 );
	} );

	it( 'should display default title and description', () => {
		const cardWrapper = recommendedExtensionsWrapper.find( Card );
		expect( cardWrapper.prop( 'title' ) ).toBe( 'Recommended extensions' );
		expect( cardWrapper.prop( 'description' ) ).toBe(
			'Great marketing requires the right tools. Take your marketing to the next level with our recommended marketing extensions.'
		);
	} );

	it( 'should display correct number of recommendations', () => {
		const itemsContainer = recommendedExtensionsWrapper.find(
			'div.woocommerce-marketing-recommended-extensions-card__items'
		);
		expect( itemsContainer.length ).toBe( 1 );

		const items = recommendedExtensionsWrapper.find(
			RecommendedExtensionsItem
		);
		expect( items.length ).toBe( 2 );
	} );
} );

describe( 'Recommendations and loading', () => {
	let recommendedExtensionsWrapper;

	beforeEach( () => {
		recommendedExtensionsWrapper = shallow(
			<RecommendedExtensions
				extensions={ mockExtensions }
				isLoading={ true }
				category={ 'marketing' }
			/>
		);
	} );

	it( 'should display spinner', () => {
		const spinner = recommendedExtensionsWrapper.find( Spinner );
		expect( spinner.length ).toBe( 1 );
	} );

	it( 'should not display recommendations', () => {
		const itemsContainer = recommendedExtensionsWrapper.find(
			'div.woocommerce-marketing-recommended-extensions-card__items'
		);
		expect( itemsContainer.length ).toBe( 0 );
	} );
} );

describe( 'No Recommendations and not loading', () => {
	let recommendedExtensionsWrapper;

	beforeEach( () => {
		recommendedExtensionsWrapper = shallow(
			<RecommendedExtensions
				extensions={ [] }
				isLoading={ false }
				category={ 'marketing' }
			/>
		);
	} );

	it( 'should not display spinner', () => {
		const spinner = recommendedExtensionsWrapper.find( Spinner );
		expect( spinner.length ).toBe( 0 );
	} );

	it( 'should not display recommendations', () => {
		const itemsContainer = recommendedExtensionsWrapper.find(
			'div.woocommerce-marketing-recommended-extensions-card__items'
		);
		expect( itemsContainer.length ).toBe( 0 );
	} );
} );

describe( 'Click Recommendations', () => {
	let recommendedExtensionItemWrapper;

	beforeEach( () => {
		recommendedExtensionItemWrapper = shallow(
			<RecommendedExtensionsItem
				title={ 'AutomateWoo' }
				description={ 'Does things.' }
				icon={ 'icons/automatewoo.svg' }
				url={ 'https://woocommerce.com/products/automatewoo/' }
			/>
		);
	} );

	it( 'should record an event when clicked', () => {
		const item = recommendedExtensionItemWrapper.find( 'a' );
		expect( item.length ).toBe( 1 );
		item.simulate( 'click' );
		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'marketing_recommended_extension',
			{
				name: 'AutomateWoo',
			}
		);
	} );
} );

describe( 'Custom title and description ', () => {
	let recommendedExtensionsWrapper;

	beforeEach( () => {
		recommendedExtensionsWrapper = shallow(
			<RecommendedExtensions
				extensions={ mockExtensions }
				isLoading={ false }
				title={ 'Custom Title' }
				description={ 'Custom Description' }
				category={ 'marketing' }
			/>
		);
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should override defaults', () => {
		const cardWrapper = recommendedExtensionsWrapper.find( Card );

		expect( cardWrapper.prop( 'title' ) ).toBe( 'Custom Title' );
		expect( cardWrapper.prop( 'description' ) ).toBe(
			'Custom Description'
		);
	} );
} );
