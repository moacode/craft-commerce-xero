<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @link      https://www.mylesderham.dev/
 * @copyright Copyright (c) 2019 Myles Derham
 */

namespace mediabeastnz\xero\web\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class SendToXeroAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@mediabeastnz/xero/web/assets/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/xero.js',
        ];

        $this->css = [
            'css/xero.css',
        ];

        parent::init();
    }
}
