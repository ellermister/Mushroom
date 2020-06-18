<?php
return [
    '/' => 'HomeController@indexPage',
    '/ip' => 'IpController@getGuestIP',
    '/ip/country' => 'IpController@getAreaCode',
//    '/user/friend' => 'UserController@getFriend',
    '/group/info' => 'UserController@getGroupInfo',
    '/group/join' => 'UserController@joinGroupPage',
    '/user/test' => 'UserController@test',

    '/user/register/guest' => 'UserController@registerGuestUser',
    '/user/guest/verify' => 'UserController@verifyGuest',
    '/user/profile' => 'UserController@getProfile',
    '/user/friends' => 'FriendController@friendList',
    '/user/contact' => 'UserController@getRecentMessage',
];