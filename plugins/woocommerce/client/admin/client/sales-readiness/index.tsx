/**
 * External dependencies
 */
import { Page } from '@wordpress/admin-ui';
import { Notice, Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './sales-readiness.scss';
import { useReadinessView } from './use-readiness-view';
import { CheckedCard } from './components/checked-card';
import { FixFirstCard } from './components/fix-first';
import { DeclaredCard, SectionCard } from './components/section-card';

/**
 * WooCommerce > Sales readiness: when the store was last checked, Fix first, then a card per
 * merchant question with the checks from the last scan, and what the store shares with agents.
 */
export default function SalesReadiness() {
	const { view, error, running, runCheck } = useReadinessView();

	return (
		<Page
			className="wc-sales-readiness"
			title={ view?.title ?? '' }
			subTitle={ view?.description }
			showSidebarToggle={ false }
		>
			<div className="wc-sales-readiness__content">
				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }
				{ ! view && ! error && <Spinner /> }
				{ view && (
					<>
						<CheckedCard
							view={ view }
							running={ running }
							onRun={ runCheck }
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
