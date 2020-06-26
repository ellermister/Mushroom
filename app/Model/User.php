<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2020/5/17
 * Time: 21:19
 */

namespace App\Model;

use Mushroom\Core\Database\Model;

class User extends Model
{
    protected $table = 'users';

    public static function getAllUser()
    {
        return self::get();
    }

    /**
     * 产生随机邮箱
     *
     * @return mixed
     */
    protected static function randEmail()
    {
        return str_replace('$email', make_random_string(64), '$email@am.com');
    }

    /**
     * 产生随机名称
     *
     * @return string
     */
    protected static function randName()
    {
        $path = app()->getBasePath() . '/storage/app/npc.txt';
        if (is_file($path)) {
            $npc = file_get_contents($path);
            $npcList = json_decode($npc, true);
            $rand = rand(0, count($npcList) - 1);
            return $npcList[$rand] ?? "罗杰和苹果";
        }
        return "罗伯特";
    }

    /**
     * 获取可用名称
     *
     * @param $username
     * @return array
     * @throws \Mushroom\Core\Database\DbException
     */
    protected static function getAvailableName($username)
    {
        $discriminator = '';
        $user = self::where('username', $username)->column(['max(discriminator) as discriminator'])->find();
        if (!empty($user) and !empty($user['discriminator'])) {
            $discriminator = intval($user['discriminator']) + 1;
        }
        return [$username, $discriminator];
    }


    /**
     * 注册访客用户
     */
    public static function registerGuestUser()
    {
        $username = self::randName();
        list($username, $discriminator) = self::getAvailableName($username);
        $password = make_random_string(64);
        $email = self::randEmail();
        $data = [
            'email'         => $email,
            'username'      => $username,
            'discriminator' => $discriminator,
            'password'      => bcrypt($password),
            'avatar'        => '',
            'locale'        => 'zh-CN',
            'phone'         => '',
            'public_flags'  => 0,
            'active_flags'  => 0,
            'created_at'    => time(),
            'updated_at'    => time(),
        ];
        $result = self::create($data);
        if ($result) {
            return [
                'email'         => $email,
                'username'      => $username,
                'discriminator' => $discriminator,
                'token'         => self::createPasswordToken($email, $password),
                'avatar'        => '',
                'locale'        => 'zh-CN',
                'phone'         => '',
                'public_flags'  => 0,
                'active_flags'  => 0,
            ];
        }
        return false;
    }

    /**
     * 登录用户
     *
     * @param $email
     * @param $password
     * @return bool|mixed
     * @throws \Mushroom\Core\Database\DbException
     */
    public static function loginUser($email, $password)
    {
        $user = self::where('email', $email)->find();
        var_dump($password);
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $user['token'] = self::createPasswordToken($email, $password);
            return $user;
        }
        return false;
    }

    /**
     * 创建一次性密码token
     *
     * @param $email
     * @param $password
     * @return mixed|string
     */
    public static function createPasswordToken($email, $password)
    {
        return net_encrypt_data($email . ':' . $password, app()->getConfig('app.key'));
    }

    /**
     * 解析密码token
     *
     * @param $text
     * @return array|bool
     */
    public static function parsePasswordToken($text)
    {
        $info = net_decrypt_data($text, app()->getConfig('app.key'));
        $userInfo = explode(':', $info);
        if (count($userInfo) == 2) {
            return $userInfo;
        }
        return false;
    }

    /**
     * 获取用户信息通过token
     *
     * @param $token
     * @return bool|mixed
     * @throws \Mushroom\Core\Database\DbException
     */
    public static function getUserWithToken($token)
    {
        if ($tokenInfo = self::parsePasswordToken($token)) {
            list($email, $password) = $tokenInfo;
            return self::getProfile($email, $password);
        }
        return false;
    }

    /**
     * 获取资料
     *
     * @param $email
     * @param $password
     * @return bool|mixed
     * @throws \Mushroom\Core\Database\DbException
     */
    public static function getProfile($email, $password)
    {
        $user = self::where('email', $email)->find();
        var_dump($password);
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }
        return false;
    }

    /**
     * 获取用户基础资料
     * 一般用户聊天记录消息中
     *
     * @param $id
     * @return mixed
     */
    public static function getUserBasicProfile($id)
    {
        $user = self::column(['id', 'username', 'discriminator', 'avatar'])->find($id);
        if (isset($user['avatar']) && empty($user['avatar'])) {
            $user['avatar'] = "https://ss3.bdstatic.com/70cFv8Sh_Q1YnxGkpoWK1HF6hhy/it/u=2519824424,1132423651&fm=26&gp=0.jpg";
        }
        return $user;
    }

    /**
     * 搜索用户（公共接口）
     *
     * @param $username
     * @param $user
     * @return array|bool
     * @throws \Mushroom\Core\Database\DbException
     */
    public static function searchUserForPublic($username, $user)
    {
        $list = self::where('username', 'like', $username . '%')
            ->where('public_flags', 0)->column(['id', 'username', 'discriminator', 'avatar', 'bio'])->get();
        $friendId = [];
        if ($list) {
            $friendIdList = self::table('users_friends')->where('user_id', $user['id'])->column(['friend_id'])->get();
            foreach ($friendIdList as $item) {
                $friendId[] = $item['friend_id'];
            }
        }
        foreach ($list as &$user) {
            if (isset($user['avatar']) && empty($user['avatar'])) {
                $user['avatar'] = "https://ss3.bdstatic.com/70cFv8Sh_Q1YnxGkpoWK1HF6hhy/it/u=2519824424,1132423651&fm=26&gp=0.jpg";
            }
            $user['is_added'] = in_array($user['id'], $friendId) ? true : false;
        }
        return $list;
    }


}