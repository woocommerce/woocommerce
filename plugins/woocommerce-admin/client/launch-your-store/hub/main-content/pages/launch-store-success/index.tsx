/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';
import { createInterpolateElement, useState } from '@wordpress/element';
import { ConfettiAnimation } from '@woocommerce/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { Button, Dashicon } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './style.scss';
import WooLogo from '~/core-profiler/components/navigation/woologo';
import type { MainContentComponentProps } from '../../xstate';
export * as actions from './actions';
export * as services from './services';

export type CongratsProps = {
	hasCompleteSurvey: boolean;
	isWooExpress: boolean;
	completeSurvey: ( surveyData: SurveyData ) => void;
	children?: React.ReactNode;
	siteIsShowingCachedContent: boolean;
};

export type SurveyData = {
	action: 'lys_experience';
	score: number | null;
	comments: string;
};
export type events =
	| {
			type: 'COMPLETE_SURVEY';
			payload: SurveyData;
	  }
	| {
			type: 'BACK_TO_HOME';
	  }
	| {
			type: 'PREVIEW_STORE';
	  };
import { WhatsNext } from './WhatsNext';
import { LysSurvey } from './Survey';
import { useFullScreen } from '~/utils';

export const LaunchYourStoreSuccess = ( {
	context: {
		congratsScreen: { activePlugins, allTasklists, hasCompleteSurvey },
		siteIsShowingCachedContent,
	},
	sendEventToMainContent,
	className,
}: MainContentComponentProps ) => {
	const copyLink = __( 'Copy link', 'woocommerce' );
	const copied = __( 'Copied!', 'woocommerce' );
	const homeUrl: string = getSetting( 'homeUrl', '' );
	const urlObject = new URL( homeUrl );
	let hostname: string = urlObject?.hostname;
	if ( urlObject?.port ) {
		hostname += ':' + urlObject.port;
	}

	const [ copyLinkText, setCopyLinkText ] = useState( copyLink );

	const copyClipboardRef = useCopyToClipboard< HTMLAnchorElement >(
		homeUrl,
		() => {
			setCopyLinkText( copied );
			setTimeout( () => {
				setCopyLinkText( copyLink );
			}, 2000 );
		}
	);

	useFullScreen( [ 'woocommerce-launch-your-store-success' ] );

	return (
		<div
			className={ clsx(
				'launch-store-success-page__container',
				className
			) }
		>
			<div className="woocommerce-launch-store__congrats">
				<ConfettiAnimation
					delay={ 1000 }
					colors={ [
						'#DFD1FB',
						'#FB79D9',
						'#FFA60E',
						'#03D479',
						'#AD86E9',
						'#7F54B3',
						'#3C2861',
					] }
				/>
				<div className="woocommerce-launch-store__congrats-header-container">
					<span className="woologo">
						<WooLogo />
					</span>
					<Button
						onClick={ () => {
							sendEventToMainContent( { type: 'BACK_TO_HOME' } );
						} }
						className="back-to-home-button"
						variant="link"
					>
						<Dashicon icon="arrow-left-alt2"></Dashicon>
						<span>{ __( 'Back to Home', 'woocommerce' ) }</span>
					</Button>
				</div>
				<div className="woocommerce-launch-store__congrats-content">
					<h1 className="woocommerce-launch-store__congrats-heading">
						{ siteIsShowingCachedContent
							? __(
									'Congratulations! Your store will launch soon',
									'woocommerce'
							  )
							: __(
									'Congratulations! Your store is now live',
									'woocommerce'
							  ) }
					</h1>
					<h2 className="woocommerce-launch-store__congrats-subheading">
						{ siteIsShowingCachedContent
							? createInterpolateElement(
									__(
										'It’ll be ready to view as soon as your <link></link> have updated. Please wait, or contact your web host to find out how to do this manually.',
										'woocommerce'
									),
									{
										link: (
											<a
												href="https://woocommerce.com/document/configuring-woocommerce-settings/coming-soon-mode/#server-caches"
												target="_blank"
												rel="noreferrer"
											>
												{ __(
													'server caches',
													'woocommerce'
												) }
											</a>
										),
									}
							  )
							: __(
									"You've successfully launched your store and are ready to start selling! We can't wait to see your business grow.",
									'woocommerce'
							  ) }
					</h2>
					<div className="woocommerce-launch-store__congrats-midsection-container">
						<div className="woocommerce-launch-store__congrats-visit-store">
							<p className="store-name">{ hostname }</p>
							<div className="buttons-container">
								<Button
									className=""
									variant="secondary"
									ref={ copyClipboardRef }
									onClick={ () => {
										recordEvent(
											'launch_your_store_congrats_copy_store_link_click'
										);
									} }
								>
									{ copyLinkText }
								</Button>
								<Button
									className=""
									variant="primary"
									onClick={ () => {
										sendEventToMainContent( {
											type: 'PREVIEW_STORE',
										} );
									} }
								>
									{ __( 'Visit your store', 'woocommerce' ) }
								</Button>
							</div>
						</div>

						<LysSurvey
							hasCompleteSurvey={ hasCompleteSurvey }
							onSubmit={ ( surveyData: SurveyData ) => {
								sendEventToMainContent( {
									type: 'COMPLETE_SURVEY',
									payload: surveyData,
								} );
							} }
						/>
					</div>
					<h2 className="woocommerce-launch-store__congrats-main-actions-title">
						{ __( "What's next?", 'woocommerce' ) }
					</h2>
					<WhatsNext
						activePlugins={ activePlugins }
						allTasklists={ allTasklists }
					/>
				</div>
			</div>
		</div>
	);
};
