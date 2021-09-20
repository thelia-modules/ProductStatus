<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace ProductStatus;

use Propel\Runtime\Connection\ConnectionInterface;
use Thelia\Install\Database;
use Thelia\Module\BaseModule;

class ProductStatus extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'productstatus';
    const CODE_EXIST_MESSAGE = 'This code already exists, please pick another one';

    public function preActivation(ConnectionInterface $con = null) : bool
    {
        if (! self::getConfigValue('is_initialized', false)) {
            $database = new Database($con);

            $database->insertSql(null, array(__DIR__ . '/Config/thelia.sql'));

            self::setConfigValue('is_initialized', true);
        }

        return true;
    }

}
