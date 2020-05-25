<?php
return [
    '/' => 'HomeController@indexPage',
    '/ip' => 'IpController@getGuestIP',
    '/ip/country' => 'IpController@getAreaCode',
    '/user/friend' => 'UserController@getFriend',
    '/group/info' => 'UserController@getGroupInfo',
    '/group/join' => 'UserController@joinGroupPage',
];