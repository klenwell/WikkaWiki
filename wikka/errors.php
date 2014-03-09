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
class WikkaError extends Exception {
    public function __construct($message, $code=0) {
        parent::__construct($message, $code);
    }

    public function __toString() {
        return sprintf("%s: %s\n", __CLASS__, $this->message);
    }
}


class WikkaWebServiceError extends WikkaError {
    public $status_code = 0;
    public $exit_code = 1;
    
    # http://stackoverflow.com/a/3914021/1093087
    public $http_codes = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        449 => 'Retry With',
        450 => 'Blocked by Windows Parental Controls',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended'
    );
    
    public function __construct($message, $status_code=0, $exit_code=1) {
        parent::__construct($message, $status_code);
    }

    public function __toString() {
        return sprintf("%s: %s\n", __CLASS__, $this->message);
    }
    
    public function send_header() {
        if ( isset($this->http_codes[$this->status_code]) ) {
            $status_code = $this->status_code;
        }
        else {
            $status_code = 500;
        }
        
        # http://stackoverflow.com/a/12018482/1093087
        $header = sprintf('X-PHP-Response-Code: %d', $status_code);
        header($header, true, $status_code);
    }
    
    public function terminate() {
        exit($this->exit_code);
    }
}
