<?php

namespace Genesis\Api\Request\Financial\Preauthorization;

use master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Types;
use master\libraries\genesis\src\Genesis\Api\Request\Base\Financial;

/**
 * Class IncrementalAuthorize
 * @package Genesis\Api\Request\Financial\Preauthorization
 */
class IncrementalAuthorize extends master\libraries\genesis\src\Genesis\Api\Request\Base\Financial\Reference
{
    /**
     * Returns the Request transaction type
     * @return string
     */
    protected function getTransactionType()
    {
        return Types::INCREMENTAL_AUTHORIZE;
    }
}
