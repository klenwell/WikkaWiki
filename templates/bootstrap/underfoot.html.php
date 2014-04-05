<?php

    $t = $this;   # templater object

?>   
    <!-- LOGOUT FORM: to logout logged in users using logout link -->
    <?php if ( $t->get_user() ): ?>
      <?php echo $t->open_form('UserSettings', 'bootstrap-logout'); ?>
        <input type="hidden" name="logout" value="Logout" />
        <input type="hidden" name="logout-via" value="bootstrap" />
      <?php echo $t->close_form(); ?>
    <?php endif; ?>
    <!-- END LOGOUT FORM -->

    <!-- BEGIN SYSTEM INFO -->
    <?php
    if ( $t->get_config_value('sql_debugging', FALSE) ) {
      $t->output_sql_debugging();
    }
    ?>
    
    <!-- <?php echo $t->output_load_time() ?> -->
    <!-- END SYSTEM INFO -->
    
    
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script
      src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js">
    </script>
    <script
      src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js">
    </script>
    <script
      src="templates/bootstrap/js/onload.js">
    </script>
    
