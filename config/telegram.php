<?php
return [
    'bot_api_key'  => env('BOT_API_KEY'),
    'bot_username' => env('BOT_USERNAME'),

    'block_keywords' => [
        'text' => [
            '服务器出租',
            '跟我聯絡',
            '现金奖励',
        ],
        'preg' => [
            '.*?可以找我哦',
            '.*?可以找我哦',
        ]
    ]
];