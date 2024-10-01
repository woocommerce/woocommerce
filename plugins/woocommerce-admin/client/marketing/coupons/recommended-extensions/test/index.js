/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { RecommendedExtensions } from '../index.js';
import RecommendedExtensionsItem from '../item.js';

jest.mock( '@woocommerce/tracks' );

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
		recommendedExtensionsWrapper = render(
			<RecommendedExtensions
				extensions={ mockExtensions }
				isLoading={ false }
				category={ 'marketing' }
			/>
		);
	} );

	it( 'should not display the placeholder', () => {
		const { container } = recommendedExtensionsWrapper;
		expect(
			container.querySelector(
				'.is-loading.woocommerce-marketing-recommended-extensions-item'
			)
		).toBeNull();
	} );

	it( 'should display default title and description', () => {
		const { getByText } = recommendedExtensionsWrapper;

		expect( getByText( 'Recommended extensions' ) ).toBeInTheDocument();

		expect(
			getByText(
				'Great marketing requires the right tools. Take your marketing to the next level with our recommended marketing extensions.'
			)
		).toBeInTheDocument();
	} );

	it( 'should display correct number of recommendations', () => {
		const { getByRole } = recommendedExtensionsWrapper;

		expect(
			getByRole( 'heading', { level: 4, name: 'AutomateWoo' } )
		).toBeInTheDocument();

		expect(
			getByRole( 'heading', {
				level: 4,
				name: 'Mailchimp for WooCommerce',
			} )
		).toBeInTheDocument();
	} );
} );

describe( 'Recommendations and loading', () => {
	let recommendedExtensionsWrapper;

	beforeEach( () => {
		recommendedExtensionsWrapper = render(
			<RecommendedExtensions
				extensions={ mockExtensions }
				isLoading={ true }
				category={ 'marketing' }
			/>
		);
	} );

	it( 'should display placeholder', () => {
		const { container } = recommendedExtensionsWrapper;
		expect(
			container.querySelector(
				'.is-loading.woocommerce-marketing-recommended-extensions-item'
			)
		).toBeTruthy();
	} );

	it( 'should not display recommendations', () => {
		const { queryByRole } = recommendedExtensionsWrapper;

		expect(
			queryByRole( 'heading', { level: 4, name: 'AutomateWoo' } )
		).toBeNull();

		expect(
			queryByRole( 'heading', {
				level: 4,
				name: 'Mailchimp for WooCommerce',
			} )
		).toBeNull();
	} );
} );

describe( 'No Recommendations and not loading', () => {
	let recommendedExtensionsWrapper;

	beforeEach( () => {
		recommendedExtensionsWrapper = render(
			<RecommendedExtensions
				extensions={ [] }
				isLoading={ false }
				category={ 'marketing' }
			/>
		);
	} );

	it( 'should not display placeholder', () => {
		const { container } = recommendedExtensionsWrapper;
		expect(
			container.querySelector(
				'.is-loading.woocommerce-marketing-recommended-extensions-item'
			)
		).toBeNull();
	} );

	it( 'should not display recommendations', () => {
		const { container } = recommendedExtensionsWrapper;
		expect(
			container.getElementsByClassName(
				'woocommerce-marketing-recommended-extensions-card__items'
			)
		).toHaveLength( 0 );
	} );
} );

describe( 'Click Recommendations', () => {
	it( 'should record an event when clicked', () => {
		const { getByRole } = render(
			<RecommendedExtensionsItem
				title={ 'AutomateWoo' }
				description={ 'Does things.' }
				icon={ 'icons/automatewoo.svg' }
				url={ 'https://woocommerce.com/products/automatewoo/' }
				product={ 'automatewoo' }
				category={ 'marketing' }
			/>
		);

		userEvent.click( getByRole( 'link' ) );

		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'marketing_recommended_extension',
			{
				name: 'AutomateWoo',
				source: 'plugin-woocommerce',
			}
		);
	} );
} );

describe( 'Custom title and description', () => {
	it( 'should override defaults', () => {
		const { getByText } = render(
			<RecommendedExtensions
				extensions={ mockExtensions }
				isLoading={ false }
				title={ 'Custom Title' }
				description={ 'Custom Description' }
				category={ 'marketing' }
			/>
		);

		expect( getByText( 'Custom Title' ) ).toBeInTheDocument();
		expect( getByText( 'Custom Description' ) ).toBeInTheDocument();
	} );
} );
