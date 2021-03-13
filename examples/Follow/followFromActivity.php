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


$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);

try {
    $ig->login($username, $password);
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit(0);
}

try {
    $ig->event->sendNavigation('main_inbox', 'feed_timeline', 'newsfeed_you');

    $suggedtedUsers = $ig->people->getRecentActivityInbox()->getSuggestedUsers()->getSuggestionCards();

    $users = [];
    foreach ($suggedtedUsers as $suggestedUser) {
        $users[] = $suggestedUser->getUserCard()->getUser();
    }

    $userId = $users[0]->getPk();

    $ig->people->getFriendship($userId);
    $ig->highlight->getUserFeed($userId);
    $ig->people->getInfoById($userId);
    $ig->story->getUserStoryFeed($userId);
    $ig->event->sendProfileView($userId);
    $ig->event->sendFollowButtonTapped($userId);
    $ig->people->follow($userId);
    $navstack = [
        [
            'module'        => 'feed_timeline',
            'click_point'   => 'newsfeed_you',
        ],
    ];
    $ig->event->sendProfileAction('follow', $userId, $navstack);
    $ig->event->forceSendBatch();
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
