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
    $feedItems = $ig->timeline->getTimelineFeed()->getFeedItems();
    $item = $feedItems[0]->getMediaOrAd();
    $previewComments = $item->getPreviewComments();
    $ig->event->sendOrganicMediaImpression($item, 'feed_timeline');
    foreach ($previewComments as $comment) {
        $ig->event->sendCommentImpression($item, $comment->getUserId(), $comment->getPk(), $comment->getCommentLikeCount());
    }
    $ig->event->sendNavigation('button', 'feed_timeline', 'comments_v2');
    $comments = $ig->media->getComments($item->getId())->getComments();
    $c = 0;
    foreach ($comments as $comment) {
        $ig->event->sendCommentImpression($item, $comment->getUserId(), $comment->getPk(), $comment->getCommentLikeCount());
        if ($c === 5) {
            break;
        }
        $c++;
    }
    $ig->media->likeComment($comments[0]->getPk(), 0);
    $ig->event->sendOrganicCommentLike($item, $comments[0]->getUser()->getPk(), $comments[0]->getPk());
    $ig->event->forceSendBatch();
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
