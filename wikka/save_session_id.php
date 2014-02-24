<?php
/**
 * main/save_session_id.php
 * 
 * Module of main wikka.php script
 *
 * TODO: replace this module with a method within a request handling object.
 */

$user = $wakka->GetUser();

# Only store sessions for real users! 
if( NULL != $user ) {
    $query = sprintf('SELECT * FROM %ssessions WHERE sessionid="%s" AND userid="%s"',
        $wakka->config['table_prefix'],
        session_id(),
        $user['name']
    );
    
	$res = $wakka->LoadSingle($query);
	
    if ( isset($res) ) {
        # Update the session_start time
        $query = sprintf('UPDATE %ssessions SET session_start=FROM_UNIXTIME(%s) WHERE sessionid="%s" AND userid="%s"',
            $wakka->config['table_prefix'],
            $wakka->GetMicroTime(),
            session_id(),
            $user['name']
        );
	}
	else {
        # Create new session record
        $query_f = <<<SQLDOC
INSERT INTO %ssessions (sessionid, userid, session_start)
    VALUES("%s", "%s", FROM_UNIXTIME(%s))
SQLDOC;

        $query = sprintf($query_f,
            $wakka->config['table_prefix'],
            session_id(),
            $user['name'],
            $wakka->GetMicroTime()
        );
	}
    
    $wakka->Query($query);
}
