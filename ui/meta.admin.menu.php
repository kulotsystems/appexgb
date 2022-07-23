<?php

    $arr_menu_items = array(
        array(
            'name'        => 'HOME',
            'href'        => 'home',
            'status'      => '',
            'default-tab' => '1',
            'icon'        => 'fa fa-home fa-fw',
            'tabs'        => array(
                array(
                    'tab_icon'      => 'fa fa-th-large',
                    'tab_text'      => 'DASHBOARD',
                    'desc'          => 'View the system dashboard.',
                    'model'         => 'Dashboard',
                    'controls'      => array(),
                    'for_devs_only' => false
                ),
                array(
                    'tab_icon'      => 'fas fa-layer-group fa-fw',
                    'tab_text'      => 'UPDATES',
                    'desc'          => 'View the updates posted by the developers.',
                    'model'         => 'Update',
                    'controls'      => array('list', 'select'),
                    'for_devs_only' => false
                )
            )
        ),
        array(
            'name'        => 'AREAS',
            'href'        => 'areas',
            'status'      => '',
            'default-tab' => '1',
            'icon'        => 'fa fa-map fa-fw',
            'tabs'        => array(
                array(
                    'tab_icon'      => 'fa fa-map-marker fa-fw',
                    'tab_text'      => 'AREAS',
                    'desc'          => 'Manage areas.',
                    'model'         => 'Area',
                    'controls'      => array('list', 'create', 'select', 'update', 'delete', 'search'),
                    'for_devs_only' => false
                )
            )
        ),
        array(
            'name'        => 'LOANS',
            'href'        => 'loans',
            'status'      => '',
            'default-tab' => '1',
            'icon'        => 'fa fa-money-bill-wave fa-fw',
            'tabs'        => array(
                array(
                    'tab_icon'      => 'fa fa-coins fa-fw',
                    'tab_text'      => 'RELEASE',
                    'desc'          => 'Manage release.',
                    'model'         => 'Release',
                    'controls'      => array('list', 'create', 'select', 'update', 'delete', 'search'),
                    'for_devs_only' => false
                ),
                array(
                    'tab_icon'      => 'fa fa-clipboard-list fa-fw',
                    'tab_text'      => 'OFFERS',
                    'desc'          => 'Manage offers.',
                    'model'         => 'Offer',
                    'controls'      => array('list', 'create', 'select', 'update', 'delete', 'search'),
                    'for_devs_only' => false
                )
            )
        ),
        array(
            'name'        => 'COLLECTIONS',
            'href'        => 'collections',
            'status'      => '',
            'default-tab' => '1',
            'icon'        => 'fa fa-piggy-bank fa-fw',
            'tabs'        => array(
                array(
                    'tab_icon'      => 'fa fa-thumbtack fa-fw',
                    'tab_text'      => 'POSTINGS',
                    'desc'          => 'Manage postings.',
                    'model'         => 'Posting',
                    'controls'      => array('list', 'select', 'update', 'search', 'print'),
                    'for_devs_only' => false
                )
            )
        ),
        array(
            'name'        => 'OUTPUTS',
            'href'        => 'reports',
            'status'      => '',
            'default-tab' => '1',
            'icon'        => 'fa fa-copy fa-fw',
            'tabs'        => array(
                array(
                    'tab_icon'      => 'fa fa-copy fa-fw',
                    'tab_text'      => 'REPORTS',
                    'desc'          => 'Manage reports.',
                    'model'         => 'Report',
                    'controls'      => array('list', 'select', 'search', 'print'),
                    'for_devs_only' => false
                )
            )
        ),
        array(
            'name'        => 'PEOPLE',
            'href'        => 'people',
            'status'      => '',
            'default-tab' => '1',
            'icon'        => 'fa fa-user fa-fw',
            'tabs'        => array(
                array(
                    'tab_icon'      => 'fa fa-user-circle fa-fw',
                    'tab_text'      => 'CITIZENS',
                    'desc'          => 'View citizens.',
                    'model'         => 'PhCitizen',
                    'controls'      => array('list', 'create', 'select', 'update', 'delete', 'search'),
                    'for_devs_only' => false
                ),
                array(
                    'tab_icon'      => 'fa fa-tags fa-fw',
                    'tab_text'      => 'USER TYPES',
                    'desc'          => 'Manage settings for user types.',
                    'model'         => 'UserType',
                    'controls'      => array('list', 'create', 'select', 'update', 'delete', 'search'),
                    'for_devs_only' => false
                ),
                array(
                    'tab_icon'      => 'fa fa-user fa-fw',
                    'tab_text'      => 'USER ACCOUNTS',
                    'desc'          => 'Manage settings for user accounts.',
                    'model'         => 'UserAccount',
                    'controls'      => array('list', 'create', 'select', 'update', 'delete', 'search'),
                    'for_devs_only' => false
                ),
            )
        ),
        array(
            'name'        => 'SYSTEM LOGS',
            'href'        => 'systemlogs',
            'status'      => '',
            'default-tab' => '1',
            'icon'        => 'fa fa-desktop fa-fw',
            'tabs'        => array(
                array (
                    'tab_icon'      => 'fa fa-desktop fa-fw',
                    'tab_text'      => 'ACTIVITY LOGS',
                    'desc'          => 'Access activity logs.',
                    'model'         => 'ActivityLog',
                    'controls'      => array('list', 'select', 'search'),
                    'for_devs_only' => false
                )
            )
        )
    );

?>