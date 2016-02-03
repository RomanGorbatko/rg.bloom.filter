<?php
/**
 * Created by PhpStorm.
 * User: romangorbatko
 * Date: 02.02.16
 * Time: 14:10.
 */
namespace RG;

/**
 * Main BloomClass
 * Use cases:
 * -to cooldown HDD usage
 * -to speedup checking of object excistance (with counter 40% slower, but still more faster than native)
 * -to save memory.
 *
 * When to use:
 * -Get/Set operations are more than 0.001
 * -You need a fast check with big set, more than 100 000 entries
 */
class BloomFilter
{
    /**
     * Bloom object container.
     *
     * @var mixed
     */
    public $set;

    /**
     * Array of Hashes objects.
     *
     * @var array
     */
    public $hashes;

    /**
     * Error chance (0;1).
     *
     * @var float
     */
    public $error_chance;

    /**
     * Size of set variable.
     *
     * @var int
     */
    public $set_size;

    /**
     * Number of different hash objects.
     *
     * @var int
     */
    public $hash_count;

    /**
     * Number of current entries.
     *
     * @var int
     */
    public $entries_count;

    /**
     * Number of entries max.
     *
     * @var int
     */
    public $entries_max;

    /**
     * Use counter or not (for remove ability).
     *
     * @var bool
     */
    public $counter;

    /**
     * Alphabet for counter.
     *
     * @var string
     */
    const alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Map of user setup parameters.
     *
     * @var bool
     */
    private $map = array(
        'entries_max' => array(
            'type' => 'integer',
            'min' => 0,
        ),
        'error_chance' => array(
            'type' => 'double',
            'min' => 0,
            'max' => 1,
        ),
        'set_size' => array(
            'type' => 'integer',
            'min' => 100,
        ),
        'hash_count' => array(
            'type' => 'integer',
            'min' => 1,
        ),
        'counter' => array(
            'type' => 'boolean',
        ),
        'hash' => array(
            'strtolower' => array(
                'type' => 'boolean',
            ),
        ),
    );

    /**
     * Class initiation.
     *
     * BloomFilter constructor.
     *
     * @param null $setup
     */
    public function __construct($setup = null)
    {
        /*
         *	Default parameters
         */
        $params = array(
            'entries_max' => 100,
            'error_chance' => 0.001,
            'counter' => false,
            'hash' => array(
                'strtolower' => true,
            ),
        );

        /*
         *	Applying income user parameters
         */
        $params = Map::apply($this->map, $params, $setup);

        /*
         *	Setting every parameters as properties
         */
        foreach ($params as $key => $value) {
            $this->$key = $value;
        }

        /*
         *	Calculating size of set using maximum number of entries and error chance
         */
        if (!$this->set_size) {
            $this->set_size = abs(ceil(($this->entries_max * log($this->error_chance)) / pow(log(2), 2)));
        }

        /*
         *	Calculating number of hashes using maximum number of entries and set size
         */
        if (!$this->hash_count) {
            $this->hash_count = round($this->set_size * log(2) / $this->entries_max);
        }

        /*
         *	Setting up HashObjects to hashes property
         */
        for ($i = 0; $i < $this->hash_count; ++$i) {
            $this->hashes[] = new Hash($params['hash'], $this->hashes);
        }

        /*
         *	Initiation set
         */
        $this->set = str_repeat('0', $this->set_size);

        return $this;
    }

    public function export()
    {
        $cache = [];
        foreach ($this->hashes as $hash) {
            $cache[] = $hash->seed[0];
        }

        return [
            'set' => $this->set,
            'hashes' => $cache,
            'entries' => $this->entries_max
        ];
    }

    /**
     * For serializing.
     *
     * @return object for serializing
     */
    public function __sleep()
    {
        foreach ($this as $key => $attr) {
            $result[] = $key;
        }
        if ($this->entries_count == 0) {
            unset($result['set']);
        }

        return $result;
    }

    /**
     * For unserializing.
     *
     * @return object unserialized object
     */
    public function __wakeup()
    {
        if ($this->entries_count == 0) {
            $this->set = str_repeat('0', $this->set_size);
        }
    }

    /**
     * Set value to Bloom filter.
     *
     * @param mixed
     *
     * @return BloomObject
     */
    public function set($mixed)
    {
        /*
         *	In case of array given, no matter how depp it is,
         * method calls itself recursively with each array element
         */
        if (is_array($mixed)) {
            foreach ($mixed as $arg) {
                $this->set($arg);
            }
        }
        /*
         *	Otherwise method set mark into set property by using every HashObject
         */
        else {
            for ($i = 0; $i < $this->hash_count; ++$i) {
                if ($this->counter === false) {
                    $this->set[ $this->hashes[$i]->crc($mixed, $this->set_size) ] = 1;
                } else {
                    $this->counter($this->hashes[$i]->crc($mixed, $this->set_size), 1);
                }

                ++$this->entries_count;
            }
        }

        return $this;
    }

    /**
     * Unset value from Bloom filter.
     *
     * @param mixed
     *
     * @return mixed (boolean) or (array)
     */
    public function delete($mixed)
    {
        if ($this->counter === false) {
            return false;
        }
        /*
         *	In case of array given, no matter how depp it is,
         * method calls itself recursively with each array element
         */
        if (is_array($mixed)) {
            foreach ($mixed as $key => $arg) {
                $result[$key] = $this->delete($arg);
            }

            return $result;
        }
        /*
         *	Otherwise method decrements mark if element exists
         */
        elseif ($this->has($mixed)) {
            for ($i = 0; $i < $this->hash_count; ++$i) {
                $this->counter($this->hashes[$i]->crc($mixed, $this->set_size), -1);

                --$this->entries_count;
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Works with special string in counter mode.
     *
     * @param int position
     * @param int number to add
     * @param bool return value or setup set
     *
     * @return mixed
     */
    public function counter($position, $add = 0, $get = false)
    {
        /*
         *	Return value or recalculate with alphabet
         */
        if ($get === true) {
            return $this->set[$position];
        } else {
            $in_a = strpos(self::alphabet, $this->set[$position]);
            $this->set[$position] = (self::alphabet[$in_a + $add] != null) ? self::alphabet[$in_a + $add] : $this->set[$position];
        }
    }

    /**
     * Test set with given array or string, to check it existance.
     *
     * @param mixed (array) or (string)
     * @param bool
     *
     * @return mixed (array) or (boolean) or (float)
     */
    public function has($mixed, $boolean = true)
    {
        /*
         *	In case of array given will be returned array,
         * and method call's itself recursively with ararray's alements
         */
        if (is_array($mixed)) {
            $result = [];

            foreach ($mixed as $key => $arg) {
                $result[$key] = $this->has($arg, $boolean);
            }

            return $result;
        } else {
            $c = 0;
            for ($i = 0; $i < $this->hash_count; ++$i) {
                if ($this->counter === false) {
                    $crc = $this->hashes[$i]->crc($mixed, $this->set_size);
                    $value = $this->set[ $crc ];
                } else {
                    $value = $this->counter($this->hashes[$i]->crc($mixed, $this->set_size), 0, true);
                }

                /**
                 *	$boolean parameter allows to choose what to return
                 * boolean or the procent of entries pass.
                 */
                if ($boolean && !$value) {
                    return false;
                } elseif ($boolean === false) {
                    $c += ($value) ? 1 : 0;
                }
            }

            return ($boolean === true) ? true : $c / $this->hash_count;
        }
    }
}
