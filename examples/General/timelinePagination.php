<?php

set_time_limit(0);
date_default_timezone_set('UTC');

require __DIR__.'/../../vendor/autoload.php';

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
    $maxId = null;
    $mediaDepth = 0;
    do {
        $feed = $ig->timeline->getTimelineFeed($maxId);
        if ($maxId !== null) {
            $ig->event->sendEndMainFeedRequest($mediaDepth);
        }
        $maxId = $feed->getNextMaxId();
        $mediaDepth += count($feed->getFeedItems());

        foreach ($feed->getFeedItems() as $item) {
            if ($item->getMediaOrAd() !== null) {
                $ig->event->sendOrganicMediaImpression($item->getMediaOrAd(), 'feed_timeline');
            }
        }

        $ig->event->sendStartMainFeedRequest($mediaDepth);
        $ig->event->sendMainFeedLoadingMore(round(microtime(true) * 1000), $mediaDepth);
    } while ($maxId !== null);
    $ig->event->forceSendBatch();
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
