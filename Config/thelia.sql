
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- product_status
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `product_status`;

CREATE TABLE `product_status`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `protected` TINYINT DEFAULT 0 NOT NULL,
    `color` CHAR(7),
    `code` VARCHAR(255),
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- product_product_status
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `product_product_status`;

CREATE TABLE `product_product_status`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `product_id` INTEGER NOT NULL,
    `product_status_id` INTEGER NOT NULL,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    INDEX `fi_product_status` (`product_status_id`),
    INDEX `fi_product` (`product_id`),
    CONSTRAINT `fk_product_status`
        FOREIGN KEY (`product_status_id`)
            REFERENCES `product_status` (`id`)
            ON UPDATE RESTRICT
            ON DELETE CASCADE,
    CONSTRAINT `fk_product`
        FOREIGN KEY (`product_id`)
            REFERENCES `product` (`id`)
            ON UPDATE RESTRICT
            ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- product_status_i18n
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `product_status_i18n`;

CREATE TABLE `product_status_i18n`
(
    `id` INTEGER NOT NULL,
    `locale` VARCHAR(5) DEFAULT 'en_US' NOT NULL,
    `title` VARCHAR(255),
    `backoffice_title` VARCHAR(255),
    `description` LONGTEXT,
    `chapo` TEXT,
    `postscriptum` TEXT,
    PRIMARY KEY (`id`,`locale`),
    CONSTRAINT `product_status_i18n_fk_c32b93`
        FOREIGN KEY (`id`)
            REFERENCES `product_status` (`id`)
            ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
