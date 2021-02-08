<?php

namespace thejoshsmith\xero\web\twig;

use thejoshsmith\xero\Plugin;

use yii\base\Behavior;

/**
 * Class CraftVariableBehavior
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  2.0
 */
class CraftVariableBehavior extends Behavior
{
    /**
     * @var Plugin
     */
    public $xero;

    public function init()
    {
        parent::init();

        // Point `craft.xero` to the craft\xero\Plugin instance
        $this->xero = Plugin::getInstance();
    }
}
