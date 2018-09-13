<?php
/// -----
// Part of the "Zen Tags" plugin by Cindy Merkin (lat9)
// Copyright (c) 2014-2018 Vinos de Frutas Tropicales
//
$content = '';
if (defined('ZEN_TAGS_ENABLE') && ZEN_TAGS_ENABLE == 'true') {
    $zen_tag_cloud = new ZenTags();
    $content = '<div class="sideBoxContent centeredContent">' . $zen_tag_cloud->makeTagCloud() . '</div>';
}