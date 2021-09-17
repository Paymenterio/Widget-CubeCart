<?php
if(!defined('CC_DS')) die('Access Denied');
$module		= new Module(__FILE__, $_GET['module'], 'admin/index.tpl', true,false);
$template_vars = array(
    'version'=>checkVersion('1.0.1')
);
$module->assign_to_template($template_vars);
$module->fetch();
$page_content = $module->display();

function checkVersion($version)
{
    $currentVersion = '1.0.1';

    if ($currentVersion == $version)
    {
        return '<p style="text-align:center;">Posiadasz najnowszą wersję modułu płatności Paymenterio dla CubeCart</p>';
    }

    return '<p style="text-align:center;">Aktualnie posiadasz wersję '. $version .', jednak istnieje nowsza wersja. Wejdź na <a href="https://paymenterio.com">paymenterio.com</a>, aby dowiedzieć się więcej.<p>';
}