<?php
/**
 * @copyright Copyright (c) Myles Derham.
 * @license https://craftcms.github.io/license/
 */

namespace mediabeastnz\xero\jobs;

use mediabeastnz\xero\Xero;
use mediabeastnz\xero\services\XeroAPIService;

use Craft;
use craft\queue\BaseJob;

class SendToXeroJob extends BaseJob
{
    // Properties
    // =========================================================================

    /**
     * @var array
     */
    public $orders = [];


    // Protected Methods
    // ========================================================================

    protected function defaultDescription()
    {
        return Craft::t('xero', 'Send Orders to Xero');
    }


    // Public Methods
    // =========================================================================

    public function execute($queue)
    {
        $totalSteps = count($this->orders);
        for ($step = 0; $step < $totalSteps; $step++) { 
            // run functions here
            $this->setProgress($queue, $step / $totalSteps);
        }
    }
}