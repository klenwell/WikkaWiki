<?php
/**
 * test/helpers.php
 * 
 * Helper functions for non-phpunit test (e.g. main/refactor.php)
 *
 */
 
function assert_true($assertion, $msg=null) {
    if ( $assertion ) {
        $msg = ( $msg ) ? $msg : 'assert_true passed';
        assert_success($msg);
    }
    else {
        $msg = ( $msg ) ? $msg : 'assert_true failed';
        assert_fail($msg);
    }
}

function assert_equal($val1, $val2) {
    if ( $val1 == $val2 ) {
        assert_success("value [$val1] == [$val2]");
    }
    else {
        assert_fail("value [$val1] != [$val2]");
    }
}

function assert_found($needle, $haystack) {
    if ( strpos($haystack, $needle) !== false ) {
        assert_success("value [$needle] found");
    }
    else {
        assert_fail("value [$needle] not found in:\n$haystack");
    }
}

function assert_not_found($needle, $haystack) {
    if ( strpos($haystack, $needle) === false ) {
        assert_success("value [$needle] not found");
    }
    else {
        assert_fail("value [$needle] found in:\n$haystack");
    }
}

function assert_success($message='no message') {
    $bt = debug_backtrace();
    $caller = $bt[1];
    printf("PASS: %s [%s:%s]\n", $message, basename($caller['file']), $caller['line']);
}

function assert_fail($message=null) {
    $bt = debug_backtrace();
    $caller = $bt[1];
    printf("\nASSERTION FAILED at %s:%d\n", $caller['file'], $caller['line']);
    if ( $message ) {
        print "$message\n";
    }
    print "\nTEST FAILED\n";
    exit(1);
}