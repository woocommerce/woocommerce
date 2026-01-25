type FieldTransformer = (
	setting: Record< string, unknown >,
	baseField: Record< string, unknown >
) => Record< string, unknown >;

type CreateInterpolateElement = (
	text: string,
	elements: Record< string, JSX.Element >
) => JSX.Element;

type WPComponents = {
	Card: ( props: {
		className?: string;
		isRounded?: boolean;
		children?: JSX.Element | JSX.Element[];
	} ) => JSX.Element;
	CardBody: ( props: {
		className?: string;
		children?: JSX.Element | JSX.Element[];
	} ) => JSX.Element;
	Button: ( props: {
		variant?: 'primary' | 'tertiary';
		children?: JSX.Element | string;
	} ) => JSX.Element;
};

type WPGlobal = {
	element?: {
		createInterpolateElement?: CreateInterpolateElement;
	};
	i18n?: {
		__( text: string, domain?: string ): string;
	};
	components?: WPComponents;
};

type WCSettingsGlobal = {
	admin?: {
		wcAdminAssetUrl?: string;
	};
};

type ReactSettingsRegistry = {
	registerFieldTypeTransformer?: ( type: string, transformer: FieldTransformer ) => void;
};

declare global {
	interface Window {
		wp?: WPGlobal;
		wcSettings?: WCSettingsGlobal;
		wcReactSettings?: ReactSettingsRegistry;
	}
}

const register = window.wcReactSettings?.registerFieldTypeTransformer;
const element = window.wp?.element;
const i18n = window.wp?.i18n;
const components = window.wp?.components;

if ( register && element && i18n && components ) {
	const { __ } = i18n;
	const { createInterpolateElement } = element;
	const { Card, CardBody, Button } = components;

	const assetUrl = window.wcSettings?.admin?.wcAdminAssetUrl || '';

	const HelloIncentiveBanner = ( {
		value,
		onChange,
	}: {
		value?: string;
		onChange: ( nextValue: string ) => void;
	} ) => (
		<Card className="woocommerce-incentive-banner" isRounded={ true }>
			<div className="woocommerce-incentive-banner__content">
				<div className="woocommerce-incentive-banner__image">
					<img
						src={
							assetUrl +
							'/settings-payments/incentives-illustration.svg'
						}
						alt={ __( 'Incentive illustration', 'woocommerce' ) }
					/>
				</div>
				<CardBody className="woocommerce-incentive-banner__body">
					<span className="woocommerce-status-badge woocommerce-status-badge--success">
						{ __( 'Limited time offer', 'woocommerce' ) }
					</span>

					<div className="woocommerce-incentive-banner__copy">
						<h2>
							{ __(
								'Save 10% on processing fees during your first 3 months when you sign up for WooPayments.',
								'woocommerce'
							) }
						</h2>
						<p>
							{ __(
								'Use the native payments solution built and supported by Woo to accept online and in-person payments, track revenue, and handle all payment activity in one place.',
								'woocommerce'
							) }
						</p>
					</div>

					<div className="woocommerce-incentive-banner__terms">
						{ createInterpolateElement ? createInterpolateElement(
							__( 'See <termsLink /> for details.', 'woocommerce' ),
							{
								termsLink: (
									<a
										href="https://woocommerce.com/terms-conditions/"
										target="_blank"
										rel="noreferrer"
									>
										{ __(
											'Terms and Conditions',
											'woocommerce'
										) }
									</a>
								),
							}
						) : null }
					</div>

					<div className="woocommerce-incentive-banner__actions">
						<Button variant="primary">
							{ __( 'Install and save 10%', 'woocommerce' ) }
						</Button>
						<Button variant="tertiary">
							{ __( 'Dismiss', 'woocommerce' ) }
						</Button>
					</div>
				</CardBody>
			</div>
		</Card>
	);

	register( 'incentive_field', ( _setting, baseField ) => ( {
		...baseField,
		type: 'text',
		Edit: HelloIncentiveBanner,
	} ) );
}
