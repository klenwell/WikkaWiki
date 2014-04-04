<?php
  $t = $this;
?>
	  <?php
	    # display system messages
	    if ( isset($t->message) && strlen($t->message)>0 ) {
		  printf('<div class="alert alert-success">%s</div>',
		    $t->message);
	    }
	  ?>
    
      <!-- BEGIN MASTHEAD -->
      <div class="masthead">
        <h2 class="muted">
          <?php echo $t->build_masthead(); ?>
        </h2>
        
        <div class="navbar">
          <div class="navbar-inner">
            <div class="container">
              <?php echo $t->menu('main_menu'); ?>
              <?php echo $t->build_search_form(); ?>
            </div>
          </div>
        </div>
      </div>
      <!-- END MASTHEAD -->