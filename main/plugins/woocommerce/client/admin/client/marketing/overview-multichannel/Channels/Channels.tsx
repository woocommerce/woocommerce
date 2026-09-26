/**
 * External dependencies
 */
import {
	Fragment,
	useState,
	forwardRef,
	useImperativeHandle,
	useRef,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardHeader,
	CardBody,
	CardDivider,
	Button,
	Icon,
} from '@wordpress/components';
import { chevronUp, chevronDown } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { RecommendedChannel } from '~/marketing/data-multichannel/types';
import {
	CardHeaderTitle,
	CardHeaderDescription,
	SmartPluginCardBody,
} from '~/marketing/components';
import { RegisteredChannel } from '~/marketing/types';
import { RegisteredChannelCardBody } from './RegisteredChannelCardBody';
import './Channels.scss';

type ChannelsProps = {
	registeredChannels: Array< RegisteredChannel >;
	recommendedChannels: Array< RecommendedChannel >;
	onInstalledAndActivated?: ( pluginSlug: string ) => void;
};

export type ChannelsRef = {
	/**
	 * Scroll into the "Add channels" section in the card.
	 * The section will be expanded, and the "Add channels" button will be in focus.
	 */
	scrollIntoAddChannels: () => void;
};

export const Channels = forwardRef< ChannelsRef, ChannelsProps >(
	(
		{ registeredChannels, recommendedChannels, onInstalledAndActivated },
		ref
	) => {
		const hasRegisteredChannels = registeredChannels.length >= 1;

		/**
		 * State to collapse / expand the recommended channels.
		 * Initial state is expanded if there are no registered channels in first page load.
		 */
		const [ expanded, setExpanded ] = useState( ! hasRegisteredChannels );
		const addChannelsButtonRef = useRef< HTMLButtonElement >( null );

		useImperativeHandle(
			ref,
			() => ( {
				scrollIntoAddChannels: () => {
					setExpanded( true );
					addChannelsButtonRef.current?.focus();
					addChannelsButtonRef.current?.scrollIntoView( {
						block: 'center',
					} );
				},
			} ),
			[]
		);

		return (
			<Card className="woocommerce-marketing-channels-card">
				<CardHeader>
					<CardHeaderTitle>
						{ __( 'Channels', 'woocommerce' ) }
					</CardHeaderTitle>
					{ ! hasRegisteredChannels && (
						<CardHeaderDescription>
							{ __(
								'Start by adding a channel to your store',
								'woocommerce'
							) }
						</CardHeaderDescription>
					) }
				</CardHeader>

				{ /* Registered channels section. */ }
				{ registeredChannels.map( ( el, idx ) => (
					<Fragment key={ el.slug }>
						<RegisteredChannelCardBody registeredChannel={ el } />
						{ idx !== registeredChannels.length - 1 && (
							<CardDivider />
						) }
					</Fragment>
				) ) }

				{ /* Recommended channels section. */ }
				{ recommendedChannels.length >= 1 && (
					<div>
						{ hasRegisteredChannels && (
							<>
								<CardDivider />
								<CardBody>
									<Button
										ref={ addChannelsButtonRef }
										variant="link"
										onClick={ () =>
											setExpanded( ! expanded )
										}
									>
										{ __( 'Add channels', 'woocommerce' ) }
										<Icon
											icon={
												expanded
													? chevronUp
													: chevronDown
											}
											size={ 24 }
										/>
									</Button>
								</CardBody>
							</>
						) }
						{ expanded &&
							recommendedChannels.map( ( el, idx ) => (
								<Fragment key={ el.plugin }>
									<SmartPluginCardBody
										plugin={ el }
										onInstalledAndActivated={
											onInstalledAndActivated
										}
									/>
									{ idx !==
										recommendedChannels.length - 1 && (
										<CardDivider />
									) }
								</Fragment>
							) ) }
					</div>
				) }
			</Card>
		);
	}
);
