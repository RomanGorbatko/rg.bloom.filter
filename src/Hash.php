<?php
/**
 * Created by PhpStorm.
 * User: romangorbatko
 * Date: 02.02.16
 * Time: 14:16.
 */
namespace RG;

/**
 * Class Hash
 * Creates random hash generator.
 */
class Hash
{
    /**
     * Seed for unification every HashObject.
     *
     * @var array
     */
    public $seed;

    /**
     * Parameters.
     *
     * @var array
     */
    public $params;

    /**
     * Map of user setup parameters.
     *
     * @var bool
     */
    private $map = array(
        'strtolower' => array(
            'type' => 'boolean',
        ),
    );

    /**
     * Hash constructor.
     *
     * @param null $setup
     * @param null $hashes
     */
    public function __construct($setup = null, $hashes = null)
    {
        /*
         *	Default parameters
         */
        $params = array(
            'strtolower' => true,
        );

        /*
         *	Applying income user parameters
         */
        $params = Map::apply($this->map, $params, $setup);
        $this->params = $params;

        /*
         *	Creating unique seed
         */
        $seeds = array();
        if ($hashes) {
            foreach ($hashes as $hash) {
                $seeds = array_merge((array) $seeds, (array) $hash->seed);
            }
        }
        do {
            $hash = substr(str_shuffle(BloomFilter::alphabet), 0, 6);
        } while (in_array($hash, $seeds));
        $this->seed[] = $hash;
    }

    /**
     * Hash use's murmurhash2 and md5 algorithms to get number less than $size parameter.
     *
     * @param $string
     * @param $size
     *
     * @return int
     */
    public function crc($string, $size)
    {
        $string = mb_strtolower(strval($string), 'UTF-8');

        return abs(MurMur::hash2($this->seed[0].$string)) % $size;
    }
}
