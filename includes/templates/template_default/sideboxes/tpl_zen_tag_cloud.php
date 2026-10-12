<?php
/// -----
// Part of the "Zen Tags" plugin by Cindy Merkin (lat9)
// Copyright (c) 2014-2026 Vinos de Frutas Tropicales
//
// Last updated: v2.1.0
//
$content = '';
if (zen_config('ZEN_TAGS_ENABLE') === 'true') {
    $zen_tag_cloud = new ZenTags();
    $zen_tag_cloud_content = $zen_tag_cloud->makeTagCloud();
    if ($zen_tag_cloud_content !== '') {
        $content = '<div class="sideBoxContent centeredContent">' . $zen_tag_cloud_content . '</div>';
    }
    unset($zen_tag_cloud, $zen_tag_cloud_content);
}
