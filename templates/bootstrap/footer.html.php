<?php

    $t = $this;   # templater object

?>

        <p>
          Template theme built with <?php echo $t->link(
            'http://twitter.github.io/bootstrap/', 'Bootstrap'); ?>
        </p>
        <p>
          Powered by <?php echo $t->link('http://wikkawiki.org/',
            T_("WikkaWiki")); ?>
          <?php 
            if ( $t->is_admin() ) {
                sprintf('v%s', $t->get_wikka_version());
            }
          ?>
        </p>
        <ul class="footer-links">
          <li>
            <?php echo $t->link(
                'http://validator.w3.org/check/referer',
                T_("Valid XHTML")); ?>
          </li>
          <li class="muted">&middot;</li>
          <li>
            <?php echo $t->link(
                'http://jigsaw.w3.org/css-validator/check/referer',
                T_("Valid CSS")); ?>
          </li>
        </ul>
