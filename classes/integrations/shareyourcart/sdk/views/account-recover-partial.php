<script type="text/javascript">
  if(_gaq) _gaq.push(['_trackPageview', '/admin/recover-view']);
</script>
<?php echo SyC::t('sdk','Enter the domain and email you used when you created the ShareYourCart account'); ?>
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
<input type="submit" name="syc-recover-account" value="<?php echo SyC::t('sdk','Recover my account'); ?>" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin/recover-view/save-click']);" />
</td>
</tr>
</table>
<?php echo $html;?>
</form>