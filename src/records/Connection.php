<?php

namespace thejoshsmith\xero\records;

use craft\db\ActiveRecord;
use craft\records\Site;
use craft\web\User;

/**
 * Active Record class for saving Xero Connections
 *
 * @author Josh Smith <by@joshthe.dev>
 */
class Connection extends ActiveRecord
{
    const STATUS_CONNECTED = 'connected';
    const STATUS_DISCONNECTED = 'disconnected';

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%xero_connections}}';
    }

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            [['id', 'connectionId', 'credentialId', 'resourceOwnerId', 'tenantId', 'userId', 'siteId', 'status'], 'safe'],
        ];
    }

    public function getCredential()
    {
        return $this->hasOne(Credential::class, ['id' => 'credentialId']);
    }

    public function getResourceOwner()
    {
        return $this->hasOne(ResourceOwner::class, ['id' => 'resourceOwnerId']);
    }

    public function getTenant()
    {
        return $this->hasOne(Tenant::class, ['id' => 'tenantId']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }

    public function getSite()
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}
