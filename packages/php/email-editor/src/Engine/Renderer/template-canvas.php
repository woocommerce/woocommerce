<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

// phpcs:disable Generic.Files.InlineHTML.Found
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
/**
 * Template file to render the current 'wp_template', specifcally for emails.
 *
 * Variables passed to this template:
 *
 * @var $subject string
 * @var $pre_header string
 * @var $template_html string
 * @var $meta_robots string
 * @var $layout array{contentSize: string}
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<title><?php echo esc_html( $subject ); // @phpstan-ignore-line ?></title>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="format-detection" content="telephone=no" />
	<?php echo $meta_robots; // @phpstan-ignore-line HTML defined by MailPoet--do not escape. ?>
	<!-- Forced Styles -->
</head>
<body>
	<!--[if mso | IE]><table align="center" role="presentation" border="0" cellpadding="0" cellspacing="0" width="<?php echo esc_attr( $layout['contentSize'] ); // @phpstan-ignore-line ?>" style="width:<?php echo esc_attr( $layout['contentSize'] ); // @phpstan-ignore-line ?>"><tr><td><![endif]-->
	<div class="email_layout_wrapper" style="max-width: <?php echo esc_attr( $layout['contentSize'] ); // @phpstan-ignore-line ?>">
		<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
			<tbody>
			<tr>
				<td class="email_preheader" height="1">
				<?php echo esc_html( wp_strip_all_tags( $pre_header ) ); // @phpstan-ignore-line ?>
				</td>
			</tr>
			<tr>
				<td class="email_content_wrapper">
				<?php echo $template_html; // @phpstan-ignore-line ?>
				</td>
			</tr>
			</tbody>
		</table>
	</div>
	<!--[if mso | IE]></td></tr></table><![endif]-->
</body>
</html>
