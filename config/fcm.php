<?php

return [
    'driver' => env('FCM_PROTOCOL', 'http'),
    'log_enabled' => false,

    'http' => [
        'server_key' => env('FCM_SERVER_KEY', 'AAAAxAoRWyc:APA91bHVYeWNeHFqwO74C-W-uAJPeydy1XQSShbgq1dO___UW1g8kheoOP6EBi38L-aqMsV7RYw72KiGQL7qZv7IL301DxTUuwFp1Rh3XDfTZCshr217P0EnOQnFZOm4J73vvO7ACAjo'),
        'sender_id' => env('FCM_SENDER_ID', '841982499623'),
        'server_send_url' => 'https://fcm.googleapis.com/fcm/send',
        'server_group_url' => 'https://android.googleapis.com/gcm/notification',
        'timeout' => 30.0, // in second
    ],
];
