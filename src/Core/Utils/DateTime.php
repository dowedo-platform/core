<?php
/**
 * DateTime.php
 *
 * @copyright   Copyright 2009-2015 江苏麦金莱网络科技有限公司
 * @author      Xueron
 * @package     dolphin
 * @version     $Id: $
 * @link        http://www.ruyidai.cn
 */
 
 

namespace Carter\Core\Utils;


/**
 * Class DateTime
 * @package Carter\Core\Utils
 */
class DateTime extends \DateTime
{
    const MYSQL_DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    const CHINAPNR_ORDDATE_FORMAT = 'Ymd';

    /**
     * @return bool|string
     */
    public static function now()
    {
        return static::date();
    }

    /**
     * @return bool|string
     */
    public static function time()
    {
        return \time();
    }

    /**
     * @param string $format
     * @param null $timestamp
     * @return bool|string
     */
    public static function date($format = self::MYSQL_DATE_TIME_FORMAT, $timestamp = null)
    {
        if ($timestamp != null) {
            return \date($format, $timestamp);
        } else {
            return \date($format);
        }
    }

    /**
     * @param $time
     *
     * @return int
     */
    public static function strtotime($time)
    {
        return \strtotime($time);
    }

    /**
     * 返回一个时间相对于base的持续时间. base 默认当前时间.
     *
     * @param $time
     * @param null $base
     * @return array|int|null
     */
    public static function duration($time, $base = null)
    {
        if ($base == null) {
            $base = time();
        }
        if (!is_int($base)) {
            $base = self::strtotime($base);
        }
        if (!is_int($time)) {
            return $base - self::strtotime($time);
        }
        return $base - $time;
    }

    /**
     * @param $startTime
     * @param string $duration
     * @return string
     */
    public static function getExpireTime($startTime, $duration = '2D')
    {
        $now = new static($startTime);
        $now->add(new \DateInterval('P' . $duration));
        return $now->format(self::MYSQL_DATE_TIME_FORMAT);
    }

    /**
     * @param int $num
     */
    public function addMonth($num = 1)
    {
        $date = $this->format('Y-n-j');
        list($y, $m, $d) = explode('-', $date);

        $m += $num;
        while ($m > 12) {
            $m -= 12;
            $y++;
        }

        $last_day = date('t', strtotime("$y-$m-1"));
        if ($d > $last_day) {
            $d = $last_day;
        }

        return $this->setDate($y, $m, $d);
    }

    /**
     * @param int $num
     */
    public function subMonth($num = 1)
    {
        $date = $this->format('Y-n-j');
        list($y, $m, $d) = explode('-', $date);

        $m -= $num;
        while ($m <= 0) {
            $m += 12;
            $y--;
        }

        $last_day = date('t', strtotime("$y-$m-1"));
        if ($d > $last_day) {
            $d = $last_day;
        }

        $this->setDate($y, $m, $d);
    }
}
