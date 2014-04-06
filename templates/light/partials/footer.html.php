<?php

    $t = $this;   # templater object

    $wikka_patch_level = $t->wikka->GetWikkaPatchLevel();
    
    if ( $wikka_patch_level == '0' ) {
        $wikka_patch_level = '';
    }
    else {
        $wikka_patch_level = sprintf('-p%s', $wikka_patch_level);
    }
    
    if ( ! $t->is_admin() ) {
        $wikka_version = 'WikkaWiki';
    }
    else {
        $wikka_version = sprintf('WikkaWiki %s%s',
            $t->wikka->GetWakkaVersion(),
            $wikka_patch_level
        );
    }
    
    $footer = array(
      'xhtml_link' => $t->wikka->Link('http://validator.w3.org/check/referer', '',
          T_("Valid XHTML")),
      'css_link' => $t->wikka->Link('http://jigsaw.w3.org/css-validator/check/referer',
          '', T_("Valid CSS:")),
      'wikka_link' => $t->wikka->Link('http://wikkawiki.org/', '',
          sprintf(T_("Powered by %s"), $wikka_version))
    );

?>
      <div id="footer">
        <?php echo $t->menu('options_menu', 'menu', 'options_menu'); ?>
      </div>
      
      <div id="smallprint">
        <?php echo $footer['xhtml_link']; ?> ::
        <?php echo $footer['css_link']; ?> ::
        <?php echo $footer['wikka_link']; ?>
      </div>
