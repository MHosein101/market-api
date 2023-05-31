<?php

namespace App\Http\Helpers;

/**
 * Helper methods to make seeder data for database
 */ 
class SeederHelper
{
    /**
     * Make random string with given params
     *
     * @param array $words
     * @param array $range
     * @param string $start
     * @param string $sep
     * 
     * @return string
     */ 
    public static function string($words, $range, $start = '', $sep = ' ')
    {
        $result = $start;

        $rp = $range[1] == null 
        ? $range[0] 
        : random_int($range[0], $range[1]);

        for($i = 1; $i <= $rp; $i++) 
        {
            $ri = random_int(0, count($words)-1);

            $result .= $sep . $words[$ri];
        }

        return $result;
    }

    /**
     * Make random number string with given length
     *
     * @param int $len
     * 
     * @return string
     */ 
    public static function number($len)
    {
        $nums = [0,1,2,3,4,5,6,7,8,9];

        $result = '';

        for($i = 1; $i <= $len; $i++) 
        {
            $ri = random_int(0, count($nums)-1);

            $result .= $nums[$ri];
        }

        return $result;
    }

    /**
     * Return one of data items randomly
     *
     * @param array $data
     * 
     * @return string|int
     */ 
    public static function one($data)
    {
        $ri = random_int(0, count($data)-1);

        return $data[$ri];
    }
    
}