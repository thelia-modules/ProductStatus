-- 3.0.0: one status per product, enforced by a unique index on product_product_status.product_id.
-- Duplicate links are removed first, keeping the oldest link (smallest id) of each product: the
-- one the 2.x line and ProductStatusReader already read.
-- Idempotent: does nothing when the table is missing or the index already exists. Also played by
-- ProductStatus::postActivation(). No semicolon may end a line inside a statement: the SQL file
-- is cut on ";\n".
SET @product_status_table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'product_product_status');
SET @product_status_index_exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'product_product_status' AND index_name = 'product_product_status_product_id_unique');
SET @product_status_needs_update = (@product_status_table_exists = 1 AND @product_status_index_exists = 0);
SET @product_status_statement = IF(@product_status_needs_update, 'DELETE duplicate FROM `product_product_status` duplicate INNER JOIN `product_product_status` kept ON kept.`product_id` = duplicate.`product_id` AND kept.`id` < duplicate.`id`', 'DO 0');
PREPARE product_status_update FROM @product_status_statement;
EXECUTE product_status_update;
DEALLOCATE PREPARE product_status_update;
SET @product_status_statement = IF(@product_status_needs_update, 'ALTER TABLE `product_product_status` ADD UNIQUE INDEX `product_product_status_product_id_unique` (`product_id`)', 'DO 0');
PREPARE product_status_update FROM @product_status_statement;
EXECUTE product_status_update;
DEALLOCATE PREPARE product_status_update;
