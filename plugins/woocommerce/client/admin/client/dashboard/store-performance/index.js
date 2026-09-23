/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { getPersistedQuery } from '@woocommerce/navigation';
import { withSelect } from '@wordpress/data';
import {
	EllipsisMenu,
	MenuItem,
	MenuTitle,
	SectionHeader,
	SummaryList,
	SummaryListPlaceholder,
	SummaryNumber,
	TourKit,
} from '@woocommerce/components';
import { getDateParamsFromQuery } from '@woocommerce/date';
import { recordEvent } from '@woocommerce/tracks';
import { CurrencyContext } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import './style.scss';
import { getIndicatorData, getIndicatorValues } from './utils';
import { getAdminSetting } from '~/utils/admin-settings';

const { performanceIndicators: indicators } = getAdminSetting(
	'dataEndpoints',
	{
		performanceIndicators: [],
	}
);

const TOUR_SEEN_KEY = 'wc-performance-metrics-tour-seen';

const hasSeenTour = () => {
	try {
		return window.localStorage.getItem( TOUR_SEEN_KEY ) === 'yes';
	} catch {
		// Storage unavailable: treat as seen so the tour never traps anyone.
		return true;
	}
};

class StorePerformance extends Component {
	constructor( props ) {
		super( props );
		this.state = { showTour: ! hasSeenTour() };
		this.dismissTour = this.dismissTour.bind( this );
	}

	dismissTour() {
		try {
			window.localStorage.setItem( TOUR_SEEN_KEY, 'yes' );
		} catch {}
		this.setState( { showTour: false } );
	}

	renderTour() {
		if ( ! this.state.showTour ) {
			return null;
		}

		return (
			<TourKit
				config={ {
					steps: [
						{
							referenceElements: {
								desktop:
									'.woocommerce-dashboard__performance-menu .woocommerce-ellipsis-menu__toggle',
							},
							meta: {
								heading: __(
									'Choose which metrics to display',
									'woocommerce'
								),
								descriptions: {
									desktop: __(
										'Open this menu to show or hide any of the metrics available for your store.',
										'woocommerce'
									),
								},
								primaryButton: {
									text: __( 'Got it', 'woocommerce' ),
								},
							},
						},
					],
					closeHandler: this.dismissTour,
					options: {
						effects: {
							arrowIndicator: true,
							spotlight: { interactivity: { enabled: true } },
						},
					},
				} }
			/>
		);
	}

	renderMenu() {
		const {
			hiddenBlocks,
			isFirst,
			isLast,
			onMove,
			onRemove,
			onTitleBlur,
			onTitleChange,
			onToggleHiddenBlock,
			titleInput,
			controls: Controls,
		} = this.props;

		return (
			<EllipsisMenu
				className="woocommerce-dashboard__performance-menu"
				label={ __(
					'Choose which analytics to display and the section name',
					'woocommerce'
				) }
				placement={ 'bottom-end' }
				renderContent={ ( { onToggle } ) => (
					<Fragment>
						<MenuTitle>
							{ __( 'Display stats:', 'woocommerce' ) }
						</MenuTitle>
						{ indicators.map( ( indicator, i ) => {
							const checked = ! hiddenBlocks.includes(
								indicator.stat
							);
							return (
								<MenuItem
									checked={ checked }
									isCheckbox
									isClickable
									key={ i }
									onInvoke={ () => {
										onToggleHiddenBlock( indicator.stat )();
										recordEvent( 'dash_indicators_toggle', {
											status: checked ? 'off' : 'on',
											key: indicator.stat,
										} );
									} }
								>
									{ indicator.label }
								</MenuItem>
							);
						} ) }
						<Controls
							onToggle={ onToggle }
							onMove={ onMove }
							onRemove={ onRemove }
							isFirst={ isFirst }
							isLast={ isLast }
							onTitleBlur={ onTitleBlur }
							onTitleChange={ onTitleChange }
							titleInput={ titleInput }
						/>
					</Fragment>
				) }
			/>
		);
	}

	renderList() {
		const {
			query,
			primaryRequesting,
			secondaryRequesting,
			primaryError,
			secondaryError,
			primaryData,
			secondaryData,
			userIndicators,
			defaultDateRange,
		} = this.props;
		if ( primaryRequesting || secondaryRequesting ) {
			return (
				<SummaryListPlaceholder
					numberOfItems={ userIndicators.length }
				/>
			);
		}

		if ( primaryError || secondaryError ) {
			return null;
		}

		const persistedQuery = getPersistedQuery( query );

		const { compare } = getDateParamsFromQuery( query, defaultDateRange );
		const prevLabel =
			compare === 'previous_period'
				? __( 'Previous period:', 'woocommerce' )
				: __( 'Previous year:', 'woocommerce' );
		const { formatAmount, getCurrencyConfig } = this.context;
		const currency = getCurrencyConfig();
		return (
			<SummaryList>
				{ () =>
					userIndicators.map( ( indicator, i ) => {
						const {
							primaryValue,
							secondaryValue,
							delta,
							reportUrl,
							reportUrlType,
						} = getIndicatorValues( {
							indicator,
							primaryData,
							secondaryData,
							currency,
							formatAmount,
							persistedQuery,
						} );

						return (
							<SummaryNumber
								key={ i }
								href={ reportUrl }
								hrefType={ reportUrlType }
								label={ indicator.label }
								value={ primaryValue }
								prevLabel={ prevLabel }
								prevValue={ secondaryValue }
								delta={ delta }
								onLinkClickCallback={ () => {
									recordEvent( 'dash_indicators_click', {
										key: indicator.stat,
									} );
								} }
							/>
						);
					} )
				}
			</SummaryList>
		);
	}

	render() {
		const { userIndicators, title } = this.props;
		return (
			<Fragment>
				<SectionHeader
					title={ title || __( 'Store Performance', 'woocommerce' ) }
					menu={ this.renderMenu() }
				/>
				{ userIndicators.length > 0 && (
					<div className="woocommerce-dashboard__store-performance">
						{ this.renderList() }
					</div>
				) }
				{ this.renderTour() }
			</Fragment>
		);
	}
}

StorePerformance.contextType = CurrencyContext;

export default compose(
	withSelect( ( select, props ) => {
		const { hiddenBlocks, query, filters } = props;
		const userIndicators = indicators.filter(
			( indicator ) => ! hiddenBlocks.includes( indicator.stat )
		);

		const data = {
			hiddenBlocks,
			userIndicators,
			indicators,
		};
		if ( userIndicators.length === 0 ) {
			return data;
		}
		const indicatorData = getIndicatorData(
			select,
			userIndicators,
			query,
			filters
		);

		return {
			...data,
			...indicatorData,
		};
	} )
)( StorePerformance );
