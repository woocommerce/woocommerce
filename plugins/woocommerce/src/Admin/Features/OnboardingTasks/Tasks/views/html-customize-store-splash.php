<?php
/**
 * Customize Store Splash Page Template
 *
 * @package WooCommerce\Admin\Features\OnboardingTasks\Tasks\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap woocommerce-customize-store-splash">
	<div class="woocommerce-customize-store-splash__container">
		<div class="woocommerce-customize-store-splash__header">
			<h1 class="woocommerce-customize-store-splash__title">
				<?php esc_html_e( 'Customize your store', 'woocommerce' ); ?>
			</h1>
			<p class="woocommerce-customize-store-splash__description">
				<?php
				esc_html_e(
					'Design a store that reflects your brand and business. Customize your active theme, select a professionally designed theme, or create a new look using our store designer.',
					'woocommerce'
				);
				?>
			</p>
		</div>

		<div class="woocommerce-customize-store-splash__banners">
			<!-- Design Your Own Banner -->
			<div class="woocommerce-customize-store-splash__banner woocommerce-customize-store-splash__banner--design">
				<div class="woocommerce-customize-store-splash__banner-content">
					<h2 class="woocommerce-customize-store-splash__banner-title">
						<?php esc_html_e( 'Design your own', 'woocommerce' ); ?>
					</h2>
					<p class="woocommerce-customize-store-splash__banner-text">
						<?php
						esc_html_e(
							'Quickly create a beautiful store using our built-in store designer. Choose your layout, select a style, and much more.',
							'woocommerce'
						);
						?>
					</p>
					<a href="<?php echo esc_url( $design_url ); ?>" class="button button-primary button-large">
						<?php esc_html_e( 'Start designing', 'woocommerce' ); ?>
					</a>
				</div>
			</div>

			<!-- Pick Your Perfect Theme Banner -->
			<div class="woocommerce-customize-store-splash__banner woocommerce-customize-store-splash__banner--theme">
				<div class="woocommerce-customize-store-splash__banner-content">
					<h2 class="woocommerce-customize-store-splash__banner-title">
						<?php esc_html_e( 'Pick your perfect theme', 'woocommerce' ); ?>
					</h2>
					<p class="woocommerce-customize-store-splash__banner-text">
						<?php
						esc_html_e(
							'Bring your vision to life — no coding required. Explore hundreds of free and paid ecommerce-optimized themes.',
							'woocommerce'
						);
						?>
					</p>
					<ul class="woocommerce-customize-store-splash__banner-features">
						<li><?php esc_html_e( 'Themes for every industry', 'woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Ready to use out of the box', 'woocommerce' ); ?></li>
						<li><?php esc_html_e( '30-day money-back guarantee', 'woocommerce' ); ?></li>
					</ul>
					<a href="<?php echo esc_url( $marketplace_url ); ?>" class="button button-primary button-large">
						<?php esc_html_e( 'Browse the Marketplace', 'woocommerce' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
.woocommerce-customize-store-splash {
	max-width: 1200px;
	margin: 20px auto;
	padding: 0 20px;
}

.woocommerce-customize-store-splash__container {
	background: #fff;
}

.woocommerce-customize-store-splash__header {
	margin-bottom: 40px;
	padding-bottom: 30px;
	border-bottom: 1px solid #ddd;
}

.woocommerce-customize-store-splash__title {
	font-size: 32px;
	font-weight: 600;
	margin: 0 0 16px 0;
	color: #1d2327;
}

.woocommerce-customize-store-splash__description {
	font-size: 16px;
	line-height: 1.6;
	color: #50575e;
	margin: 0;
}

.woocommerce-customize-store-splash__banners {
	display: grid;
	grid-template-columns: 1fr;
	gap: 24px;
}

@media (min-width: 782px) {
	.woocommerce-customize-store-splash__banners {
		grid-template-columns: repeat(2, 1fr);
	}
}

.woocommerce-customize-store-splash__banner {
	background: #f6f7f7;
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 32px;
	transition: box-shadow 0.2s ease;
}

.woocommerce-customize-store-splash__banner:hover {
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.woocommerce-customize-store-splash__banner-content {
	display: flex;
	flex-direction: column;
	height: 100%;
}

.woocommerce-customize-store-splash__banner-title {
	font-size: 24px;
	font-weight: 600;
	margin: 0 0 12px 0;
	color: #1d2327;
}

.woocommerce-customize-store-splash__banner-text {
	font-size: 15px;
	line-height: 1.6;
	color: #50575e;
	margin: 0 0 20px 0;
	flex-grow: 1;
}

.woocommerce-customize-store-splash__banner-features {
	list-style: none;
	margin: 0 0 20px 0;
	padding: 0;
}

.woocommerce-customize-store-splash__banner-features li {
	font-size: 14px;
	line-height: 1.8;
	color: #50575e;
	padding-left: 24px;
	position: relative;
	margin-bottom: 8px;
}

.woocommerce-customize-store-splash__banner-features li::before {
	content: "✓";
	position: absolute;
	left: 0;
	color: #2271b1;
	font-weight: 600;
}

.woocommerce-customize-store-splash__banner .button {
	align-self: flex-start;
	margin-top: auto;
}
</style>

