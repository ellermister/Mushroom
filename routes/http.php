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
    '/user/login/verify' => 'UserController@loginUser',
    '/user/profile' => 'UserController@getProfile',
    '/user/friends' => 'FriendController@friendList',
    '/user/contact' => 'UserController@getRecentMessage',

    // 贴图接口
    '/user/stickers' => 'UserController@getStickerList',

    // 搜索用户接口、搜索群组接口
    '/user/friend/search' => 'UserController@searchUserList',
    '/user/group/search' => 'UserController@searchGroupList',

    // 添加用户为好友
    '/user/friend/add' => 'FriendController@addUserToFriend',
    '/user/group/add' => 'UserController@addGroupToContact',

    // 用户消息获取
    '/user/message/find' => 'UserController@getUserMessage',
    '/group/message/find' => 'UserController@getGroupMessage',

];