DELETE FROM admin_pages WHERE page_key = 'configZenTags' LIMIT 1;
DELETE FROM configuration_group WHERE configuration_group_title = 'Configure Zen Tags' LIMIT 1;
DELETE FROM configuration WHERE configuration_key LIKE 'ZEN_TAGS_%';
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS tags_to_categories;
DROP TABLE IF EXISTS tags_to_products;