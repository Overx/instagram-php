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

/////// MEDIA ////////
$photoFilename = '';
$captionText = '';
//////////////////////

$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);

try {
    $ig->login($username, $password);
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit(0);
}

try {
    // The most basic upload command, if you're sure that your photo file is
    // valid on Instagram (that it fits all requirements), is the following:
    // $ig->timeline->uploadPhoto($photoFilename, ['caption' => $captionText]);

    // However, if you want to guarantee that the file is valid (correct format,
    // width, height and aspect ratio), then you can run it through our
    // automatic photo processing class. It is pretty fast, and only does any
    // work when the input file is invalid, so you may want to always use it.
    // You have nothing to worry about, since the class uses temporary files if
    // the input needs processing, and it never overwrites your original file.
    //
    // Also note that it has lots of options, so read its class documentation!
    $photo = new \InstagramAPI\Media\Photo\InstagramPhoto($photoFilename);

    // Click on the camera icon and move to the gallery pick or camera.
    $ig->event->sendNavigation('main_camera', 'feed_timeline', 'tabbed_gallery_camera');
    $startTime = round(microtime(true) * 1000);
    $waterfallId = \InstagramAPI\Signatures::generateUUID();
    // Open Photo camera tab
    $ig->event->sendOpenPhotoCameraTab($waterfallId, $startTime, round(microtime(true) * 1000));
    // Click on shutter (click to capture).
    $ig->event->sendShutterClickInCamera($waterfallId, $startTime, round(microtime(true) * 1000));
    // Start a new session ID for the gallery edit.
    $editSessionId = \InstagramAPI\Signatures::generateUUID();
    $ig->event->sendStartGalleryEditSession($editSessionId);
    // Navigate to the filter module.
    $ig->event->sendNavigation('button', 'tabbed_gallery_camera', 'photo_filter');
    // Send filter photo event.
    $ig->event->sendFilterPhoto($waterfallId, $startTime, round(microtime(true) * 1000));
    // Send IG Media creation. Media is being generated.
    $igMediaCreationWaterfallId = \InstagramAPI\Signatures::generateUUID();
    $ig->event->sendIGMediaCreation($igMediaCreationWaterfallId, $startTime, round(microtime(true) * 1000), 'photo');
    // Finish media filter. We don't apply any filter.
    $ig->event->sendFilterFinish($waterfallId, $startTime, round(microtime(true) * 1000));
    // End edit gallery session.
    $ig->event->sendEndGalleryEditSession($editSessionId);
    // Start share session. Now the image will be processed,
    $ig->event->sendStartShareSession($editSessionId);
    // And now we tell Instagram we will start uploading the media.
    $ig->event->sendShareMedia($waterfallId, $startTime, round(microtime(true) * 1000));
    //TODO; uploadPhoto should internally call some events related to the media ingest,
    //TODO; upload success, media publish and related.

    $ig->timeline->uploadPhoto($photo->getFile(), ['caption' => $captionText, 'waterfall_id' => $waterfallId]);
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
