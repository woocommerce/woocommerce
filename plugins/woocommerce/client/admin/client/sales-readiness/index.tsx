/**
 * External dependencies
 */
import { Breadcrumbs, Page } from '@wordpress/admin-ui';
import { Notice } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './sales-readiness.scss';
import { useReadinessView } from './use-readiness-view';
import { CheckedCard } from './components/checked-card';
import { ErrorNotice } from './components/error-notice';
import { FixFirstCard } from './components/fix-first';
import { DeclaredCard, SectionCard } from './components/section-card';

/**
 * WooCommerce > Sales readiness: when the store was last checked, Fix first, then a card per
 * merchant question with the checks from the last scan, and what the store shares with agents.
 */
export default function SalesReadiness() {
	const { view, error, reload, running, runCheck } =
		useReadinessView( 'page' );
	const [ downloadError, setDownloadError ] = useState( '' );

	return (
		<Page
			className="wc-sales-readiness"
			breadcrumbs={
				<Breadcrumbs
					items={ [
						{
							label:
								view?.title ??
								__( 'AI shopping health', 'woocommerce' ),
						},
					] }
				/>
			}
			subTitle={ view?.description }
			showSidebarToggle={ false }
		>
			<div className="wc-sales-readiness__content">
				{ error && <ErrorNotice message={ error } reload={ reload } /> }
				{ downloadError && (
					<Notice
						status="error"
						onRemove={ () => setDownloadError( '' ) }
					>
						{ downloadError }
					</Notice>
				) }
				{ view && (
					<>
						<CheckedCard
							view={ view }
							running={ running }
							onRun={ runCheck }
							onDownloadError={ setDownloadError }
						/>
						{ view.fix_first && (
							<FixFirstCard fixFirst={ view.fix_first } />
						) }
						{ view.sections.map( ( section ) => (
							<SectionCard
								key={ section.id }
								section={ section }
							/>
						) ) }
						{ view.declared && (
							<DeclaredCard declared={ view.declared } />
						) }
					</>
				) }
			</div>
		</Page>
	);
}
