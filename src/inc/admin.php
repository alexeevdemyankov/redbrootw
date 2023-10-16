<?php
add_action('admin_menu', 'menu_redbrootw_plg');

function menu_redbrootw_plg()
{
    add_menu_page('Redbro OTW Plugin', 'Redbro OTW Plugin', 'manage_options', 'redbrootw_admin', 'redbrootw_list', plugins_url('/redbrootw/img/ico.png'), 1);
}


function redbrootw_list()
{
    $tpl = $_GET['plg_action'];
    if(!isset($_GET['plg_action'])) $tpl = 'default';
    if (!is_file(__DIR__ . '/tpl/' . $tpl . '.php')):$tpl = 'error';endif;
    include('tpl/' . $tpl . '.php');
}
