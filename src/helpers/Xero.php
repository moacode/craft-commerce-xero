<?php

namespace thejoshsmith\xero\helpers;

use Firebase\JWT\JWT;
use XeroPHP\Remote\Collection;
use XeroPHP\Application as XeroApplication;

/**
 * Helper class for Xero related operations
 *
 * @author Josh Smith <by@joshthe.dev>
 */
class Xero
{
    /**
     * Returns a decoded Xero JWT
     *
     * @param string $token Access Token
     *
     * @return object
     */
    public static function decodeJwt(string $token): object
    {
        list($header, $body, $crypto) = explode('.', $token);
        return JWT::jsonDecode(JWT::urlsafeB64Decode($body));
    }

     /**
      * Serializes a Xero collection of models
      * Note: this is necessary as each model has a reference to the
      * Guzzle client which contains anonymous functions, and therefore
      * we can't serialize it into cache "as is".
      *
      * @param Collection $collection A collection of models
      *
      * @see https://github.com/calcinai/xero-php/issues/734
      *
      * @return array
      */
    public static function serialize(Collection $collection): array
    {
        $serialized = [];

        foreach ($collection as $model) {
            $serialized[] = $model->toStringArray();
        }

        return $serialized;
    }

    /**
     * Used to unserialize cached data
     *
     * @param string          $class       Name of the Xero API model class
     * @param array           $collection  Collection of data to unserialize
     * @param XeroApplication $application Collection of data to unserialize
     *
     * @see https://github.com/calcinai/xero-php/issues/734
     *
     * @return Collection|null
     */
    public static function unserialize(string $class, $collection, XeroApplication $application): ?Collection
    {
        if (!is_iterable($collection)) {
            return null;
        }

        $unserialized = [];
        foreach ($collection as $data) {
            $model = new $class($application);
            $model->fromStringArray($data);
            $unserialized[] = $model;
        }

        return new Collection($unserialized);
    }
}
