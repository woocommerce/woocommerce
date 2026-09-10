/**
 * External dependencies
 */
import { useMemo, useState, useEffect } from '@wordpress/element';
import { Notice, Spinner } from '@wordpress/components';
import { EmptyContent } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';
import type { ReactNode } from 'react';
import type { View, DataViews as DataViewsType } from '@wordpress/dataviews';
// @ts-expect-error - The /wp entry has no resolvable types under legacy node module resolution; see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/#dataviews
import { DataViews as DataViewsWp } from '@wordpress/dataviews/wp';

/**
 * Internal dependencies
 */
import { useFinanceProviders } from '../data/use-finance-providers';
import { useLastProvider } from '../data/use-last-provider';
import { usePayoutsPage } from '../data/use-payouts-page';
import { useAmountFormatter } from '../utils/format-amount';
import { ProviderSelect } from '../components/provider-select';
import { FinanceDrawer } from '../components/drawer';
import { PayoutDetails } from './payout-details';
import {
	getPayoutFields,
	pinPayoutFields,
	DEFAULT_VISIBLE_FIELDS,
} from './fields';
import {
	getPaginationInfo,
	useCursorPagination,
} from './use-cursor-pagination';
import {
	DEFAULT_PER_PAGE,
	PAYMENTS_SETTINGS_PATH,
	PER_PAGE_SIZES,
} from '../constants';
import type { FinanceProvider, Payout } from '../types';
import './style.scss';

const DataViews = DataViewsWp as typeof DataViewsType;

const DEFAULT_VIEW: View = {
	type: 'table',
	page: 1,
	perPage: DEFAULT_PER_PAGE,
	fields: DEFAULT_VISIBLE_FIELDS,
	layout: {},
};

const DEFAULT_LAYOUTS = { table: {} };

// Body class that paints the whole admin page white while the Payouts page is mounted.
const PAGE_BODY_CLASS = 'woocommerce-finance-payouts-page';

const supportsPayouts = ( provider: FinanceProvider ): boolean =>
	provider.data_types.some( ( dataType ) => dataType.type === 'payouts' );

const getItemId = ( payout: Payout ): string =>
	`${ payout.provider_id }:${ payout.id }`;

const PayoutsWrapper = ( { children }: { children: ReactNode } ) => {
	return <div className="woocommerce-finance-payouts">{ children }</div>;
};

export const FinancePayouts = () => {
	const {
		providers,
		isLoading: isLoadingProviders,
		error: providersError,
	} = useFinanceProviders();
	const payoutProviders = useMemo(
		() => providers.filter( supportsPayouts ),
		[ providers ]
	);
	const providersById = useMemo(
		() =>
			Object.fromEntries(
				providers.map( ( provider ) => [
					provider.provider_id,
					provider,
				] )
			),
		[ providers ]
	);

	useEffect( () => {
		document.body.classList.add( PAGE_BODY_CLASS );
		return () => document.body.classList.remove( PAGE_BODY_CLASS );
	}, [] );

	const { selectedId, selectProvider } = useLastProvider( payoutProviders );
	const pagination = useCursorPagination( DEFAULT_PER_PAGE );
	const [ view, setView ] = useState< View >( DEFAULT_VIEW );
	const [ selectedPayout, setSelectedPayout ] = useState< Payout | null >(
		null
	);

	const { page, isLoading, error } = usePayoutsPage( selectedId, {
		cursor: pagination.cursor,
		perPage: pagination.perPage,
	} );

	const { registerNext } = pagination;
	useEffect( () => {
		if ( page ) {
			registerNext( page.next_cursor );
		}
	}, [ page, registerNext ] );

	const formatAmount = useAmountFormatter();
	const fields = useMemo(
		() =>
			getPayoutFields( {
				providersById,
				formatAmount,
				onSelectPayout: setSelectedPayout,
			} ),
		[ providersById, formatAmount ]
	);

	const onChangeProvider = ( providerId: string ) => {
		pagination.reset();
		setSelectedPayout( null );
		selectProvider( providerId );
	};

	const onChangeView = ( nextView: View ) => {
		const nextPerPage = nextView.perPage ?? DEFAULT_PER_PAGE;
		const nextPage = nextView.page ?? 1;

		if ( nextPerPage !== pagination.perPage ) {
			pagination.reset( nextPerPage );
		} else if (
			nextPage !== pagination.page &&
			! pagination.goToPage( nextPage )
		) {
			return;
		}

		setView( { ...nextView, fields: pinPayoutFields( nextView.fields ) } );
	};

	if ( isLoadingProviders ) {
		return (
			<PayoutsWrapper>
				<Spinner />
			</PayoutsWrapper>
		);
	}

	if ( providersError ) {
		return (
			<PayoutsWrapper>
				<Notice status="error" isDismissible={ false }>
					{ providersError.message }
				</Notice>
			</PayoutsWrapper>
		);
	}

	if ( payoutProviders.length === 0 ) {
		return (
			<PayoutsWrapper>
				<EmptyContent
					title={ __( 'No payout providers', 'woocommerce' ) }
					message={ __(
						'Connect a payment provider that shares its payouts to see them here.',
						'woocommerce'
					) }
					actionLabel={ __(
						'Manage payment providers',
						'woocommerce'
					) }
					actionURL={ getAdminLink( PAYMENTS_SETTINGS_PATH ) }
				/>
			</PayoutsWrapper>
		);
	}

	const paginationInfo = getPaginationInfo(
		pagination.state,
		page ? page.items.length : null,
		page?.has_more ?? false
	);

	return (
		<PayoutsWrapper>
			<ProviderSelect
				providers={ payoutProviders }
				value={ selectedId }
				onChange={ onChangeProvider }
			/>
			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error.message }
				</Notice>
			) }
			<DataViews< Payout >
				view={ {
					...view,
					page: pagination.page,
					perPage: pagination.perPage,
				} }
				onChangeView={ onChangeView }
				fields={ fields }
				data={ page?.items ?? [] }
				isLoading={ isLoading }
				paginationInfo={ paginationInfo }
				defaultLayouts={ DEFAULT_LAYOUTS }
				config={ { perPageSizes: PER_PAGE_SIZES } }
				search={ false }
				getItemId={ getItemId }
				empty={
					<p className="woocommerce-finance-payouts__empty">
						{ __(
							'No payouts found for this provider.',
							'woocommerce'
						) }
					</p>
				}
			/>
			{ selectedPayout && (
				<FinanceDrawer
					title={ __( 'Payout details', 'woocommerce' ) }
					onClose={ () => setSelectedPayout( null ) }
				>
					<PayoutDetails
						payout={ selectedPayout }
						provider={ providersById[ selectedPayout.provider_id ] }
						formatAmount={ formatAmount }
					/>
				</FinanceDrawer>
			) }
		</PayoutsWrapper>
	);
};

/*
 * Exported as default to support code-splitting and lazy loading.
 */
export default FinancePayouts;
