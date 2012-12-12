<?php if(!class_exists('ShareYourCartBase',false)) die('Access Denied'); ?>
<?php if($this->hasNewerVersion()): //if there is a newer version, show the upgrade message?>
<div class="updated syc-update-nag">
	<p><strong><?php echo SyC::t('sdk','{link}ShareYourCart {version}</a> is available! {link}Please update now</a>.', array('{version}' => $this->getConfigValue('latest_version'), '{link}' =>  '<a href="'.$this->getConfigValue("download_url").'" target="_blank">')); ?></strong></p>
</div>
<?php endif; ?>