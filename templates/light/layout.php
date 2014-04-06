<?php

$WikkaLayout = <<<HTML5
<!DOCTYPE html>
<html lang="en">
  {{ head }}
  <body>
    <div id="page">
    
      <div id="page-header">
        {{ header }}
      </div>
      
      <div id="handler-content">
        {{ content }}
      </div>
    
      {{ footer }}
    
    </div>
    
    {{underfoot}}
    
  </body>
</html>
HTML5;
