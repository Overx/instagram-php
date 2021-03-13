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

// Get the profile picture pic url to display on the question.
$profilePicUrl = $ig->account->getCurrentUser()->getUser()->getProfilePicUrl();

// Now create the metadata array:
$metadata = [
    'story_questions' => [
        // Note that you can only do one story question in this array.
        [
            'x'                     => 0.5, // Range: 0.0 - 1.0. Note that x = 0.5 and y = 0.5 is center of screen.
            'y'                     => 0.5004223, // Also note that X/Y is setting the position of the CENTER of the clickable area.
            'z'                     => 0, // Don't change this value.
            'width'                 => 0.63118356, // Clickable area size, as percentage of image size: 0.0 - 1.0
            'height'                => 0.22212838, // ...
            'rotation'              => 0.0,
            'viewer_can_interact'   => false, // Don't change this value.
            'background_color'      => '#ffffff',
            'profile_pic_url'       => $profilePicUrl, // Must be the profile pic url of the account you are posting from!
            'question_type'         => 'text', // Don't change this value.
            'question'              => 'What do you want to see in the API?', // Story question.
            'text_color'            => '#000000',
            'is_sticker'            => true, // Don't change this value.
        ],
    ],
];

try {
    $temp = tmpfile();
    fwrite($temp, $ig->creative->getDefaultInstagramBackground());
    $photoFilename = stream_get_meta_data($temp)['uri'];
    $photo = new \InstagramAPI\Media\Photo\InstagramPhoto($photoFilename, ['targetFeed' => \InstagramAPI\Constants::FEED_STORY]);
    $ig->story->uploadPhoto($photo->getFile(), $metadata);
    fclose($temp);
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
