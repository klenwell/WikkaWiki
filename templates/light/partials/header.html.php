<?php

	$t = $this;   # templater object

?>
	  <?php echo $t->show_flash_message_if_set(); ?>
    
      <!-- BEGIN PAGE HEADER -->
      <div id="header">
        <h2>
          <?php echo $t->build_masthead(); ?>
        </h2>
		
		<?php echo $t->menu('main_menu', 'menu', 'main_menu'); ?>
      </div>
	  
	  <?php
		if ( $t->is_admin() ) {
			echo $t->menu('dashboard', 'menu', 'dashboard');
		}
	  ?>
      <!-- END PAGE HEADER -->