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
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';
import { Chart, Link, TableCard } from '@woocommerce/components';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import {
	mostOverdue,
	mostSignedUpByWindow,
	mostWanted,
	notificationsTimeseries,
	signupsTimeseries,
	summaryStats,
} from './mock-data';
import './style.scss';

const NUMBER_FORMATTER = new Intl.NumberFormat();
function formatNumber( value ) {
	return NUMBER_FORMATTER.format( value );
}

/**
 * One of the two big summary cards (Notifications, Sign-Ups). Renders a
 * SummaryList of metrics on top with a daily bar Chart underneath.
 *
 * @param {Object} props
 * @param {string} props.title      Card title.
 * @param {Array}  props.metrics    Array of `{ key, label, value }` (one becomes the selected SummaryNumber).
 * @param {Array}  props.chartData  Pre-shaped data array for `<Chart>`.
 * @param {string} props.itemsLabel Label for the chart's items (used in tooltips/legend).
 * @return {Object} React node.
 */
function SummaryCard( { title, metrics, chartData, itemsLabel } ) {
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
						showHeaderControls={ false }
					/>
				</div>
			</CardBody>
		</Card>
	);
}

SummaryCard.propTypes = {
	title: PropTypes.string.isRequired,
	metrics: PropTypes.arrayOf(
		PropTypes.shape( {
			key: PropTypes.string.isRequired,
			label: PropTypes.string.isRequired,
			value: PropTypes.number.isRequired,
		} )
	).isRequired,
	chartData: PropTypes.array.isRequired,
	itemsLabel: PropTypes.string.isRequired,
};

function productLink( product ) {
	return (
		<Link
			href={ `post.php?action=edit&post=${ product.productId }` }
			type="wp-admin"
		>
			{ product.name }
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
			value: product.name,
		},
		{
			display: formatNumber( product[ valueKey ] ),
			value: product[ valueKey ],
		},
	];
}

export default class NoticesReport extends Component {
	state = {
		signupsWindow: 'month',
	};

	render() {
		const { signupsWindow } = this.state;
		const signupRows = mostSignedUpByWindow[ signupsWindow ];

		return (
			<Fragment>
				<div className="bis-notices-report__row is-summary">
					<SummaryCard
						title={ __( 'Notifications', 'woocommerce' ) }
						itemsLabel={ __( 'Notifications sent', 'woocommerce' ) }
						chartData={ notificationsTimeseries }
						metrics={ [
							{
								key: 'sent-last-month',
								label: __( 'Sent last month', 'woocommerce' ),
								value: summaryStats.notifications.sentLastMonth,
							},
							{
								key: 'sent-today',
								label: __( 'Sent today', 'woocommerce' ),
								value: summaryStats.notifications.sentToday,
							},
							{
								key: 'queued',
								label: __( 'Queued', 'woocommerce' ),
								value: summaryStats.notifications.queued,
							},
						] }
					/>
					<SummaryCard
						title={ __( 'Sign-Ups', 'woocommerce' ) }
						itemsLabel={ __( 'Sign-ups', 'woocommerce' ) }
						chartData={ signupsTimeseries }
						metrics={ [
							{
								key: 'signed-up-last-month',
								label: __(
									'Signed up last month',
									'woocommerce'
								),
								value: summaryStats.signups.signedUpLastMonth,
							},
							{
								key: 'signed-up-today',
								label: __( 'Signed up today', 'woocommerce' ),
								value: summaryStats.signups.signedUpToday,
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
						headers={ [
							PRODUCT_HEADER,
							{
								key: 'customers',
								label: __( 'Customers', 'woocommerce' ),
								isNumeric: true,
								required: true,
							},
						] }
						rows={ mostWanted.map( ( p ) =>
							leaderboardRow( p, 'customers' )
						) }
						rowsPerPage={ mostWanted.length }
						totalRows={ mostWanted.length }
						showMenu={ false }
					/>
					<TableCard
						title={ __( 'Most overdue', 'woocommerce' ) }
						headers={ [
							PRODUCT_HEADER,
							{
								key: 'days',
								label: __( 'Days', 'woocommerce' ),
								isNumeric: true,
								required: true,
							},
						] }
						rows={ mostOverdue.map( ( p ) =>
							leaderboardRow( p, 'days' )
						) }
						rowsPerPage={ mostOverdue.length }
						totalRows={ mostOverdue.length }
						showMenu={ false }
					/>
					<TableCard
						title={ __( 'Most signed-up', 'woocommerce' ) }
						headers={ [
							PRODUCT_HEADER,
							{
								key: 'customers',
								label: __( 'Customers', 'woocommerce' ),
								isNumeric: true,
								required: true,
							},
						] }
						rows={ signupRows.map( ( p ) =>
							leaderboardRow( p, 'customers' )
						) }
						rowsPerPage={ signupRows.length }
						totalRows={ signupRows.length }
						showMenu={ false }
						actions={ [
							<ToggleGroupControl
								key="window"
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								hideLabelFromVision
								label={ __( 'Time window', 'woocommerce' ) }
								value={ signupsWindow }
								onChange={ ( value ) =>
									this.setState( { signupsWindow: value } )
								}
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
}

NoticesReport.propTypes = {
	path: PropTypes.string,
	query: PropTypes.object,
};
