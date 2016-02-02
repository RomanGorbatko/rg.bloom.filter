<?php
/**
 * Created by PhpStorm.
 * User: romangorbatko
 * Date: 02.02.16
 * Time: 14:17.
 */
namespace RG;

/**
 * Class MurMur.
 *
 * @author Stefano Azzolini (lastguest@gmail.com)
 *
 * @see https://github.com/lastguest/murmurhash-php
 *
 * @author Gary Court (gary.court@gmail.com)
 *
 * @see http://github.com/garycourt/murmurhash-js
 *
 * @author Austin Appleby (aappleby@gmail.com)
 *
 * @see http://sites.google.com/site/murmurhash/
 *
 * @author Roman Gorbatko (romangorbatko@gmail.com)
 *
 * @see https://github.com/RomanGorbatko/
 */
class MurMur
{
    const INT = 4294967295;

    /**
     * PHP Implementation of MurmurHash3.
     *
     * @param string $key  Text to hash.
     * @param int $seed Positive integer only
     *
     * @return number 32-bit (base 32 converted) positive integer hash
     */
    public static function hash3_int($key, $seed = 0)
    {
        $key = (string) $key;
        $klen = strlen($key);
        $h1 = $seed;
        for ($i = 0, $bytes = $klen - ($remainder = $klen & 3); $i < $bytes;) {
            $k1 = ((ord($key[$i]) & 0xff))
                | ((ord($key[++$i]) & 0xff) << 8)
                | ((ord($key[++$i]) & 0xff) << 16)
                | ((ord($key[++$i]) & 0xff) << 24);
            ++$i;
            $k1 = (((($k1 & 0xffff) * 0xcc9e2d51) + ((((($k1 >= 0 ? $k1 >> 16 : (($k1 & 0x7fffffff) >> 16) | 0x8000)) * 0xcc9e2d51) & 0xffff) << 16))) & 0xffffffff;
            $k1 = $k1 << 15 | ($k1 >= 0 ? $k1 >> 17 : (($k1 & 0x7fffffff) >> 17) | 0x4000);
            $k1 = (((($k1 & 0xffff) * 0x1b873593) + ((((($k1 >= 0 ? $k1 >> 16 : (($k1 & 0x7fffffff) >> 16) | 0x8000)) * 0x1b873593) & 0xffff) << 16))) & 0xffffffff;
            $h1 ^= $k1;
            $h1 = $h1 << 13 | ($h1 >= 0 ? $h1 >> 19 : (($h1 & 0x7fffffff) >> 19) | 0x1000);
            $h1b = (((($h1 & 0xffff) * 5) + ((((($h1 >= 0 ? $h1 >> 16 : (($h1 & 0x7fffffff) >> 16) | 0x8000)) * 5) & 0xffff) << 16))) & 0xffffffff;
            $h1 = ((($h1b & 0xffff) + 0x6b64) + ((((($h1b >= 0 ? $h1b >> 16 : (($h1b & 0x7fffffff) >> 16) | 0x8000)) + 0xe654) & 0xffff) << 16));
        }
        $k1 = 0;
        switch ($remainder) {
            case 3: $k1 ^= (ord($key[$i + 2]) & 0xff) << 16;
            case 2: $k1 ^= (ord($key[$i + 1]) & 0xff) << 8;
            case 1: $k1 ^= (ord($key[$i]) & 0xff);
                $k1 = ((($k1 & 0xffff) * 0xcc9e2d51) + ((((($k1 >= 0 ? $k1 >> 16 : (($k1 & 0x7fffffff) >> 16) | 0x8000)) * 0xcc9e2d51) & 0xffff) << 16)) & 0xffffffff;
                $k1 = $k1 << 15 | ($k1 >= 0 ? $k1 >> 17 : (($k1 & 0x7fffffff) >> 17) | 0x4000);
                $k1 = ((($k1 & 0xffff) * 0x1b873593) + ((((($k1 >= 0 ? $k1 >> 16 : (($k1 & 0x7fffffff) >> 16) | 0x8000)) * 0x1b873593) & 0xffff) << 16)) & 0xffffffff;
                $h1 ^= $k1;
        }
        $h1 ^= $klen;
        $h1 ^= ($h1 >= 0 ? $h1 >> 16 : (($h1 & 0x7fffffff) >> 16) | 0x8000);
        $h1 = ((($h1 & 0xffff) * 0x85ebca6b) + ((((($h1 >= 0 ? $h1 >> 16 : (($h1 & 0x7fffffff) >> 16) | 0x8000)) * 0x85ebca6b) & 0xffff) << 16)) & 0xffffffff;
        $h1 ^= ($h1 >= 0 ? $h1 >> 13 : (($h1 & 0x7fffffff) >> 13) | 0x40000);
        $h1 = (((($h1 & 0xffff) * 0xc2b2ae35) + ((((($h1 >= 0 ? $h1 >> 16 : (($h1 & 0x7fffffff) >> 16) | 0x8000)) * 0xc2b2ae35) & 0xffff) << 16))) & 0xffffffff;
        $h1 ^= ($h1 >= 0 ? $h1 >> 16 : (($h1 & 0x7fffffff) >> 16) | 0x8000);

        return $h1;
    }

    /**
     * @param $key
     * @param int $seed
     *
     * @return string
     */
    public static function hash3($key, $seed = 0)
    {
        return base_convert(self::hash3_int($key, $seed), 10, 32);
    }

    /**
     * @param $key
     * @param int $seed
     *
     * @return int
     */
    public static function hash2($key, $seed = 0)
    {
        $m = 0x5bd1e995;
        $r = 24;
        $len = strlen($key);
        $h = $seed ^ $len;
        $o = 0;

        while ($len >= 4) {
            $k = ord($key[$o]) | (ord($key[$o + 1]) << 8) | (ord($key[$o + 2]) << 16) | (ord($key[$o + 3]) << 24);
            $k = ($k * $m) & self::INT;
            $k = ($k ^ ($k >> $r)) & self::INT;
            $k = ($k * $m) & self::INT;

            $h = ($h * $m) & self::INT;
            $h = ($h ^ $k) & self::INT;

            $o += 4;
            $len -= 4;
        }

        $data = substr($key, 0 - $len, $len);

        switch ($len) {
            case 3: $h = ($h ^ (ord($data[2]) << 16)) & self::INT;
            case 2: $h = ($h ^ (ord($data[1]) << 8)) & self::INT;
            case 1: $h = ($h ^ (ord($data[0]))) & self::INT;
                $h = ($h * $m) & self::INT;
        };
        $h = ($h ^ ($h >> 13)) & self::INT;
        $h = ($h * $m) & self::INT;
        $h = ($h ^ ($h >> 15)) & self::INT;

        return $h;
    }
}
