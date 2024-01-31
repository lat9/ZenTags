<?php
// -----
// Part of the "Zen Tags" plugin by lat9
// Copyright (c) 2014-2024, Vinos de Frutas Tropicales
//
$autoLoadConfig[200][] = [
    'autoType' => 'class',
    'loadFile' => 'ZenTags.php'
];
$autoLoadConfig[200][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_zen_tags.php'
];

$autoLoadConfig[200][] = [
    'autoType' => 'class',
    'loadFile' => 'observers/ZenTagsAdminObserver.php',
    'classPath' => DIR_WS_CLASSES
];
$autoLoadConfig[200][] = [
    'autoType' => 'classInstantiate',
    'className' => 'ZenTagsAdminObserver',
    'objectName' => 'zen_tags_observer'
];
