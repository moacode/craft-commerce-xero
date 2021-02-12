<?php
namespace thejoshsmith\xero\web\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class XeroCPAsset extends AssetBundle
{
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = '@thejoshsmith/xero/web/assets/dist';

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'js/cp.js',
        ];

        $this->css = [
            'css/styles.css',
        ];

        parent::init();
    }
}
