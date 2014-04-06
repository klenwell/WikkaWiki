<?php

$WikkaLayout = <<<HTML5
<!DOCTYPE html>
<html lang="en">
  {{ head }}
  <body>
    <div class="container">
      <div id="page-header">
        {{ header }}
      </div>
      
      <div id="handler-content">
        {{ content }}
      </div>
      
      <div id="page-controls" class="navbar">
        <div class="navbar-inner-disabled">
          <div class="container">
            {{ page_controls_menu }}
          </div>
        </div>
      </div>
      
    </div>
    
    <div id="footer">
      <div class="container">
        {{ footer }}
      </div>
    </div>
    
    {{underfoot}}
    
  </body>
</html>
HTML5;
