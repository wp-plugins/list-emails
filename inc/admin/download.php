<?php
global $pip_list_emails;
?><h3><?php _e("Complete! Download Lists:", "listemails"); ?></h3>

<p><?php echo number_format($pip_list_emails->get_option("num_results")); ?> results.</p>

<ol>
<?php

foreach (glob($pip_list_emails->get_option("output_dir") . "/*.*") as $file):
?>
	<li><a href="<?php echo esc_attr(WP_PLUGIN_URL . "/" . plugin_basename($file)); ?>" target="_blank"><?php echo plugin_basename($file); ?></a></li>
<?php endforeach; ?>
</ol>