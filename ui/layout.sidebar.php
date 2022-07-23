
        <nav class="page-sidebar" data-pages="sidebar">
            <div class="sidebar-header">
                <?php $banner_text_class = 'text-white'; $app_logo = 'app_logo_2.png';  $brand_id = 'banner2'; require INDEX . "ui/layout.banner.php"; ?>
            </div>
            <div class="sidebar-menu">
                <ul class="menu-items p-t-15"><?php ret(1);
                    for($i=0; $i<sizeof($arr_menu_items); $i++) {
                        $arr_attr_tabs = array();
                        $arr_tabs = $arr_menu_items[$i]['tabs'];

                        // check if all tabs are accessible by the user
                        $arr_accessible_tabs = array();
                        for($j=0; $j<sizeof($arr_tabs); $j++) {
                            if(in_array($arr_tabs[$j]['tab_text'], $arr_admin_details['user_type_access']) || ($arr_tabs[$j]['for_devs_only'] && $arr_admin_details['citizen_id'] == 1)) {
                                array_push($arr_accessible_tabs, $arr_tabs[$j]['tab_text']);
                            }
                        }

                        if(sizeof($arr_accessible_tabs) > 0) {
                            for($j=0; $j<sizeof($arr_tabs); $j++) {
                                if(in_array($arr_tabs[$j]['tab_text'], $arr_accessible_tabs)) {
                                    array_push($arr_attr_tabs, array(
                                        'icon'        => $arr_tabs[$j]['tab_icon'],
                                        'title'       => $arr_tabs[$j]['tab_text'],
                                        'model'       => $arr_tabs[$j]['model'],
                                        'active_item' => (($arr_menu_items[$i]['href'] == $page && ($j+1) == $tab) ? $item : -1),
                                        'controls'    => $arr_tabs[$j]['controls'],
                                        'is_active'   => ($arr_menu_items[$i]['default-tab'] == ($j+1))
                                    ));
                                }
                            }
                            tab(5); echo "<li class='cursor-pointer sidebar-menu-item no-selection" . $arr_menu_items[$i]['status'] . "' data-href='" . $arr_menu_items[$i]['href'] . "' data-tabs='" . json_encode($arr_attr_tabs) . "'>"; ret(1);
                            tab(6); echo "<a class='sidebar-menu-item-title' href='?" . $arr_menu_items[$i]['href'] . "'>" . $arr_menu_items[$i]['name'] . "</a>"; ret(1);
                            tab(6); echo "<span class='icon-thumbnail border-radius-2'><span class='" . $arr_menu_items[$i]['icon'] . "'></span></span>"; ret(1);
                            tab(5); echo "</li>"; ret(1);
                        }
                    }
                    tab(5); echo "<li class='cursor-pointer no-selection'>"; ret(1);
                    tab(6); echo "<a class='sidebar-menu-item-title text-danger' href='backup'>DATA BACKUP</a>"; ret(1);
                    tab(6); echo "<span class='icon-thumbnail border-radius-2 bg-master-darker'><a class='fa fa-download fa-fw text-danger' href='backup'></a></span>"; ret(1);
                    tab(5); echo "</li>"; ret(1);

                    tab(4); ?></ul>
                <div class="clearfix"></div>
            </div>
        </nav>
