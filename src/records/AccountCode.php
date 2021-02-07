<?php

namespace thejoshsmith\xero\records;

use craft\db\ActiveRecord;

/**
 * Active Record class for saving Xero Account Codes
 *
 * @author Josh Smith <by@joshthe.dev>
 */
class AccountCode extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%xero_account_codes}}';
    }
}
