<?php

    define('INDEX', '../');
    $dir  = 'admin';
    $page = '';
    $tab  =   1;
    $item = -1;

    require INDEX . "php/__init.php";
    foreach ($_GET as $page => $val) {
        break;
    }
    $arr_page = explode("-", $page);
    if(sizeof($arr_page) > 1) {
        $page = $arr_page[0];
        $tab  = $arr_page[1];
        if(sizeof($arr_page) > 2)
            $item = $arr_page[2];
    }

    require INDEX . "ui/meta.admin.menu.php";
    $is_page_found = false;
    for($i=0; $i<sizeof($arr_menu_items); $i++) {
        if($arr_menu_items[$i]['href'] == $page) {
            $arr_menu_items[$i]['status']      = ' active';
            $arr_menu_items[$i]['default-tab'] = $tab;
            $is_page_found = true;
            break;
        }
    }
    if(!$is_page_found) {
        $page = 'home';
        $arr_menu_items[0]['status'] = ' active';
    }
    require INDEX . "php/admin.user_verifier.php";
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?php echo APP_NAME . ($page != '' ? ' (' . strtoupper(implode(' ', explode('_', $page))) . ')' : ''); ?></title>
        <?php require INDEX . "ui/imports.global.php"; ?>
        <?php require INDEX . "ui/scripts.global.top.php"; ?>
    </head>
    <body class="fixed-header menu-pin menu-behind overflow-hidden hidden" data-app='<?php echo APP_NAME; ?>' data-index='<?php echo INDEX; ?>' data-root='<?php echo HOST_ROOT; ?>' data-page='<?php echo $page; ?>' data-dir='<?php echo $dir; ?>'>
        <?php require INDEX . "ui/layout.sidebar.php"; ?>
        <?php require INDEX . "ui/layout.main.php"; ?>
        <?php require INDEX . "inc/popups/popups.html"; ?>
        <?php require INDEX . "ui/scripts.global.bottom.php"; ?>
    </body>
</html>
<?php require INDEX . "php/__fin.php"; ?>