<?php

/*
 * This file is part of the Сáша framework.
 *
 * (c) tchiotludo <http://github.com/tchiotludo>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Cawa\Tracking\Models\Logs;

use Cawa\Core\DI;
use Cawa\Orm\Model;

abstract class AbstractLog extends Model
{
    /**
     * @param string $data
     *
     * @return string
     */
    protected static function encrypt(string $data) : string
    {
        // Generate a 256-bit encryption key
        // This should be stored somewhere instead of recreating it each time
        $encryptionKey = base64_decode(DI::config()->get('tracking/encryption/secret')); //openssl_random_pseudo_bytes(32);

        // Generate an initialization vector
        // This *MUST* be available for decryption as well
        $iv = base64_decode(DI::config()->get('tracking/encryption/iv')); //openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        // Encrypt $data using aes-256-cbc cipher with the given encryption key and
        // our initialization vector. The 0 gives us the default options, but can
        // be changed to OPENSSL_RAW_DATA or OPENSSL_ZERO_PADDING
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryptionKey, 0, $iv);

        // If we lose the $iv variable, we can't decrypt this, so:
        // - $encrypted is already base64-encoded from openssl_encrypt
        // - Append a separator that we know won't exist in base64, ":"
        // - And then append a base64-encoded $iv

        // replace to url friendly chars
        $encrypted = strtr($encrypted, '+/=', '._-');

        return $encrypted;
    }

    /**
     * @param string $data
     *
     * @return string
     */
    protected static function decrypt(string $data) : ?string
    {
        // replace from url friendly chars
        $data = strtr($data, '._-', '+/=');

        $encryptionKey = base64_decode(DI::config()->get('tracking/encryption/secret'));
        $iv = base64_decode(DI::config()->get('tracking/encryption/iv'));

        $encrypted = $data . ':' . base64_encode($iv);

        // To decrypt, separate the encrypted data from the initialization vector ($iv).
        $parts = explode(':', $encrypted);

        // $parts[0] = encrypted data
        // $parts[1] = base-64 encoded initialization vector
        // Don't forget to base64-decode the $iv before feeding it back to

        $decrypted = openssl_decrypt($parts[0], 'aes-256-cbc', $encryptionKey, 0, base64_decode($parts[1]));

        return $decrypted ?: null;
    }
}
