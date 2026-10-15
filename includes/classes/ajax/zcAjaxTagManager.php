<?php
declare(strict_types=1);

// -----
// Part of the "Zen Tags" plugin by Cindy Merkin (lat9)
// Copyright (c) 2014-2026 Vinos de Frutas Tropicales
//
// Last updated: v2.1.0
//
class zcAjaxTagManager extends base
{
    /** @var string[] */
    public const ALLOWED_METHODS = ['removeTag', 'addTags', 'addTagItem', 'makeCloud'];

    public function removeTag(): array
    {
        if (!$this->isAdmin() || !isset($_POST['tag_id'], $_POST['tag_mapping_id'], $_POST['tag_mapping_type'])) {
            return [
                'tag_list' => '',
            ];
        }

        $zen_tags = new ZenTags();
        if (preg_match('/^tag_id\[([0-9]+)\]/', $_POST['tag_id'], $matches)) {
            if (isset($matches[1])) {
                $zen_tags->removeTagByType((int)$_POST['tag_mapping_id'], (int)$matches[1], (int)$_POST['tag_mapping_type']);
            }
        }

        return [
            'tag_list' => $zen_tags->generateTagsList($_POST['tag_mapping_id'], $_POST['tag_mapping_type']) 
        ];
    }

    public function addTags(): array
    {
        if (!$this->isAdmin() || !isset($_POST['tag_mapping_id'], $_POST['tag_mapping_type'])) {
            return [
                'tag_list' => '',
            ];
        }

        $zen_tags = new ZenTags();
        $zen_tags->updateTagInputs($_POST['tag_mapping_id'], $_POST['tag_mapping_type']);
        return [
            'tag_list' => $zen_tags->generateTagsList($_POST['tag_mapping_id'], $_POST['tag_mapping_type']) 
        ];
    }

    public function addTagItem(): array
    {
        if (!$this->isAdmin() || !isset($_POST['tag_id'], $_POST['tag_mapping_id'], $_POST['tag_mapping_type'])) {
            return [
                'tag_list' => '',
            ];
        }

        $zen_tags = new ZenTags();
         if (preg_match('/^tag_id\[([0-9]+)\]/', $_POST['tag_id'], $matches)) {
            if (isset($matches[1])) {
                $zen_tags->addTagMapping($_POST['tag_mapping_id'], $matches[1], $_POST['tag_mapping_type']);
            }
        }
        return [
            'tag_list' => $zen_tags->generateTagsList($_POST['tag_mapping_id'], $_POST['tag_mapping_type']) 
        ];
    }

    public function makeCloud(): array
    {
        $zen_tags = new ZenTags();
        return [
            'tag_title' => ZEN_TAG_TEXT_ADD_TAG,
            'tag_list' => $this->isAdmin() ? $zen_tags->makeTagCloudArray() : [],
        ];
    }

    protected function isAdmin(): bool
    {
        return defined('IS_ADMIN_FLAG') && IS_ADMIN_FLAG === true;
    }
}
