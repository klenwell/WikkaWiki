<?php
/**
 * main/functions.php
 * 
 * Module of main wikka.php script
 *
 * Globally useful functions. This should be loaded at the beginning of the
 * main wikka script.
 */
function define_constant_if_not_defined($name, $value) {
    if ( ! defined($name) ) {
        define($name, $value);
    }
}

function generate_wikka_form_id($group, $id='') {
    /*
     * This is lifted directly from Wakka::makeId. TODO: excise asap (see
     * https://github.com/klenwell/WikkaWiki/issues/19)
     */
    // initializations
    static $aSeq = array();                                        # group sequences
    static $aIds = array();                                        # used ids

    // preparation for group
    if (!preg_match('/^[A-Z-a-z]/',$group))                        # make sure group starts with a letter
    {
        $group = 'g'.$group;
    }
    if (!isset($aSeq[$group]))
    {
        $aSeq[$group] = 0;
    }
    if (!isset($aIds[$group]))
    {
        $aIds[$group] = array();
    }
    if ('embed' != $group)
    {
        $id = preg_replace('/\s+/','_',trim($id));                # replace any whitespace sequence in $id with a single underscore
    }

    // validation (full for 'embed', characters only for other groups since we'll add a prefix)
    if ('embed' == $group)
    {
        $validId = preg_match('/^[A-Za-z][A-Za-z0-9_:.-]*$/',$id);    # ref: http://www.w3.org/TR/html4/types.html#type-id
    }
    else
    {
        $validId = preg_match('/^[A-Za-z0-9_:.-]*$/',$id);
    }

    // build or generate id
    if ('' == $id || !$validId || in_array($id,$aIds))            # ignore specified id if it is invalid or exists already
    {
        $id = substr(md5($group.$id),0,ID_LENGTH);                # use group and id as basis for generated id
    }
    $idOut = ('embed' == $group) ? $id : $group.'_'.$id;        # add group prefix (unless embedded HTML)
    if (in_array($id,$aIds[$group]))
    {
        $idOut .= '_'.++$aSeq[$group];                            # add suffiX to make ID unique
    }

    // result
    $aIds[$group][] = $id;                                        # keep track of both specified and generated ids (without suffix)
    return $idOut;
}
