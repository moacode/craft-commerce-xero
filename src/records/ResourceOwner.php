<?php

namespace thejoshsmith\xero\records;

use craft\db\ActiveRecord;

/**
 * Active Record class for saving Xero Resource Owners
 *
 * @author Josh Smith <by@joshthe.dev>
 */
class ResourceOwner extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%xero_resource_owners}}';
    }

    public function rules()
    {
        return [
            [['id', 'xeroUserId', 'preferredUsername', 'email', 'givenName', 'familyName'], 'safe'],
        ];
    }
}
