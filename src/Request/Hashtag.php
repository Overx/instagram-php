<?php

namespace InstagramAPI\Request;

use InstagramAPI\Exception\RequestHeadersTooLargeException;
use InstagramAPI\Response;
use InstagramAPI\Constants;
use InstagramAPI\Utils;

/**
 * Functions related to finding and exploring hashtags.
 */
class Hashtag extends RequestCollection
{
    /**
     * Get detailed hashtag information.
     *
     * @param string $hashtag The hashtag, not including the "#".
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\TagInfoResponse
     */
    public function getInfo(
        $hashtag)
    {
        Utils::throwIfInvalidHashtag($hashtag);
        $urlHashtag = urlencode($hashtag); // Necessary for non-English chars.
        return $this->ig->request("tags/{$urlHashtag}/info/")
            ->getResponse(new Response\TagInfoResponse());
    }

    /**
     * Get detailed hashtag information.
     *
     * @param string $hashtag The hashtag, not including the "#".
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\GraphqlResponse
     */
    public function getInfoGraph(
        $hashtag)
    {
        Utils::throwIfInvalidHashtag($hashtag);
        $urlHashtag = urlencode($hashtag); // Necessary for non-English chars.
        return $request = $this->ig->request("explore/tags/{$hashtag}/?__a=1")
            ->setVersion(5)
            ->setSignedPost(false)
            ->getResponse(new Response\GraphqlResponse());
    }

    /**
     * Get hashtag story.
     *
     * @param string $hashtag The hashtag, not including the "#".
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\TagsStoryResponse
     */
    public function getStory(
        $hashtag)
    {
        Utils::throwIfInvalidHashtag($hashtag);
        $urlHashtag = urlencode($hashtag); // Necessary for non-English chars.
        return $this->ig->request("tags/{$urlHashtag}/story/")
            ->getResponse(new Response\TagsStoryResponse());
    }

    /**
     * Get hashtags from a section.
     *
     * Available tab sections: 'top', 'recent' or 'places'.
     *
     * @param string      $hashtag      The hashtag, not including the "#".
     * @param string      $rankToken    The feed UUID. You must use the same value for all pages of the feed.
     * @param string|null $tab          Section tab for hashtags.
     * @param int[]|null  $nextMediaIds Used for pagination.
     * @param string|null $maxId        Next "maximum ID", used for pagination.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\TagFeedResponse
     */
    public function getSection(
        $hashtag,
        $rankToken,
        $tab = null,
        $nextMediaIds = null,
        $maxId = null)
    {
        Utils::throwIfInvalidHashtag($hashtag);
        $urlHashtag = urlencode($hashtag); // Necessary for non-English chars.

        $request = $this->ig->request("tags/{$urlHashtag}/sections/")
            ->setSignedPost(false)
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_csrftoken', $this->ig->client->getToken())
            ->addPost('rank_token', $rankToken)
            ->addPost('include_persistent', true);

        if ($tab !== null) {
            if ($tab !== 'top' && $tab !== 'recent' && $tab !== 'places' && $tab !== 'discover') {
                throw new \InvalidArgumentException('Tab section must be \'top\', \'recent\', \'places\' or \'discover\'.');
            }
            $request->addPost('tab', $tab);
        } else {
            $request->addPost('supported_tabs', '["top","recent","places","discover"]');
        }

        if ($nextMediaIds !== null) {
            if (!is_array($nextMediaIds) || !array_filter($nextMediaIds, 'is_int')) {
                throw new \InvalidArgumentException('Next media IDs must be an Int[].');
            }
            $request->addPost('next_media_ids', json_encode($nextMediaIds));
        }
        if ($maxId !== null) {
            $request->addPost('max_id', $maxId);
        }

        return $request->getResponse(new Response\TagFeedResponse());
    }

    /**
     * Search for hashtags.
     *
     * Gives you search results ordered by best matches first.
     *
     * Note that you can get more than one "page" of hashtag search results by
     * excluding the numerical IDs of all tags from a previous search query.
     *
     * Also note that the excludes must be done via Instagram's internal,
     * numerical IDs for the tags, which you can get from this search-response.
     *
     * Lastly, be aware that they will never exclude any tag that perfectly
     * matches your search query, even if you provide its exact ID too.
     *
     * @param string         $query       Finds hashtags containing this string.
     * @param string[]|int[] $excludeList Array of numerical hashtag IDs (ie "17841562498105353")
     *                                    to exclude from the response, allowing you to skip tags
     *                                    from a previous call to get more results.
     * @param string|null    $rankToken   (When paginating) The rank token from the previous page's response.
     *
     * @throws \InvalidArgumentException                  If invalid query or
     *                                                    trying to exclude too
     *                                                    many hashtags.
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\SearchTagResponse
     *
     * @see SearchTagResponse::getRankToken() To get a rank token from the response.
     * @see examples/paginateWithExclusion.php For an example.
     */
    public function search(
        $query,
        array $excludeList = [],
        $rankToken = null)
    {
        // Do basic query validation. Do NOT use throwIfInvalidHashtag here.
        if (!is_string($query) || $query === '') {
            throw new \InvalidArgumentException('Query must be a non-empty string.');
        }

        $request = $this->_paginateWithExclusion(
            $this->ig->request('tags/search/')
                ->addParam('q', $query)
                ->addParam('timezone_offset', (!is_null($this->ig->getTimezoneOffset())) ? $this->ig->getTimezoneOffset() : date('Z'))
                ->addParam('search_surface', 'hashtag_search_page')
                ->addParam('count', 30),
            $excludeList,
            $rankToken
        );

        try {
            /** @var Response\SearchTagResponse $result */
            $result = $request->getResponse(new Response\SearchTagResponse());
        } catch (RequestHeadersTooLargeException $e) {
            $result = new Response\SearchTagResponse([
                'has_more'   => false,
                'results'    => [],
                'rank_token' => $rankToken,
            ]);
        }

        return $result;
    }

