/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	CardHeader,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { Fragment, useState } from '@wordpress/element';
import PropTypes from 'prop-types';
import { Chart, Link, TableCard } from '@woocommerce/components';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import { useNoticesData } from './use-notices-data';
import './style.scss';

const NUMBER_FORMATTER = new Intl.NumberFormat();
function formatNumber( value ) {
	return NUMBER_FORMATTER.format( value || 0 );
}

/**
 * One of the two big summary cards (Notifications, Sign-Ups).
 *
 * @param {Object}  props
 * @param {string}  props.title      Card title.
 * @param {Array}   props.metrics    Array of `{ key, label, value }`.
 * @param {Array}   props.chartData  Pre-shaped data array for `<Chart>`.
 * @param {string}  props.itemsLabel Label for the chart's items.
 * @param {boolean} props.isLoading  Whether the underlying data is still loading.
 * @return {Object} React node.
 */
function SummaryCard( { title, metrics, chartData, itemsLabel, isLoading } ) {
	return (
		<Card className="bis-notices-report__summary-card">
			<CardHeader>
				<Text
					as="h2"
					variant="title.small"
					size="20"
					lineHeight="28px"
					weight="500"
				>
					{ title }
				</Text>
			</CardHeader>
			<CardBody size="none">
				<ul className="bis-notices-report__metrics">
					{ metrics.map( ( metric ) => (
						<li
							className="bis-notices-report__metric"
							key={ metric.key }
						>
							<Text
								className="bis-notices-report__metric-label"
								variant="body.small"
								size="18"
								lineHeight="26px"
							>
								{ metric.label }
							</Text>
							<Text
								className="bis-notices-report__metric-value"
								variant="title.medium"
								size="40"
								lineHeight="48px"
								weight="400"
							>
								{ formatNumber( metric.value ) }
							</Text>
						</li>
					) ) }
				</ul>
				<div className="bis-notices-report__chart-wrap">
					<Chart
						chartType="bar"
						data={ chartData }
						dateParser={ '%Y-%m-%dT%H:%M:%S' }
						interval="day"
						mode="time-comparison"
						itemsLabel={ itemsLabel }
						isRequesting={ isLoading }
						showHeaderControls={ false }
					/>
				</div>
			</CardBody>
		</Card>
	);
}

SummaryCard.propTypes = {
	title: PropTypes.string.isRequired,
	metrics: PropTypes.array.isRequired,
	chartData: PropTypes.array.isRequired,
	itemsLabel: PropTypes.string.isRequired,
	isLoading: PropTypes.bool,
};

function productLink( product ) {
	if ( ! product.product_id ) {
		return product.product_name || '';
	}
	const href =
		product.product_edit_link ||
		`post.php?action=edit&post=${ product.product_id }`;
	return (
		<Link href={ href } type="wp-admin">
			{ product.product_name || __( '(deleted product)', 'woocommerce' ) }
		</Link>
	);
}

const PRODUCT_HEADER = {
	key: 'product',
	label: __( 'Product', 'woocommerce' ),
	isLeftAligned: true,
	required: true,
};

function leaderboardRow( product, valueKey ) {
	return [
		{
			display: productLink( product ),
			value: product.product_name || '',
		},
		{
			display: formatNumber( product[ valueKey ] ),
			value: Number( product[ valueKey ] || 0 ),
		},
	];
}

