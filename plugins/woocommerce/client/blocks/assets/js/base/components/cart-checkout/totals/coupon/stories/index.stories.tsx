/**
 * External dependencies
 */
import { useArgs } from 'storybook/preview-api';
import type { StoryFn, Meta } from '@storybook/react-webpack5';
import { INTERACTION_TIMEOUT } from '@woocommerce/storybook-controls';

/**
 * Internal dependencies
 */
import { TotalsCoupon, TotalsCouponProps } from '..';

export default {
	title: 'Base Components/Totals/Coupon',
	component: TotalsCoupon,
	args: {
		initialOpen: true,
	},
} as Meta< TotalsCouponProps >;

const Template: StoryFn< TotalsCouponProps > = ( args ) => {
	const [ {}, setArgs ] = useArgs();

	const onSubmit = ( code: string ) => {
		args.onSubmit?.( code );
		setArgs( { isLoading: true } );
		return new Promise< boolean >( ( resolve ) => {
			setTimeout( () => {
				setArgs( { isLoading: false } );
				resolve( true );
			}, INTERACTION_TIMEOUT );
		} );
	};

	return <TotalsCoupon { ...args } onSubmit={ onSubmit } />;
};

export const Default = Template.bind( {} );
Default.args = {};

export const LoadingState = Template.bind( {} );
LoadingState.args = {
	isLoading: true,
};

// Type a code and click Apply to see the error the server would return.
export const ErrorState: StoryFn< TotalsCouponProps > = ( args ) => {
	const onSubmit = () => Promise.reject( new Error( 'Invalid coupon code' ) );

	return (
		<TotalsCoupon
			{ ...args }
			displayCouponForm={ true }
			onSubmit={ onSubmit }
		/>
	);
};
