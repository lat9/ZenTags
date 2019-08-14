<?php
// -----
// Part of the "Zen Tags" plugin by Cindy Merkin (lat9)
// Copyright (c) 2014-2019 Vinos de Frutas Tropicales
//
class zcAjaxTagManager extends base
{
    public function removeTag()
    {
        $zen_tags = new ZenTags();
        if (isset($_POST['tag_id'])) {
            if (preg_match('/^tag_id\[([0-9]+)\]/', $_POST['tag_id'], $matches)) {
                if (isset($matches[1])) {
                    $zen_tags->removeTagByType($_POST['tag_mapping_id'], $matches[1], $_POST['tag_mapping_type']);
                }
            }
        }
        $response = array(
            'tag_list' => $zen_tags->generateTagsList($_POST['tag_mapping_id'], $_POST['tag_mapping_type']) 
        );
        return $response;
    }
    
    public function addTags()
    {
        $zen_tags = new ZenTags();
        $zen_tags->updateTagInputs($_POST['tag_mapping_id'], $_POST['tag_mapping_type']);
        $response = array(
            'tag_list' => $zen_tags->generateTagsList($_POST['tag_mapping_id'], $_POST['tag_mapping_type']) 
        );
        return $response;
    }
    
    public function addTagItem()
    {
        $zen_tags = new ZenTags();
        if (isset($_POST['tag_id'])) {
            if (preg_match('/^tag_id\[([0-9]+)\]/', $_POST['tag_id'], $matches)) {
                if (isset($matches[1])) {
                    $zen_tags->addTagMapping($_POST['tag_mapping_id'], $matches[1], $_POST['tag_mapping_type']);
                }
            }
        }
        $response = array(
            'tag_list' => $zen_tags->generateTagsList($_POST['tag_mapping_id'], $_POST['tag_mapping_type']) 
        );
        return $response;
    }
    
    public function makeCloud()
    {
        $zen_tags = new ZenTags();
        $response = array(
            'tag_title' => ZEN_TAG_TEXT_ADD_TAG,
            'tag_list' => $zen_tags->makeTagCloudArray()
        );
        return $response;
    }
}
