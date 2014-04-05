<?php
	$t = $this;   # templater object
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
        
        <div class="navbar navbar-default" role="navigation">
          <div class="container-fluid">
      
            <div class="navbar-header">
              <button type="button" class="navbar-toggle" data-toggle="collapse"
                data-target=".navbar-collapse">
              <span class="sr-only">Toggle navigation</span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
              </button>
            </div>
      
            <div class="navbar-collapse collapse">
              <?php echo $t->menu('main_menu', 'nav navbar-nav'); ?>
              
              <ul class="nav navbar-nav navbar-right">
                <li>
                  <?php echo $t->build_search_form(); ?>
                </li>
              </ul>
            </div><!--/.nav-collapse -->
          </div><!--/.container-fluid -->
        </div><!--/.navbar -->
      </div>
      <!-- END MASTHEAD -->