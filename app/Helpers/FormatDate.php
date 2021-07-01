<?php
namespace App\Helpers;

class FormatDate {
    public static function stringToDate($string=null) {
        
        if ($string) {
            $new_date = '';
            $new_date_with_new_format = '';

            $new_date = strlen((string)$string) == 13 ? new \MongoDB\BSON\UTCDateTime((string)$string) : $string;
            $new_date_with_new_format = strlen((string)$string) == 13 ? $new_date->toDateTime()->format('Y-m-d H:i:s') : $new_date;
        }

        return $new_date_with_new_format;
    }
}