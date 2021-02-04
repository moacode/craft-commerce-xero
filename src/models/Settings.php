<?php

namespace thejoshsmith\xero\models;

use craft\base\Model;

/**
 * Settings Model
 * Defines the plugin settings stored in project config
 */
class Settings extends Model
{
    public $xeroClientId = '';
    public $xeroClientSecret = '';

    /**
     * Defines validation rules for the above settings
     *
     * @return void
     */
    public function rules()
    {
        return [
            [['xeroClientId', 'xeroClientSecret'], 'required'],
            [['xeroClientId', 'xeroClientSecret'], 'string'],
        ];
    }
}
