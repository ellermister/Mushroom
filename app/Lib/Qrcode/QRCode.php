<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2020/6/17
 * Time: 21:12
 */

namespace App\Lib\Qrcode;

include "lib/QrReader.php";
class QRCode
{

    public static function text($img)
    {
        $QrReader = new \QrReader($img);
        $result = $QrReader->text();
        unset($QrReader);
        return $result;
    }

}