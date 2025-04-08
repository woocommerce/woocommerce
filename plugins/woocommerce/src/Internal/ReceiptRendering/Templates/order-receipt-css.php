<?php /* phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
html { font-family: "Helvetica Neue", sans-serif; font-size: <?php echo $data['constants']['font_size']; ?>pt; }
header { margin-top: <?php echo $data['constants']['margin']; ?>; }
h1 { font-size: <?php echo $data['constants']['title_font_size']; ?>pt; font-weight: 500; text-align: center; }
h3 { color: #707070; margin:0; }
table {
	background-color:#F5F5F5;
	width:100%;
	color: #707070;
	margin: <?php echo $data['constants']['margin'] / 2; ?>pt 0;
	padding: <?php echo $data['constants']['margin'] / 2; ?>pt;
}
table td:last-child { width: 30%; text-align: right; }
table tr:last-child { color: #000000; font-weight: bold; }
footer {
	font-size: <?php echo $data['constants']['footer_font_size']; ?>pt;
	border-top: 1px solid #707070;
	margin-top: <?php echo $data['constants']['margin']; ?>pt;
	padding-top: <?php echo $data['constants']['margin']; ?>pt;
}
p { line-height: <?php echo $data['constants']['line_height']; ?>pt; margin: 0 0 <?php echo $data['constants']['margin'] / 2; ?> 0; }
<?php if ( isset( $data['payment_info'] ) ) { ?>
.card-icon {
	width: <?php echo $data['constants']['icon_width']; ?>pt;
	height: <?php echo $data['constants']['icon_height']; ?>pt;
	vertical-align: top;
	background-repeat: no-repeat;
	background-position-y: center;
	display: inline-block;
	background-image: url("data:image/svg+xml;base64,<?php echo $data['payment_info']['card_icon']; ?>");
}
<?php } ?>
