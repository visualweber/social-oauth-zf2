<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace SocialOAuth\Uri;

use Zend\Uri\Http;

/**
 * HTTP URI handler
 */
class OAuthHttp extends Http {
    public function toString() {
        return parent::toString();
    }
    public static function encodePath($path) {
        if (!is_string($path)) {
            throw new Exception\InvalidArgumentException(sprintf(
                    'Expecting a string, got %s', (is_object($path) ? get_class($path) : gettype($path))
            ));
        }
        return $path;
        $regex = '/(?:[^' . self::CHAR_UNRESERVED . ':@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/';
        $escaper = static::getEscaper();
        $replace = function ($match) use ($escaper) {
            return $escaper->escapeUrl($match[0]);
        };

        return preg_replace_callback($regex, $replace, $path);
    }

}
