<?php
/**
 * wikka/errors.php
 *
 * Wikka error classes.
 *
 * @package     Wikka
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @author      {@link https://github.com/klenwell/WikkaWiki Tom Atwell}
 * @copyright   Copyright 2014  Tom Atwell <klenwell@gmail.com>
 *
 * REFERENCES
 * http://php.net/manual/en/language.exceptions.extending.php
 *
 */
#
# Errors
#
class WikkaError extends Exception {
    public function __construct($message, $code=0) {
        parent::__construct($message, $code);
    }

    public function __toString() {
        return sprintf("%s: %s\n", __CLASS__, $this->message);
    }
}

class WikkaWebServiceError extends WikkaError {}

class WikkaCsrfError extends WikkaError {}

class WikkaHandlerError extends WikkaError {}

class WikkaInstallerError extends WikkaError {
    public function render_solution() { /* Interface Method */ }
}

class ConfigDirWriteError extends WikkaInstallerError {    
    public function render_solution($installer) {
        $html_f = <<<XHTML
        <p>There are two possible solutions:</p>
        
        <dl class="dl-horizontal">
          <dt>1.</dt>
            <dd>
              <p>Give your web server temporary write access to the <tt>config</tt>
              directory:</p>
              <pre>chmod -v 777 config</pre>
              
              <p>When done, change it back:</p>
              <pre>chmod -v 755 config</pre>
              
              <p>When ready, click:</p>
              %s
            </dd>
        </dl>
        
        <dl class="dl-horizontal">
          <dt>2.</dt>
            <dd>
              <p>Edit the menu files in the <tt>config</tt> directory. You will
              also need to remove the <tt>navigation_links</tt> and
              <tt>logged_in_navigation_links</tt> parameters in your
              <tt>wikka.config.php</tt> file.</p>
              
              <p>When ready, click:</p>
              %s
            </dd>
        </dl>
        
        <p>For more information, please visit <a href="%s">%s</a></p>
XHTML;

        return sprintf($html_f,
            $installer->next_stage_button('save_config_file', 'Try Again'),
            $installer->next_stage_button('save_config_file', 'Continue', 'warning'),
            WIKKA_INSTALL_DOCS_URL, WIKKA_INSTALL_DOCS_URL);
    }
}

class ConfigFileWriteError extends WikkaInstallerError {
    public function __construct($message, $file_contents, $code=0) {
        $this->file_contents = $file_contents;
        parent::__construct($message, $code);
    }
    
    public function render_solution($installer) {
        $html_f = <<<XHTML
        <p>There are two possible solutions:</p>
        
        <dl class="dl-horizontal">
          <dt>1.</dt>
            <dd>
              <p>Give your web server temporary write access to the config file:</p>
              <pre>touch %s ; chmod -v 666 %s</pre>
              
              <p>When done, change it back:</p>
              <pre>chmod -v 644 %s</pre>
              
              <p>When ready, click:</p>
              %s
            </dd>
        </dl>
        
        <dl class="dl-horizontal">
          <dt>2.</dt>
            <dd>
              <p>Copy the text below into a new file and save/upload it as
                <tt>%s</tt> within the Wikka directory. Once you've done this,
                your Wikka site should work.</p>
                
              <textarea class="form-control" rows="8">%s</textarea>

              
              <p>When the config file is saved, click:</p>
              %s
            </dd>
        </dl>
        
        <p>For more information, please visit <a href="%s">%s</a></p>
XHTML;

        return sprintf($html_f,
            WIKKA_CONFIG_PATH, WIKKA_CONFIG_PATH,
            WIKKA_CONFIG_PATH,
            $installer->next_stage_button('save_config_file', 'Try Again'),
            WIKKA_CONFIG_PATH,
            $this->file_contents,
            $installer->next_stage_button('conclusion', 'Finish', 'warning'),
            WIKKA_INSTALL_DOCS_URL, WIKKA_INSTALL_DOCS_URL);
    }
}


#
# Exceptions and Flags
#
class WikkaInstallInterrupt extends Exception {}
