<?php if ( ! class_exists( 'ShareYourCartBase', false ) ) die( 'Access Denied' ); ?>

<?php echo SyC::t('sdk','Create a ShareYourCart account'); ?>

<form method="post">
	<table>
		<tr>
			<td>
				<label for="domain"><?php echo SyC::t('sdk','Domain:'); ?></label>
			</td>
			<td>
				<input type="text" name="domain" id="domain" class="regular-text" value="<?php echo (isset($_POST['domain']) ? $_POST['domain'] : $this->getDomain()); ?>"/>
			</td>
		</tr>
		<tr>
			<td>
				<label for="email"><?php echo SyC::t('sdk','Email:'); ?></label>
			</td>
			<td>
				<input type="text" name="email" id="email" class="regular-text" value="<?php echo (isset($_POST['email']) ? $_POST['email'] : $this->getAdminEmail()); ?>"/>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input class="buttonCheckbox" name="syc-terms-agreement" id="syc-terms-agreement" <?php if( $_SERVER['REQUEST_METHOD'] !== 'POST' || isset($_POST['syc-terms-agreement'])){ echo 'checked="checked"'; } ?> type="checkbox"><label for="syc-terms-agreement"><?php echo SyC::t('sdk','I agree with the {brand} terms & conditions',array('{brand}' => '<a href="http://www.shareyourcart.com/terms" target="_blank">ShareYourCart</a>')); ?></label>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="submit" class="button" name="syc-create-account" value="<?php echo SyC::t('sdk','Create account'); ?>" />
			</td>
		</tr>
	</table>
</form>