export default function NoticesReport() {
	const [ signupsWindow, setSignupsWindow ] = useState( 'month' );
	const data = useNoticesData( { signupsWindow, timeseriesDays: 15 } );
	const {
		summary,
		charts,
		mostWanted,
		mostOverdue,
		mostSignedUp,
		isLoading,
	} = data;

	const notificationsTotals = summary?.totals || {};
	const signupsTotals = summary?.totals || {};

	return (
		<Fragment>
			<div className="bis-notices-report__row is-summary">
				<SummaryCard
					title={ __( 'Notifications', 'woocommerce' ) }
					itemsLabel={ __( 'Notifications sent', 'woocommerce' ) }
					chartData={ charts.notifications }
					isLoading={ isLoading }
					metrics={ [
						{
							key: 'sent-last-month',
							label: __( 'Sent last month', 'woocommerce' ),
							value: notificationsTotals.this_month
								?.notifications_sent,
						},
						{
							key: 'sent-today',
							label: __( 'Sent today', 'woocommerce' ),
							value: notificationsTotals.today
								?.notifications_sent,
						},
					] }
				/>
				<SummaryCard
					title={ __( 'Sign-Ups', 'woocommerce' ) }
					itemsLabel={ __( 'Sign-ups', 'woocommerce' ) }
					chartData={ charts.signups }
					isLoading={ isLoading }
					metrics={ [
						{
							key: 'signed-up-last-month',
							label: __( 'Signed up last month', 'woocommerce' ),
							value: signupsTotals.this_month?.total_signups,
						},
						{
							key: 'signed-up-today',
							label: __( 'Signed up today', 'woocommerce' ),
							value: signupsTotals.today?.total_signups,
						},
					] }
				/>
			</div>

			<h2 className="bis-notices-report__section-title">
				{ __( 'Product Leaderboards', 'woocommerce' ) }
			</h2>

			<div className="bis-notices-report__row is-leaderboards">
				<TableCard
					title={ __( 'Most wanted', 'woocommerce' ) }
					isLoading={ isLoading }
					headers={ [
						PRODUCT_HEADER,
						{
							key: 'active_signups',
							label: __( 'Customers', 'woocommerce' ),
							isNumeric: true,
							required: true,
						},
					] }
					rows={ mostWanted.map( ( p ) =>
						leaderboardRow( p, 'active_signups' )
					) }
					rowsPerPage={ Math.max( mostWanted.length, 1 ) }
					totalRows={ mostWanted.length }
					showMenu={ false }
				/>
				<TableCard
					title={ __( 'Most overdue', 'woocommerce' ) }
					isLoading={ isLoading }
					headers={ [
						PRODUCT_HEADER,
						{
							key: 'days_overdue',
							label: __( 'Days', 'woocommerce' ),
							isNumeric: true,
							required: true,
						},
					] }
					rows={ mostOverdue.map( ( p ) =>
						leaderboardRow( p, 'days_overdue' )
					) }
					rowsPerPage={ Math.max( mostOverdue.length, 1 ) }
					totalRows={ mostOverdue.length }
					showMenu={ false }
				/>
				<TableCard
					title={ __( 'Most signed-up', 'woocommerce' ) }
					isLoading={ isLoading }
					headers={ [
						PRODUCT_HEADER,
						{
							key: 'signups',
							label: __( 'Customers', 'woocommerce' ),
							isNumeric: true,
							required: true,
						},
					] }
					rows={ mostSignedUp.map( ( p ) =>
						leaderboardRow( p, 'signups' )
					) }
					rowsPerPage={ Math.max( mostSignedUp.length, 1 ) }
					totalRows={ mostSignedUp.length }
					showMenu={ false }
					actions={ [
						<ToggleGroupControl
							key="window"
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							hideLabelFromVision
							label={ __( 'Time window', 'woocommerce' ) }
							value={ signupsWindow }
							onChange={ ( value ) => setSignupsWindow( value ) }
						>
							<ToggleGroupControlOption
								value="week"
								label={ __( 'Week', 'woocommerce' ) }
							/>
							<ToggleGroupControlOption
								value="month"
								label={ __( 'Month', 'woocommerce' ) }
							/>
							<ToggleGroupControlOption
								value="quarter"
								label={ __( 'Quarter', 'woocommerce' ) }
							/>
						</ToggleGroupControl>,
					] }
				/>
			</div>
		</Fragment>
	);
}

NoticesReport.propTypes = {
	path: PropTypes.string,
	query: PropTypes.object,
};
