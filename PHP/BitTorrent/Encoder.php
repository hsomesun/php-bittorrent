<?php
/**
 * This file is part of the PHP BitTorrent package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace PHP\BitTorrent;

use InvalidArgumentException;

/**
 * Encode encodable PHP variables to the BitTorrent counterpart
 *
 * @package Encoder
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class Encoder implements EncoderInterface {
    /**
     * Parameters for the encoder
     *
     * @var array
     */
    private $params = array(
        // Set to true to encode empty arrays as dictionaries ("de") instead of lists ("le")
        'encodeEmptyArrayAsDictionary' => false,
    );

    /**
     * Class constructor
     *
     * @param array $params Parameters for the encoder
     */
    public function __construct(array $params = array()) {
        $this->params = array_replace($this->params, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function setParam($key, $value) {
        $this->params[$key] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function encode($var) {
        if ($this->isInt($var)) {
            return $this->encodeInteger($var);
        } else if (is_string($var)) {
            return $this->encodeString($var);
        } else if (is_array($var)) {
            $size = count($var);

            if (!$size && $this->params['encodeEmptyArrayAsDictionary']) {
                return $this->encodeDictionary($var);
            }

            for ($i = 0; $i < $size; $i++) {
                if (!isset($var[$i])) {
                    return $this->encodeDictionary($var);
                }
            }

            return $this->encodeList($var);
        }

        throw new InvalidArgumentException('Variables of type ' . gettype($var) . ' can not be encoded.');
    }

    /**
     * {@inheritDoc}
     */
    public function encodeInteger($integer) {
        if ($this->isInt($integer)) {
            return 'i' . $integer . 'e';
        }

        throw new InvalidArgumentException('Expected an integer.');
    }

    /**
     * {@inheritDoc}
     */
    public function encodeString($string) {
        if (!is_string($string)) {
            throw new InvalidArgumentException('Expected string, got: ' . gettype($string) . '.');
        }

        return strlen($string) . ':' . $string;
    }

    /**
     * {@inheritDoc}
     */
    public function encodeList($list) {
        if (!is_array($list)) {
            throw new InvalidArgumentException('Expected array, got: ' . gettype($list) . '.');
        }

        $ret = 'l';

        foreach ($list as $value) {
            $ret .= $this->encode($value);
        }

        return $ret . 'e';
    }

    /**
     * {@inheritDoc}
     */
    public function encodeDictionary($dictionary) {
        if (!is_array($dictionary)) {
            throw new InvalidArgumentException('Expected array, got: ' . gettype($dictionary) . '.');
        }

        ksort($dictionary);

        $ret = 'd';

        foreach ($dictionary as $key => $value) {
            $ret .= $this->encodeString((string) $key) . $this->encode($value);
        }

        return $ret . 'e';
    }

    /**
     * Check if a variable is an integer
     *
     * @param int|string
     * @return boolean
     */
    private function isInt($var) {
        return is_int($var) ||
               (PHP_INT_SIZE === 4 && is_numeric($var) && (strpos($var, '.') === false));
    }
}
