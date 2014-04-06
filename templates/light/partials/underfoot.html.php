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
