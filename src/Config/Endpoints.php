<?php

declare(strict_types=1);

namespace SandeepV\WrapsplashPHP\Config;

final class Endpoints
{
    public const API_LOCATION = 'https://api.unsplash.com/';
    public const BEARER_TOKEN_URL = 'https://unsplash.com/oauth/token';

    // Users
    public const USERS_PUBLIC_PROFILE = 'users/{username}';
    public const USERS_PORTFOLIO = 'users/{username}/portfolio';
    public const USERS_PHOTOS = 'users/{username}/photos';
    public const USERS_LIKED_PHOTOS = 'users/{username}/likes';
    public const USERS_COLLECTIONS = 'users/{username}/collections';
    public const USERS_STATISTICS = 'users/{username}/statistics';

    // Photos
    public const LIST_PHOTOS = 'photos';
    public const LIST_CURATED_PHOTOS = 'photos/curated';
    public const GET_A_PHOTO = 'photos/{id}';
    public const GET_A_RANDOM_PHOTO = 'photos/random';
    public const GET_A_PHOTO_STATISTICS = 'photos/{id}/statistics';
    public const GET_A_PHOTO_DOWNLOAD_LINK = 'photos/{id}/download';
    public const UPDATE_A_PHOTO = 'photos/{id}';
    public const LIKE_A_PHOTO = 'photos/{id}/like';
    public const UNLIKE_A_PHOTO = 'photos/{id}/like';

    // Search
    public const SEARCH_PHOTOS = 'search/photos';
    public const SEARCH_COLLECTIONS = 'search/collections';
    public const SEARCH_USERS = 'search/users';

    // Current User
    public const CURRENT_USER_PROFILE = 'me';
    public const UPDATE_CURRENT_USER_PROFILE = 'me';

    // Stats
    public const STATS_TOTALS = 'stats/total';
    public const STATS_MONTH = 'stats/month';

    // Collections
    public const LIST_COLLECTIONS = 'collections';
    public const LIST_FEATURED_COLLECTIONS = 'collections/featured';
    public const LIST_CURATED_COLLECTIONS = 'collections/curated';
    public const GET_COLLECTION = 'collections/{id}';
    public const GET_CURATED_COLLECTION = 'collections/curated/{id}';
    public const GET_COLLECTION_PHOTOS = 'collections/{id}/photos';
    public const GET_CURATED_COLLECTION_PHOTOS = 'collections/curated/{id}/photos';
    public const LIST_RELATED_COLLECTION = 'collections/{id}/related';
    public const CREATE_NEW_COLLECTION = 'collections';
    public const UPDATE_EXISTING_COLLECTION = 'collections/{id}';
    public const DELETE_COLLECTION = 'collections/{id}';
    public const ADD_PHOTO_TO_COLLECTION = 'collections/{collection_id}/add';
    public const REMOVE_PHOTO_FROM_COLLECTION = 'collections/{collection_id}/remove';
}
