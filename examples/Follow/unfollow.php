<?php

set_time_limit(0);
date_default_timezone_set('UTC');

require __DIR__.'/../vendor/autoload.php';

/////// CONFIG ///////
$username = '';
$password = '';
$debug = true;
$truncatedDebug = false;
//////////////////////

//////////////////////
$userId = '';
//////////////////////

$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);

try {
    $ig->login($username, $password);
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit(0);
}

try {
    $ig->event->sendNavigation('button', 'feed_timeline', 'profile');
    $ig->people->getFriendship($userId);
    $ig->highlight->getUserFeed($userId);
    $ig->people->getInfoById($userId);
    $ig->story->getUserStoryFeed($userId);
    $ig->event->sendProfileView($userId);
    $ig->event->sendFollowButtonTapped($userId);
    $ig->people->unfollow($userId);
    $navstack = [
        [
            'module'        => 'feed_timeline',
            'click_point'   => 'main_search',
        ],
        [
            'module'        => 'explore_popular',
            'click_point'   => 'explore_topic_load',
        ],
        [
            'module'        => 'explore_popular',
            'click_point'   => 'button',
        ],
        [
            'module'        => 'blended_search',
            'click_point'   => 'button',
        ],
        [
            'module'        => 'blended_search',
            'click_point'   => 'search_result',
        ],
    ];
    $ig->event->sendProfileAction('unfollow', $userId, $navstack);
    $ig->event->forceSendBatch();
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
