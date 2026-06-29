import ZonesTab from './ZonesTab.jsx';
import SettingsTab, { LabelsPackagesTab } from './SettingsTab.jsx';

function LiveRateSettings( { carriers } ) {
	const wooShipping = carriers.find(
		( carrier ) => carrier.id === 'woocommerce-shipping'
	);

	return (
		<div className="woo-shipping-detail-stack">
			<section
				className="carrier-detail-card"
				aria-labelledby="live-rates-service-title"
			>
				<div className="carrier-detail-card-head">
					<h3 id="live-rates-service-title">Live rate service</h3>
					<p>
						Connected services become available as rate sources
						inside delivery options.
					</p>
				</div>
				<div className="carrier-detail-card-body">
					<div className="carrier-detail-row">
						<div>
							<div className="carrier-detail-row-label">
								Woo Shipping
							</div>
							<div className="carrier-detail-row-meta">
								{ wooShipping?.description ||
									'First-party labels and live rates from Woo.' }
							</div>
						</div>
						<div className="carrier-detail-row-value">
							<span className="carrier-detail-strong">
								Connected
							</span>
						</div>
					</div>
					<div className="carrier-detail-row">
						<div>
							<div className="carrier-detail-row-label">
								Carriers available
							</div>
							<div className="carrier-detail-row-meta">
								USPS, UPS, DHL Express
							</div>
						</div>
						<div className="carrier-detail-row-value">Ready</div>
					</div>
				</div>
			</section>

			<section
				className="carrier-detail-card"
				aria-labelledby="live-rates-defaults-title"
			>
				<div className="carrier-detail-card-head">
					<h3 id="live-rates-defaults-title">Default behavior</h3>
					<p>
						These defaults can still be adjusted inside each
						delivery option.
					</p>
				</div>
				<div className="carrier-detail-card-body">
					<div className="carrier-detail-row">
						<div>
							<div className="carrier-detail-row-label">
								When live rates and standard shipping overlap
							</div>
							<div className="carrier-detail-row-meta">
								Customers see live carrier rates. Standard
								shipping appears only if carriers cannot return
								a rate.
							</div>
						</div>
						<div className="carrier-detail-row-value">
							Use as backup
						</div>
					</div>
					<div className="carrier-detail-row">
						<div>
							<div className="carrier-detail-row-label">
								Rate display
							</div>
							<div className="carrier-detail-row-meta">
								Controls how carrier services are grouped at
								checkout.
							</div>
						</div>
						<div className="carrier-detail-row-value">
							Show separately
						</div>
					</div>
				</div>
			</section>
		</div>
	);
}

function HubDetailColumn( { children } ) {
	return <div className="hub-detail-column">{ children }</div>;
}

// The hub content is controlled by the top-level shipping header tabs.
export default function Hub( {
	zones,
	carriers,
	productGroups,
	activeTab,
	onAddZone,
	onEditZone,
	onRenameZone,
	onDeleteZone,
} ) {
	if ( activeTab === 'zones' ) {
		return (
			<ZonesTab
				zones={ zones }
				onAddZone={ onAddZone }
				onEditZone={ onEditZone }
				onRenameZone={ onRenameZone }
				onDeleteZone={ onDeleteZone }
			/>
		);
	}

	if ( activeTab === 'live' ) {
		return (
			<HubDetailColumn>
				<LiveRateSettings carriers={ carriers } />
			</HubDetailColumn>
		);
	}

	if ( activeTab === 'packages' ) {
		return (
			<HubDetailColumn>
				<LabelsPackagesTab />
			</HubDetailColumn>
		);
	}

	if ( activeTab === 'settings' ) {
		return (
			<HubDetailColumn>
				<SettingsTab productGroups={ productGroups } />
			</HubDetailColumn>
		);
	}

	return null;
}
