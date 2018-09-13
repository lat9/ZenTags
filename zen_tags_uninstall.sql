DELETE FROM admin_pages WHERE page_key='configZenTags';
DELETE FROM configuration_group WHERE configuration_group_titls = 'configZenTags';
DELETE FROM configuration WHERE configuration_key LIKE 'ZEN_TAGS_%';
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS tags_to_categories;
DROP TABLE IF EXISTS tags_to_products;