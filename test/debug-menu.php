<?php
// This is a simple script to debug current globals
echo '<pre>';
global $menu, $submenu, $parent_file, $submenu_file, $plugin_page;
echo 'Current plugin page: ' . $plugin_page . "
";
echo 'Current parent file: ' . $parent_file . "
";
echo 'Current submenu file: ' . $submenu_file . "
";
echo '</pre>';
?>
