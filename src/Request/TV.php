<?php

namespace InstagramAPI\Request;

use InstagramAPI\Constants;
use InstagramAPI\Response;

/**
 * Functions related to Instagram TV.
 */
class TV extends RequestCollection
{
    /**
     * Get Instagram TV feed.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\TVGuideResponse
     */
    public function getTvFeed()
    {
        $response = $this->ig->request('igtv/browse_feed/')
            ->addParam('prefetch', 1)
            ->addParam('banner_token', $this->ig->settings->get('banner_token'))
            ->getResponse(new Response\TVGuideResponse());

        if ($response->getBannerToken() !== $this->ig->settings->get('banner_token')) {
            $this->ig->settings->set('banner_token', $response->getBannerToken());
        }

        return $response;
    }

    /**
     * Get non prefetched Instagram TV feed.
     *
     * @param string|null $maxId Next "maximum ID", used for pagination.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\TVGuideResponse
     */
    public function getNonPrefetchFeed(
        $maxId = null)
    {
        $request = $this->ig->request('igtv/non_prefetch_browse_feed/');

        if ($maxId !== null) {
            $request->addParam('max_id', $maxId);
        }

        $response = $request->getResponse(new Response\TVGuideResponse());

        if ($response->getBannerToken() !== $this->ig->settings->get('banner_token')) {
            $this->ig->settings->set('banner_token', $response->getBannerToken());
        }

        return $response;
    }

    /**
     * Get Instagram TV guide.
     * 
     * @deprecated This endpoint has been removed in favor of getTvFeed() and getNonPrefetchFeed().
     *
     * It provides a catalogue of popular and suggested channels.
     *
     * @param array|null $options An associative array with following keys (all
     *                            of them are optional):
     *                            "is_charging" Wether the device is being charged
     *                            or not. Valid values: 0 for not charging, 1 for
     *                            charging.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\TVGuideResponse
     */
    public function getTvGuide(
        array $options = null)
    {
        $request = $this->ig->request('igtv/tv_guide/')
            ->addHeader('X-Ads-Opt-Out', '0')
            ->addHeader('X-Google-AD-ID', $this->ig->advertising_id)
            ->addHeader('X-DEVICE-ID', $this->ig->uuid)
            ->addParam('prefetch', 1)
            ->addParam('phone_id', $this->ig->phone_id)
            ->addPost('battery_level', $this->ig->getBatteryLevel())
            ->addPost('is_charging', $this->ig->getIsDeviceCharging())
            ->addParam('banner_token', $this->ig->settings->get('banner_token'))
            ->addParam('will_sound_on', '1');

        if (isset($options['is_charging'])) {
            $request->addParam('is_charging', $options['is_charging']);
        } else {
            $request->addParam('is_charging', '0');
        }

        $response = $request->getResponse(new Response\TVGuideResponse());

        if ($response->getBannerToken() !== $this->ig->settings->get('banner_token')) {
            $this->ig->settings->set('banner_token', $response->getBannerToken());
        }

        return $response;
    }

    /**
     * Get Instagram TV Feed.
     *
     * @param bool $prefetch (optional) Indicates if the request is called from prefetch.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\TVGuideResponse
     */
    public function getBrowseFeed(
        $prefetch = false)
    {
        $request = $this->ig->request('igtv/browse_feed/')
            ->addParam('prefetch', 1)
            ->addParam('banner_token', $this->ig->settings->get('banner_token'));
        if ($prefetch) {
            $request->addHeader('X-IG-Prefetch-Request', 'foreground');
        }

        return $request->getResponse(new Response\TVGuideResponse());
    }

    /**
     * Get channel.
     *
     * You can filter the channel with different IDs: 'for_you', 'chrono_following', 'popular', 'continue_watching'
     * and using a user ID in the following format: 'user_1234567891'.
     *
     * @param string      $id    ID used to filter channels.
     * @param string|null $maxId Next "maximum ID", used for pagination.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\TVChannelsResponse
     */
    public function getChannel(
        $id = 'for_you',
        $maxId = null)
    {
        if (!in_array($id, ['for_you', 'chrono_following', 'popular', 'continue_watching'])
        && !preg_match('/^user_[1-9]\d*$/', $id)) {
            throw new \InvalidArgumentException('Invalid ID type.');
        }

        $request = $this->ig->request('igtv/channel/')
            ->addHeader('X-Ads-Opt-Out', '0')
            ->addHeader('X-Google-AD-ID', $this->ig->advertising_id)
            ->addHeader('X-DEVICE-ID', $this->ig->uuid)
            ->addParam('phone_id', $this->ig->phone_id)
            ->addPost('battery_level', $this->ig->getBatteryLevel())
            ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('id', $id)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('is_charging', $this->ig->getIsDeviceCharging())
            ->addPost('is_dark_mode', '0')
            ->addPost('will_sound_on', '0');

        if ($maxId !== null) {
            $request->addPost('max_id', $maxId);
        }

        return $request->getResponse(new Response\TVChannelsResponse());
    }

    /**
     * Uploads a video to your Instagram TV.
     *
     * @param string $videoFilename    The video filename.
     * @param array  $externalMetadata (optional) User-provided metadata key-value pairs.
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \InstagramAPI\Exception\InstagramException
     * @throws \InstagramAPI\Exception\UploadFailedException If the video upload fails.
     *
     * @return \InstagramAPI\Response\ConfigureResponse
     *
     * @see Internal::configureSingleVideo() for available metadata fields.
     */
    public function uploadVideo(
        $videoFilename,
        array $externalMetadata = [])
    {
        return $this->ig->internal->uploadSingleVideo(Constants::FEED_TV, $videoFilename, null, $externalMetadata);
    }

    /**
     * Searches for channels.
     *
     * @param string $query The username or channel you are looking for.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\TVSearchResponse
     */
    public function search(
        $query = '')
    {
        if ($query !== '') {
            $endpoint = 'igtv/search/';
        } else {
            $endpoint = 'igtv/suggested_searches/';
        }

        return $this->ig->request($endpoint)
            ->addParam('query', $query)
            ->getResponse(new Response\TVSearchResponse());
    }

    /**
     * Write seen state on a video.
     *
     * @param string $impression      Format: 1813637917462151382
     * @param int    $viewProgress    Video view progress in seconds.
     * @param mixed  $gridImpressions TODO No info yet.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\GenericResponse
     */
    public function writeSeenState(
        $impression,
        $viewProgress = 0,
        $gridImpressions = [])
    {
        if (!ctype_digit($viewProgress) && (!is_int($viewProgress) || $viewProgress < 0)) {
            throw new \InvalidArgumentException('View progress must be a positive integer.');
        }

        $seenState = json_encode([
            'impressions'       => [
                $impression => [
                    'view_progress_s'   => $viewProgress,
                ],
            ],
            'grid_impressions'  => $gridImpressions,
        ]);

        return $this->ig->request('igtv/write_seen_state/')
            ->addPost('seen_state', $seenState)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());
    }
}
