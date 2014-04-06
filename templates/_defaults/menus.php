<?php

$WikkaMenus = array(
    'main_menu' => array(
        'admin' => array(
            '[[CategoryCategory Categories]]',
            'PageIndex',
            'RecentChanges',
            'RecentlyCommented',
            '[[UserSettings Settings]]',
            'You are {{whoami}}',
            '{{searchform}}',
            '{{logout}}'
        ),
        'user' => array(
            '[[CategoryCategory Categories]]',
            'PageIndex',
            'RecentChanges',
            'RecentlyCommented',
            '[[UserSettings Settings]]',
            'You are {{whoami}}',
            '{{searchform}}',
            '{{logout}}'
        ),
        'default' => array(
            '[[CategoryCategory Categories]]',
            'PageIndex',
            'RecentChanges',
            'RecentlyCommented',
            '[[UserSettings Login/Register]]',
            '{{searchform}}'
        )
    ),
    
    'dashboard' => array(
        'admin' => array(
            'AdminUsers',
            'AdminPages',
            'SysInfo',
            'WikkaConfig'
        )
    ),
    
    'options_menu' => array(
        'admin' => array(
            '{{editlink}}',
            '{{revertlink}}',
            '{{deletelink}}',
            '{{clonelink}}',
            '{{historylink}}',
            '{{revisionlink}}',
            '{{ownerlink}}',
            '{{referrerslink}}'
        ),
        'user' => array(
            '{{editlink}}',
            '{{clonelink}}',
            '{{historylink}}',
            '{{revisionlink}}',
            '{{ownerlink}}'
        ),
        'default' => array(
            '{{editlink}}',
            '{{historylink}}',
            '{{revisionlink}}',
            '{{ownerlink}}',
            'Your hostname is {{whoami}}'
        ),
    ),
);