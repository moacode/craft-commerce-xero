<?php

namespace thejoshsmith\xero\records;

use craft\db\ActiveRecord;

/**
 * Active Record class for saving Xero Credentials
 *
 * @author Josh Smith <by@joshthe.dev>
 */
class Credential extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%xero_credentials}}';
    }

    public function rules()
    {
        return [
            [['id', 'accessToken', 'refreshToken', 'expires', 'scope'], 'safe'],
        ];
    }
}
