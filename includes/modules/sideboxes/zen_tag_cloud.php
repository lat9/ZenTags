<?php
/// -----
// Part of the "Zen Tags" plugin by Cindy Merkin (lat9)
// Copyright (c) 2014-2018 Vinos de Frutas Tropicales
//
require $template->get_template_dir('tpl_zen_tag_cloud.php', DIR_WS_TEMPLATE, $current_page_base, 'sideboxes') . '/tpl_zen_tag_cloud.php';
if ($content != '') {
    $title =  ZEN_TAG_HEADING_TAG_CLOUD;
    $title_link = false;
    require $template->get_template_dir($column_box_default, DIR_WS_TEMPLATE, $current_page_base, 'common') . '/' . $column_box_default;
}