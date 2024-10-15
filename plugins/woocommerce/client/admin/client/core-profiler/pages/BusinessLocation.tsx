/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { SelectControl } from '@woocommerce/components';
import { Icon, chevronDown } from '@wordpress/icons';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CoreProfilerStateMachineContext } from '../index';
import { BusinessLocationEvent } from '../events';
import { CountryStateOption } from '../services/country';
import { Heading } from '../components/heading/heading';
import { Navigation } from '../components/navigation/navigation';
export const BusinessLocation = ( {
	sendEvent,
	navigationProgress,
	context,
}: {
	sendEvent: ( event: BusinessLocationEvent ) => void;
	navigationProgress: number;
	context: Pick< CoreProfilerStateMachineContext, 'countries' >;
} ) => {
	const [ storeCountry, setStoreCountry ] = useState< CountryStateOption >( {
		key: '',
		label: '',
	} );

	const inputLabel = __( 'Select country/region', 'woocommerce' );

	return (
		<div
			className="woocommerce-profiler-business-location"
			data-testid="core-profiler-business-location"
		>
			<Navigation percentage={ navigationProgress } />
			<div className="woocommerce-profiler-page__content woocommerce-profiler-business-location__content">
				<Heading
					className="woocommerce-profiler__stepper-heading"
					title={ __(
						'Where is your business located?',
						'woocommerce'
					) }
					subTitle={ __(
						'We’ll use this information to help you set up payments, shipping, and taxes.',
						'woocommerce'
					) }
				/>
				<SelectControl
					className="woocommerce-profiler-select-control__country"
					instanceId={ 1 }
					placeholder={ inputLabel }
					label={ storeCountry.key === '' ? inputLabel : '' }
					getSearchExpression={ ( query: string ) => {
						return new RegExp(
							'(^' + query + '| — (' + query + '))',
							'i'
						);
					} }
					autoComplete="new-password" // disable autocomplete and autofill
					options={ context.countries }
					excludeSelectedOptions={ false }
					help={ <Icon icon={ chevronDown } /> }
					onChange={ ( results: Array< CountryStateOption > ) => {
						if ( results.length ) {
							setStoreCountry( results[ 0 ] );
						}
					} }
					selected={ storeCountry ? [ storeCountry ] : [] }
					showAllOnFocus
					isSearchable
				/>
				<div className="woocommerce-profiler-button-container woocommerce-profiler-go-to-mystore__button-container">
					<Button
						className="woocommerce-profiler-button"
						variant="primary"
						disabled={ ! storeCountry.key }
						onClick={ () => {
							sendEvent( {
								type: 'BUSINESS_LOCATION_COMPLETED',
								payload: {
									storeLocation: storeCountry.key,
								},
							} );
						} }
					>
						{ __( 'Go to my store', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</div>
	);
};