    /**
     * Follow hashtag.
     *
     * @param string $hashtag The hashtag, not including the "#".
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\TagRelatedResponse
     */
    public function follow(
        $hashtag)
    {
        Utils::throwIfInvalidHashtag($hashtag);
        $urlHashtag = urlencode($hashtag); // Necessary for non-English chars.
        return $this->ig->request("tags/follow/{$urlHashtag}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Unfollow hashtag.
     *
     * @param string $hashtag The hashtag, not including the "#".
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\TagRelatedResponse
     */
    public function unfollow(
        $hashtag)
    {
        Utils::throwIfInvalidHashtag($hashtag);
        $urlHashtag = urlencode($hashtag); // Necessary for non-English chars.
        return $this->ig->request("tags/unfollow/{$urlHashtag}/")
            ->addPost('_uuid', $this->ig->uuid)
            ->addPost('_uid', $this->ig->account_id)
            ->addPost('_csrftoken', $this->ig->client->getToken())
            ->getResponse(new Response\GenericResponse());
    }

    /**
     * Get related hashtags.
     *
     * @param string $hashtag The hashtag, not including the "#".
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\TagRelatedResponse
     */
    public function getRelated(
        $hashtag)
    {
        Utils::throwIfInvalidHashtag($hashtag);
        $urlHashtag = urlencode($hashtag); // Necessary for non-English chars.
        return $this->ig->request("tags/{$urlHashtag}/related/")
            ->addParam('visited', '[{"id":"'.$hashtag.'","type":"hashtag"}]')
            ->addParam('related_types', '["hashtag"]')
            ->getResponse(new Response\TagRelatedResponse());
    }

    /**
     * Get the feed for a hashtag (DEPRECATED, use getSection() instead)
     *
     * @deprecated The feed endpoint has been removed in favor of the hashtag section API.
     * This function is now just wrapping Hashtag::getSection() and will be removed shortly.
     * Please use Hashtag::getSection().
     *
     * @param string      $hashtag   The hashtag, not including the "#".
     * @param string      $rankToken The feed UUID. You must use the same value for all pages of the feed.
     * @param string|null $maxId     Next "maximum ID", used for pagination.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\TagFeedResponse
     *
     * @see Hashtag::getSection() To see the function that will replace this one in the future.
     */
    public function getFeed(
        $hashtag,
        $rankToken,
        $maxId = null)
    {
        Utils::throwIfInvalidHashtag($hashtag);
        Utils::throwIfInvalidRankToken($rankToken);
        $urlHashtag = urlencode($hashtag); // Necessary for non-English chars.
        $hashtagFeed = $this->ig->request("feed/tag/{$urlHashtag}/")
            ->addParam('rank_token', $rankToken);
        if ($maxId !== null) {
            $hashtagFeed->addParam('max_id', $maxId);
        }

        return $hashtagFeed->getResponse(new Response\TagFeedResponse());
    }

    /**
     * Get the feed for a hashtag via web API
     *
     * @param string      $hashtag   The hashtag, not including the "#".
     * @param int         $next_page     Limit the userlist.
     * @param string|null $end_cursor    Next "maximum ID", used for pagination.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\GraphqlResponse
     */
    public function getFeedGraph(
        $hashtag,
        $next_page = 12,
        $end_cursor = null,
        $query_hash = "9b498c08113f1e09617a1703c22b2f32")
    {
        Utils::throwIfInvalidHashtag($hashtag);

        $request = $this->ig->request("graphql/query/")
            ->setVersion(5)
            ->setAddDefaultHeaders(false)
            ->setSignedPost(false)
            ->setIsBodyCompressed(false)
            ->addHeader('X-CSRFToken', $this->ig->client->getToken())
            ->addHeader('Referer', 'https://www.instagram.com/')
            ->addHeader('X-Requested-With', 'XMLHttpRequest')
            ->addHeader('X-IG-App-ID', Constants::IG_WEB_APPLICATION_ID);
            if ($this->ig->getIsAndroid()) {
                $request->addHeader('User-Agent', sprintf('Mozilla/5.0 (Linux; Android %s; Google) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Mobile Safari/537.36', $this->ig->device->getAndroidRelease()));
            } else {
                $request->addHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS ' . Constants::IOS_VERSION . ' like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.4 Mobile/15E148 Safari/604.1');
            }
            $request->addParam('query_hash', $query_hash)
                    ->addParam('variables', json_encode([
                        "tag_name" => $hashtag,
                        "first" => $next_page,
                        "after" => $end_cursor,
                    ]));
        return $request->getResponse(new Response\GraphqlResponse());
    }

    /**
     * Get list of tags that a user is following.
     *
     * @param string $userId Numerical UserPK ID.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\HashtagsResponse
     */
    public function getFollowing(
        $userId)
    {
        return $this->ig->request("users/{$userId}/following_tags_info/")
            ->getResponse(new Response\HashtagsResponse());
    }

    /**
     * Get list of which a hashtag is following with web API
     *
     * @param string      $userId        Numerical UserPK ID.
     * 
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\GraphqlResponse
     */
    public function getFollowingHashtagGraph(
        $userId)
    {
        if ($userId == null) {
            throw new \InvalidArgumentException('Empty $userId sent to getFollowingHashtagGraph() function.');
        }

        return $request = $this->ig->request("graphql/query/")
            ->setVersion(5)
            ->setSignedPost(false)
            ->addParam('query_hash', 'e6306cc3dbe69d6a82ef8b5f8654c50b')
            ->addParam('variables', json_encode([
                "id" => $userId
            ]))
            ->getResponse(new Response\GraphqlResponse());
    }

    /**
     * Get list of tags that you are following.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\HashtagsResponse
     */
    public function getSelfFollowing()
    {
        return $this->getFollowing($this->ig->account_id);
    }

    /**
     * Get list of tags that are suggested to follow to.
     *
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\HashtagsResponse
     */
    public function getFollowSuggestions()
    {
        return $this->ig->request('tags/suggested/')
            ->getResponse(new Response\HashtagsResponse());
    }

    /**
     * Mark TagFeedResponse story media items as seen.
     *
     * The "story" property of a `TagFeedResponse` only gives you a list of
     * story media. It doesn't actually mark any stories as "seen", so the
     * user doesn't know that you've seen their story. Actually marking the
     * story as "seen" is done via this endpoint instead. The official app
     * calls this endpoint periodically (with 1 or more items at a time)
     * while watching a story.
     *
     * This tells the user that you've seen their story, and also helps
     * Instagram know that it shouldn't give you those seen stories again
     * if you request the same hashtag feed multiple times.
     *
     * Tip: You can pass in the whole "getItems()" array from the hashtag's
     * "story" property, to easily mark all of the TagFeedResponse's story
     * media items as seen.
     *
     * @param Response\TagFeedResponse $hashtagFeed The hashtag feed response
     *                                              object which the story media
     *                                              items came from. The story
     *                                              items MUST belong to it.
     * @param Response\Model\Item[]    $items       Array of one or more story
     *                                              media Items.
     *
     * @throws \InvalidArgumentException
     * @throws \InstagramAPI\Exception\InstagramException
     *
     * @return \InstagramAPI\Response\MediaSeenResponse
     *
     * @see Story::markMediaSeen()
     * @see Location::markStoryMediaSeen()
     */
    public function markStoryMediaSeen(
        Response\TagsStoryResponse $hashtagFeed,
        array $items)
    {
        // Extract the Hashtag Story-Tray ID from the user's hashtag response.
        // NOTE: This can NEVER fail if the user has properly given us the exact
        // same hashtag response that they got the story items from!
        $sourceId = '';
        if ($hashtagFeed->getStory() instanceof Response\Model\StoryTray) {
            $sourceId = $hashtagFeed->getStory()->getId();
        }
        if (!strlen($sourceId)) {
            throw new \InvalidArgumentException('Your provided TagFeedResponse is invalid and does not contain any Hashtag Story-Tray ID.');
        }

        // Ensure they only gave us valid items for this hashtag response.
        // NOTE: We validate since people cannot be trusted to use their brain.
        $validIds = [];
        foreach ($hashtagFeed->getStory()->getItems() as $item) {
            $validIds[$item->getId()] = true;
        }
        foreach ($items as $item) {
            // NOTE: We only check Items here. Other data is rejected by Internal.
            if ($item instanceof Response\Model\Item && !isset($validIds[$item->getId()])) {
                throw new \InvalidArgumentException(sprintf(
                    'The item with ID "%s" does not belong to this TagFeedResponse.',
                    $item->getId()
                ));
            }
        }

        // Mark the story items as seen, with the hashtag as source ID.
        return $this->ig->internal->markStoryMediaSeen($items, $sourceId);
    }
}