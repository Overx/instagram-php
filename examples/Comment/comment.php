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

//////////////////////
$commentText = 'text';
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
    $comment = $ig->media->comment($item->getId(), $commentText)->getComment();
    $ig->event->sendOrganicComment($item, true, mt_rand(1, 3));
    $ig->event->sendCommentCreate();
    $ig->event->sendCommentImpression($item, $comment->getUser()->getPk(), $comment->getPk(), 0);
    $ig->event->forceSendBatch();
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
