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
    // For this example we are harcoding a media ID, which is a video
    // from the user 'selenagomez'. Ideally, you don't want to do this, you
    // would need to load the user profile and get the feed, and from there find
    // the video you are looking for. Once then you can follow this example to proceed
    // with the event implementation.
    // 1) You should send navigation to the user profile.
    // 2) You should send instagram_thumbnail_click.
    // 3) Navigation from 'profile' to 'feed_contextual_profile' via 'button'.
    $mediaId = '2182224389000227068_460563723'; // Video ID.
    // Get media Item.
    $item = $ig->media->getInfo($mediaId)->getItems()[0];
    // Send video action. Video has been displayed.
    $ig->event->sendVideoAction('video_displayed', $item, 'feed_contextual_profile');
    // We are sending an orgamic media impression, just the media itself (not viewing the video).
    $ig->event->sendOrganicMediaImpression($item, 'feed_contextual_profile');
    $viewSession = \InstagramAPI\Signatures::generateUUID(); // view session ID.
    $seq = 1; // Sequence ID.
    // Send event. Now video should start playing.
    $ig->event->sendVideoAction('video_should_start', $item, 'feed_contextual_profile', ['viewer_session_id' => $viewSession, 'seq' => $seq]);
    ++$seq; // Increase sequence ID.
    // Video starts buffering.
    $ig->event->sendVideoAction('video_buffering_started', $item, 'feed_contextual_profile', ['viewer_session_id' => $viewSession, 'seq' => $seq]);
    ++$seq;
    // Video started playing
    $ig->event->sendVideoAction('video_started_playing', $item, 'feed_contextual_profile', ['viewer_session_id' => $viewSession, 'seq' => $seq]);
    ++$seq;
    // Video Paused (because we clicked on it or we just moved on).
    $ig->event->sendVideoAction('video_paused', $item, 'feed_contextual_profile', ['viewer_session_id' => $viewSession, 'seq' => $seq]);
    ++$seq;
    // Video Paused (because we clicked on it or we just moved on).
    $ig->event->sendVideoAction('video_exited', $item, 'feed_contextual_profile', ['viewer_session_id' => $viewSession, 'seq' => $seq]);
    ++$seq;
    // We are sending the organic viewed impression. You need to specify wether you are following the
    // owner of the media in this case 'selenagomez'.
    $ig->event->sendOrganicViewedImpression($item, 'feed_contextual_profile', null, null, null, ['following' => false]);
    // forceSendBatch() should be only used if you are "closing" the app so all the events that
    // are queued will be sent. Batch event will automatically be sent when it reaches 50 events.
    $ig->event->forceSendBatch();
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
