SET NAMES utf8mb4;

INSERT INTO `product_status` (`id`, `protected`, `color`, `code`, `created_at`, `updated_at`) VALUES
(1,	1,	'#6dd073',	'normal',	CURRENT_TIMESTAMP,	CURRENT_TIMESTAMP),
(2,	1,	'#d9534f',	'discontinued',	CURRENT_TIMESTAMP,	CURRENT_TIMESTAMP),
(3,	1,	'#986dff',	'sale',	CURRENT_TIMESTAMP,	CURRENT_TIMESTAMP),
(4,	1,	'#2c75ff',	'oddment',	CURRENT_TIMESTAMP,	CURRENT_TIMESTAMP);


INSERT INTO `product_status_i18n` (`id`, `locale`, `title`,`backoffice_title`, `description`, `chapo`, `postscriptum`) VALUES
(1,	'fr_FR',	'Normal','Normal',	'statut normal de l\'article',	NULL,	NULL),
(2,	'fr_FR',	'Arrêté','Arrêté',	'article qui ne sera plus produit',	NULL,	NULL),
(3,	'fr_FR',	'Soldes','Soldes',	'article remisé',	NULL,	NULL),
(4,	'fr_FR',	'Fin de série','Fin de série',	'échange de taille possible dans la limite des stocks disponibles. Il n\'y aura pas de réassort',	NULL,	NULL),

(1,	'en_US',	'Normal', 'Normal',	'normal status of the product',	NULL,	NULL),
(2,	'en_US',	'Discontinued','Discontinued',	'this product will not be made anymore',	NULL,	NULL),
(3,	'en_US',	'Sale','Sale',	'clearance sale',	NULL,	NULL),
(4,	'en_US',	'Oddment', 'Oddment',	'exchange available within the limits of available sizes',	NULL,	NULL),

(1,	'es_ES',	'Normal','Normal',	'estado normal del producto',	NULL,	NULL),
(2,	'es_ES',	'Interrumpido','Interrumpido',	'este artículo no se producirá',	NULL,	NULL),
(3,	'es_ES',	'Ventas','Ventas',	'ventas',	NULL,	NULL),
(4,	'es_ES',	'Remanente','Remanente',	'intercambio posible dentro de los límites de los tamaños disponibles',	NULL,	NULL),

(1,	'it_IT',	'Normale','Normale',	'stato normale dell\'articolo',	NULL,	NULL),
(2,	'it_IT',	'Smettere','Smettere',	'questo articolo non sarà più prodotto',	NULL,	NULL),
(3,	'it_IT',	'Saldi','Saldi',	'saldi',	NULL,	NULL),
(4,	'it_IT',	'Fine serie','Fine serie',	'cambio taglia possibile nei limiti delle scorte disponibili. Non ci sarà rifornimento',	NULL,	NULL);