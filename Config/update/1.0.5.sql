SET FOREIGN_KEY_CHECKS = 0;
ALTER TABLE `product_status_i18n` ADD `backoffice_title` VARCHAR(255) AFTER `title`;

UPDATE thelia.product_status_i18n t
SET t.backoffice_title = 'Fin de Série'
WHERE t.id= 4
  AND t.locale LIKE 'fr_FR';

UPDATE thelia.product_status_i18n t
SET t.backoffice_title = 'Normal'
WHERE t.id = 1
  AND t.locale LIKE 'fr_FR';

UPDATE thelia.product_status_i18n t
SET t.backoffice_title = 'Arrêté'
WHERE t.id = 2
  AND t.locale LIKE 'fr_FR';

UPDATE thelia.product_status_i18n t
SET t.backoffice_title = 'Soldes'
WHERE t.id = 3
  AND t.locale LIKE 'fr_FR';

SET FOREIGN_KEY_CHECKS = 1;