/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { TaskType } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ShippingRecommendation as _ShippingRecommendation } from '../shipping-recommendation';
import { ShippingRecommendationProps, TaskProps } from '../types';
import { redirectToWCSSettings } from '../utils';

jest.mock( '../../tax/utils', () => ( {
	hasCompleteAddress: jest.fn().mockReturnValue( true ),
} ) );

jest.mock( '../utils', () => ( {
	redirectToWCSSettings: jest.fn(),
} ) );

jest.mock( '@woocommerce/components', () => {
	const originalModule = jest.requireActual( '@woocommerce/components' );

	return {
		__esModule: true,
		...originalModule,
		Plugins: jest.fn().mockReturnValue( <div>MockedPlugins</div> ),
		Spinner: jest.fn().mockReturnValue( null ),
	};
} );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn().mockImplementation( () => {
		return {
			createNotice: jest.fn(),
			installAndActivatePlugins: jest.fn(),
		};
	} ),
	useSelect: jest.fn().mockImplementation( ( fn ) =>
		fn( () => ( {
			getActivePlugins: jest.fn().mockReturnValue( [] ),
			getInstalledPlugins: jest.fn().mockReturnValue( [] ),
			isPluginsRequesting: jest.fn().mockReturnValue( false ),
			getSettings: () => ( {
				general: {
					woocommerce_default_country: 'US',
				},
			} ),
			getCountries: () => [],
			getLocales: () => [],
			getLocale: () => 'en',
			hasFinishedResolution: () => true,
			getOption: ( key: string ) => {
				return {
					wc_connect_options: {
						tos_accepted: true,
					},
					woocommerce_setup_jetpack_opted_in: 1,
				}[ key ];
			},
		} ) )
	),
} ) );

const taskProps: TaskProps = {
	onComplete: () => {},
	query: {},
	task: {
		id: 'shipping-recommendation',
	} as TaskType,
};

const ShippingRecommendation = ( props: ShippingRecommendationProps ) => {
	return <_ShippingRecommendation { ...taskProps } { ...props } />;
};

describe( 'ShippingRecommendation', () => {
	test( 'should show plugins step when woocommerce-services is not installed and activated', () => {
		const { getByText } = render(
			<ShippingRecommendation
				isJetpackConnected={ false }
				isResolving={ false }
				activePlugins={ [ 'foo' ] }
			/>
		);
		expect( getByText( 'MockedPlugins' ) ).toBeInTheDocument();
	} );

	test( 'should show connect step when WCS&T is activated but not yet connected', () => {
		const { getByRole } = render(
			<ShippingRecommendation
				isJetpackConnected={ false }
				isResolving={ false }
				activePlugins={ [ 'woocommerce-services' ] }
			/>
		);
		expect(
			getByRole( 'button', { name: 'Connect' } )
		).toBeInTheDocument();
	} );

	test( 'should show "complete task" button when WCS&T is activated and Jetpack is connected', () => {
		const { getByRole } = render(
			<ShippingRecommendation
				isJetpackConnected={ true }
				isResolving={ false }
				activePlugins={ [ 'woocommerce-services' ] }
			/>
		);
		expect(
			getByRole( 'button', { name: 'Complete task' } )
		).toBeInTheDocument();
	} );

	test( 'should automatically be redirected when all steps are completed', () => {
		render(
			<ShippingRecommendation
				isJetpackConnected={ true }
				isResolving={ false }
				activePlugins={ [ 'woocommerce-services' ] }
			/>
		);

		expect( redirectToWCSSettings ).toHaveBeenCalled();
	} );

	test( 'should allow location step to be manually navigated', () => {
		const { getByText } = render(
			<ShippingRecommendation
				isJetpackConnected={ true }
				isResolving={ false }
				activePlugins={ [] }
			/>
		);

		getByText( 'Set store location' ).click();
		expect( getByText( 'Address' ) ).toBeInTheDocument();
	} );
} );
