<script type="text/javascript">
  if(_gaq) _gaq.push(['_trackPageview', '/admin/create-view']);
</script>
<?php echo SyC::t('sdk','Create a ShareYourCart account'); ?>
<form method="POST">
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
<input class="buttonCheckbox" name="syc-terms-agreement" id="syc-terms-agreement" <?php if( $_SERVER['REQUEST_METHOD'] !== 'POST' || isset($_POST['syc-terms-agreement'])){ echo 'checked="checked"'; } ?> type="checkbox" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin/create-view/toogle-terms-agreement-click']);"><label for="syc-terms-agreement"><?php echo SyC::t('sdk','I agree to create the account on {brand}',array('{brand}' => '<a href="http://www.shareyourcart.com" target="_blank" onclick=" if(_gaq) _gaq.push([\'_trackPageview\', \'/admin/create-view/logo-terms-click\']);">www.shareyourcart.com</a>')); ?></label>
</td>
</tr>
<tr>
<td colspan="2">
<input type="submit" name="syc-create-account" value="<?php echo SyC::t('sdk','Create account'); ?>" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin/create-view/save-click']);"/>
</td>
</tr>
</table>
<?php echo $html;?>
</form>