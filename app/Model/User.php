<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2020/5/17
 * Time: 21:19
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{

    public static function getAllUser()
    {
        return self::all();
    }
}