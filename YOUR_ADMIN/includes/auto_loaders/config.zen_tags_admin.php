<?php
// -----
// Part of the "Zen Tags" plugin by lat9
// Copyright (c) 2014-2018, Vinos de Frutas Tropicales
//
$autoLoadConfig[200][] = array( 
    'autoType' => 'class', 
    'loadFile' => 'ZenTags.php' 
);
$autoLoadConfig[200][] = array( 
    'autoType' => 'init_script', 
    'loadFile' => 'init_zen_tags.php'
);
                             
$autoLoadConfig[200][] = array(
    'autoType' => 'class',
    'loadFile' => 'observers/ZenTagsAdminObserver.php',
    'classPath' => DIR_WS_CLASSES
);
$autoLoadConfig[200][] = array(
    'autoType' => 'classInstantiate',
    'className' => 'ZenTagsAdminObserver',
    'objectName' => 'zen_tags_observer'
);