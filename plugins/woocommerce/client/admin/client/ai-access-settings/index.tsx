/**
 * External dependencies
 */
import { Notice, RadioControl, ToggleControl } from '@wordpress/components';
import {
	createElement,
	createInterpolateElement,
	useEffect,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import { isFeatureEnabled } from '~/utils/features';
import './style.scss';

export const ANSWERS_OPTION = 'woocommerce_ai_answers_allowed';
export const TRAINING_OPTION = 'woocommerce_ai_training_allowed';

type YesNo = 'yes' | 'no';

export type AiAccessStatus =
	'default' | 'robots_file' | 'jetpack' | 'other_plugin';

export type AiAccessData = {
	[ ANSWERS_OPTION ]?: YesNo;
	[ TRAINING_OPTION ]?: YesNo;
	status?: AiAccessStatus;
	aiShoppingEnabled?: boolean;
	aiShoppingUrl?: string;
	jetpackUrl?: string;
};

type Props = {
	/**
	 * Called when the values differ, or stop differing, from the saved ones.
	 */
	onDirtyChange?: ( isDirty: boolean ) => void;
};

type SectionNotice = {
	status: 'warning' | 'info';
	content: ReturnType< typeof createInterpolateElement > | string;
};

/**
 * The notice that replaces the controls when WooCommerce's settings aren't the ones in effect.
 */
const getNotice = (
	status: AiAccessStatus,
	data: AiAccessData
): SectionNotice | null => {
	switch ( status ) {
		case 'robots_file':
			return {
				status: 'warning',
				content: __(
					'These settings aren’t being used. Your site has its own robots.txt file, so WooCommerce can’t add to it. Remove the file, or ask whoever set it up to add these preferences.',
					'woocommerce'
				),
			};
		case 'jetpack':
			return {
				status: 'info',
				content: createInterpolateElement(
					__(
						'Jetpack manages AI access for your site. <a>Change it in Jetpack</a>.',
						'woocommerce'
					),
					{ a: createElement( 'a', { href: data.jetpackUrl } ) }
				),
			};
		case 'other_plugin':
			return {
				status: 'warning',
				content: __(
					'These settings aren’t being used. Another plugin already sets AI preferences for your site. Change them there, or turn that setting off to use these.',
					'woocommerce'
				),
			};
		default:
			return null;
	}
};

/**
 * The AI access settings section. It reads its values from wcSettings.admin.aiAccessSettings and posts
 * them with the surrounding settings form through hidden inputs.
 */
export const AiAccessSettings = ( { onDirtyChange }: Props ) => {
	const data: AiAccessData = getAdminSetting( 'aiAccessSettings', {} );
	const savedAnswers = data[ ANSWERS_OPTION ] ?? 'yes';
	const savedTraining = data[ TRAINING_OPTION ] ?? 'no';
	const status = data.status ?? 'default';

	const [ answers, setAnswers ] = useState< YesNo >( savedAnswers );
	const [ training, setTraining ] = useState< YesNo >( savedTraining );

	const isDirty = answers !== savedAnswers || training !== savedTraining;
	useEffect( () => {
		onDirtyChange?.( isDirty );
	}, [ isDirty, onDirtyChange ] );

	if ( ! isFeatureEnabled( 'agent_readiness' ) || ! data.status ) {
		return null;
	}

	// A robots.txt file, Jetpack or another plugin decides instead, so a notice replaces the controls:
	// disabled controls would read as the site's settings. Nothing is posted, so saving the page leaves
	// the stored settings as they are.
	const notice = getNotice( status, data );
	if ( notice ) {
		return (
			<div className="woocommerce-ai-access-settings">
				<h2>{ __( 'AI access', 'woocommerce' ) }</h2>
				<Notice
					status={ notice.status }
					isDismissible={ false }
					className="woocommerce-ai-access-settings__notice"
				>
					{ notice.content }
				</Notice>
			</div>
		);
	}

	const showAiShoppingNotice =
		answers === 'no' && data.aiShoppingEnabled === true;

	return (
		<div className="woocommerce-ai-access-settings">
			<input type="hidden" name={ ANSWERS_OPTION } value={ answers } />
			<input type="hidden" name={ TRAINING_OPTION } value={ training } />
			<h2>{ __( 'AI access', 'woocommerce' ) }</h2>
			<p className="woocommerce-ai-access-settings__intro">
				{ __(
					'These settings apply to your whole site. They ask AI companies to respect your choices.',
					'woocommerce'
				) }
			</p>
			<div className="woocommerce-ai-access-settings__field">
				<ToggleControl
					__nextHasNoMarginBottom
					label={
						<>
							{ __(
								'Allow AI assistants to use your content in answers',
								'woocommerce'
							) }
							<p>
								{ __(
									'AI assistants can read your pages to answer questions and recommend your products.',
									'woocommerce'
								) }
							</p>
						</>
					}
					checked={ answers === 'yes' }
					onChange={ ( enabled ) =>
						setAnswers( enabled ? 'yes' : 'no' )
					}
				/>
				{ showAiShoppingNotice && (
					<Notice
						status="info"
						isDismissible={ false }
						className="woocommerce-ai-access-settings__notice"
					>
						{ createInterpolateElement(
							__(
								'AI shopping is on, so AI assistants can still get your product details. Turn it off in <a>AI tools</a>.',
								'woocommerce'
							),
							{
								a: createElement( 'a', {
									href: data.aiShoppingUrl,
								} ),
							}
						) }
					</Notice>
				) }
			</div>
			<div className="woocommerce-ai-access-settings__field">
				<RadioControl
					label={ __( 'AI training', 'woocommerce' ) }
					selected={ training }
					options={ [
						{
							label: __(
								'Don’t allow AI training',
								'woocommerce'
							),
							value: 'no',
							description: __(
								'Asks AI companies not to use your pages to train their models.',
								'woocommerce'
							),
						},
						{
							label: __( 'Allow AI training', 'woocommerce' ),
							value: 'yes',
							description: __(
								'AI companies can use your pages to train their models.',
								'woocommerce'
							),
						},
					] }
					onChange={ ( value ) => setTraining( value as YesNo ) }
				/>
			</div>
		</div>
	);
};
