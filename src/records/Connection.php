<?php

namespace thejoshsmith\xero\records;

use craft\db\ActiveRecord;

/**
 * Active Record class for saving Xero Connections
 *
 * @author Josh Smith <by@joshthe.dev>
 */
class Connection extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%xero_connections}}';
    }

    public function rules()
    {
        return [
            [['id', 'connectionId', 'credentialId', 'resourceOwnerId', 'tenantId', 'userId', 'siteId'], 'safe'],
        ];
    }
}
