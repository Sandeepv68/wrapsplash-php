<?php

declare(strict_types=1);

namespace SandeepV\WrapsplashPHP;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use SandeepV\WrapsplashPHP\Config\Endpoints;
use SandeepV\WrapsplashPHP\Enums\PhotoOrder;
use SandeepV\WrapsplashPHP\Enums\PhotoOrientation;

class WrapSplash
{
    private string $accessToken = '';
    private string $secretKey = '';
    private string $redirectUri = '';
    private string $code = '';
    private string $bearerToken = '';
    private int $timeout = 10000;
    private int $retries = 2;
    private int $retryDelayMs = 100;
    private array $headers = [];
    private ?Client $httpClient = null;
    private bool $initialized = false;

    private const GRANT_TYPE = 'authorization_code';

    public function __construct(?Client $httpClient = null)
    {
        if ($httpClient !== null) {
            $this->httpClient = $httpClient;
        }
    }

    /**
     * Initialize the client with a Configuration object.
     *
     * @throws WrapSplashException
     */
    public function init(?Configuration $config = null): void
    {
        if ($config === null) {
            throw new WrapSplashException('Configuration is required!');
        }

        $config->validate();

        $this->timeout = $config->timeout > 0 ? $config->timeout : 10000;
        $this->retries = $config->retries >= 0 ? $config->retries : 2;
        $this->retryDelayMs = $config->retryDelayMs >= 0 ? $config->retryDelayMs : 100;

        $this->headers = [
            'Content-Type' => 'application/json',
            'X-Requested-With' => 'WrapSplashPHP',
        ];

        if ($config->bearerToken !== null) {
            $this->bearerToken = $config->bearerToken;
            $this->headers['Authorization'] = 'Bearer ' . $config->bearerToken;
            $this->headers['X-WrapSplash-Header'] = $this->computeHash($config->bearerToken);
            $this->initialized = true;
            $this->ensureHttpClient();
            return;
        }

        $this->accessToken = $config->accessToken ?? '';
        $this->secretKey = $config->secretKey ?? '';
        $this->redirectUri = $config->redirectUri ?? '';
        $this->code = $config->code ?? '';

        $this->headers['Authorization'] = 'Client-ID ' . $this->accessToken;
        $this->headers['X-WrapSplash-Header'] = $this->computeHash($this->accessToken);
        $this->initialized = true;
        $this->ensureHttpClient();
    }

    /**
     * Convenience factory: create and initialize with a bearer token.
     */
    public static function withBearerToken(string $token, int $timeout = 10000, int $retries = 2, int $retryDelayMs = 100): self
    {
        $instance = new self();
        $instance->init(new Configuration(
            bearerToken: $token,
            timeout: $timeout,
            retries: $retries,
            retryDelayMs: $retryDelayMs,
        ));
        return $instance;
    }

    /**
     * Convenience factory: create and initialize with full credentials.
     *
     * @throws WrapSplashException
     */
    public static function withCredentials(
        string $accessToken,
        string $secretKey,
        string $redirectUri,
        string $code,
        int $timeout = 10000,
        int $retries = 2,
        int $retryDelayMs = 100,
    ): self {
        $instance = new self();
        $instance->init(new Configuration(
            accessToken: $accessToken,
            secretKey: $secretKey,
            redirectUri: $redirectUri,
            code: $code,
            timeout: $timeout,
            retries: $retries,
            retryDelayMs: $retryDelayMs,
        ));
        return $instance;
    }

    // ================================================================
    // Authentication
    // ================================================================

    /**
     * Exchange the authorization code for a bearer token.
     *
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function generateBearerToken(): array
    {
        $this->ensureInitialized();
        $this->validateRequired($this->accessToken, 'access_key');
        $this->validateRequired($this->secretKey, 'secret_key');
        $this->validateRequired($this->redirectUri, 'redirect_uri');
        $this->validateRequired($this->code, 'code');

        return $this->fetchUrl(Endpoints::BEARER_TOKEN_URL, 'POST', [
            'client_id' => $this->accessToken,
            'client_secret' => $this->secretKey,
            'redirect_uri' => $this->redirectUri,
            'code' => $this->code,
            'grant_type' => self::GRANT_TYPE,
        ]);
    }

    // ================================================================
    // Current User
    // ================================================================

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getCurrentUserProfile(): array
    {
        $this->ensureInitialized();
        return $this->fetchUrl(self::api(Endpoints::CURRENT_USER_PROFILE), 'GET');
    }

    /**
     * @param array<string, string> $params
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function updateCurrentUserProfile(array $params = []): array
    {
        $this->ensureInitialized();
        return $this->fetchUrl(self::api(Endpoints::UPDATE_CURRENT_USER_PROFILE), 'PUT', $params);
    }

    // ================================================================
    // Users
    // ================================================================

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getPublicProfile(string $username, ?int $width = null, ?int $height = null): array
    {
        $this->ensureInitialized();
        $this->validateRequired($username, 'username');

        return $this->fetchUrl(self::api(Endpoints::USERS_PUBLIC_PROFILE) . $username, 'GET', [
            'w' => $width,
            'h' => $height,
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getUserPortfolio(string $username): array
    {
        $this->ensureInitialized();
        $this->validateRequired($username, 'username');

        return $this->fetchUrl(self::api(self::replaceUsername(Endpoints::USERS_PORTFOLIO, $username)), 'GET');
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getUserPhotos(
        string $username,
        int $page = 1,
        int $perPage = 10,
        ?bool $stats = null,
        ?string $resolution = null,
        ?int $quantity = null,
        ?PhotoOrder $orderBy = null,
    ): array {
        $this->ensureInitialized();
        $this->validateRequired($username, 'username');

        return $this->fetchUrl(self::api(self::replaceUsername(Endpoints::USERS_PHOTOS, $username)), 'GET', [
            'page' => $page,
            'per_page' => $perPage,
            'order_by' => ($orderBy?->value) ?? 'latest',
            'stats' => $stats ?? false,
            'resolution' => $resolution ?? 'days',
            'quantity' => $quantity ?? 30,
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getUserLikedPhotos(
        string $username,
        int $page = 1,
        int $perPage = 10,
        ?PhotoOrder $orderBy = null,
    ): array {
        $this->ensureInitialized();
        $this->validateRequired($username, 'username');

        return $this->fetchUrl(self::api(self::replaceUsername(Endpoints::USERS_LIKED_PHOTOS, $username)), 'GET', [
            'page' => $page,
            'per_page' => $perPage,
            'order_by' => ($orderBy?->value) ?? 'latest',
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getUserCollections(string $username, int $page = 1, int $perPage = 10): array
    {
        $this->ensureInitialized();
        $this->validateRequired($username, 'username');

        return $this->fetchUrl(self::api(self::replaceUsername(Endpoints::USERS_COLLECTIONS, $username)), 'GET', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getUserStatistics(string $username, ?string $resolution = null, ?int $quantity = null): array
    {
        $this->ensureInitialized();
        $this->validateRequired($username, 'username');

        return $this->fetchUrl(self::api(self::replaceUsername(Endpoints::USERS_STATISTICS, $username)), 'GET', [
            'resolution' => $resolution ?? 'days',
            'quantity' => $quantity ?? 30,
        ]);
    }

    // ================================================================
    // Photos
    // ================================================================

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function listPhotos(int $page = 1, int $perPage = 10, ?PhotoOrder $orderBy = null): array
    {
        $this->ensureInitialized();
        return $this->fetchUrl(self::api(Endpoints::LIST_PHOTOS), 'GET', [
            'page' => $page,
            'per_page' => $perPage,
            'order_by' => ($orderBy?->value) ?? 'latest',
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function listCuratedPhotos(int $page = 1, int $perPage = 10, ?PhotoOrder $orderBy = null): array
    {
        $this->ensureInitialized();
        return $this->fetchUrl(self::api(Endpoints::LIST_CURATED_PHOTOS), 'GET', [
            'page' => $page,
            'per_page' => $perPage,
            'order_by' => ($orderBy?->value) ?? 'latest',
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getPhoto(string $id, ?int $width = null, ?int $height = null, ?string $rect = null): array
    {
        $this->ensureInitialized();
        $this->validateRequired($id, 'id');

        return $this->fetchUrl(self::api(self::replaceId(Endpoints::GET_A_PHOTO, $id)), 'GET', [
            'w' => $width,
            'h' => $height,
            'rect' => $rect,
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getRandomPhoto(
        string|int|null $collections = null,
        ?bool $featured = null,
        ?string $username = null,
        ?string $query = null,
        ?int $width = null,
        ?int $height = null,
        ?PhotoOrientation $orientation = null,
        ?int $count = null,
    ): array {
        $this->ensureInitialized();

        return $this->fetchUrl(self::api(Endpoints::GET_A_RANDOM_PHOTO), 'GET', [
            'collections' => $collections !== null ? (string) $collections : null,
            'featured' => $featured ?? false,
            'username' => $username,
            'query' => $query,
            'width' => $width,
            'height' => $height,
            'orientation' => ($orientation?->value) ?? 'landscape',
            'count' => $count ?? 1,
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getPhotoStatistics(string $id, ?string $resolution = null, ?int $quantity = null): array
    {
        $this->ensureInitialized();
        $this->validateRequired($id, 'id');

        return $this->fetchUrl(self::api(self::replaceId(Endpoints::GET_A_PHOTO_STATISTICS, $id)), 'GET', [
            'resolution' => $resolution ?? 'days',
            'quantity' => $quantity ?? 30,
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getPhotoDownloadLink(string $id): array
    {
        $this->ensureInitialized();
        $this->validateRequired($id, 'id');

        return $this->fetchUrl(self::api(self::replaceId(Endpoints::GET_A_PHOTO_DOWNLOAD_LINK, $id)), 'GET');
    }

    /**
     * @param array<string, mixed> $location
     * @param array<string, mixed> $exif
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function updatePhoto(string $id, array $location = [], array $exif = []): array
    {
        $this->ensureInitialized();
        $this->validateRequired($id, 'id');

        $params = $this->flattenNested('location', $location, [
            'latitude', 'longitude', 'name', 'city', 'country', 'confidential',
        ]);
        $params = array_merge($params, $this->flattenNested('exif', $exif, [
            'make', 'model', 'exposure_time', 'aperture_value', 'focal_length', 'iso_speed_ratings',
        ]));

        return $this->fetchUrl(self::api(self::replaceId(Endpoints::UPDATE_A_PHOTO, $id)), 'PUT', $params);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function likePhoto(string $id): array
    {
        $this->ensureInitialized();
        $this->validateRequired($id, 'id');

        return $this->fetchUrl(self::api(self::replaceId(Endpoints::LIKE_A_PHOTO, $id)), 'POST');
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function unlikePhoto(string $id): array
    {
        $this->ensureInitialized();
        $this->validateRequired($id, 'id');

        return $this->fetchUrl(self::api(self::replaceId(Endpoints::UNLIKE_A_PHOTO, $id)), 'DELETE');
    }

    // ================================================================
    // Search
    // ================================================================

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function searchPhotos(
        string $query,
        int $page = 1,
        int $perPage = 10,
        string|int|null $collections = null,
        ?PhotoOrientation $orientation = null,
    ): array {
        $this->ensureInitialized();
        $this->validateRequired($query, 'query');

        return $this->fetchUrl(self::api(Endpoints::SEARCH_PHOTOS), 'GET', [
            'query' => $query,
            'page' => $page,
            'per_page' => $perPage,
            'collections' => $collections !== null ? (string) $collections : null,
            'orientation' => $orientation?->value,
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function searchCollections(string $query, int $page = 1, int $perPage = 10): array
    {
        $this->ensureInitialized();
        $this->validateRequired($query, 'query');

        return $this->fetchUrl(self::api(Endpoints::SEARCH_COLLECTIONS), 'GET', [
            'query' => $query,
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function searchUsers(string $query, int $page = 1, int $perPage = 10): array
    {
        $this->ensureInitialized();
        $this->validateRequired($query, 'query');

        return $this->fetchUrl(self::api(Endpoints::SEARCH_USERS), 'GET', [
            'query' => $query,
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    // ================================================================
    // Stats
    // ================================================================

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getStatsTotals(): array
    {
        $this->ensureInitialized();
        return $this->fetchUrl(self::api(Endpoints::STATS_TOTALS), 'GET');
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getStatsMonth(): array
    {
        $this->ensureInitialized();
        return $this->fetchUrl(self::api(Endpoints::STATS_MONTH), 'GET');
    }

    // ================================================================
    // Collections
    // ================================================================

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function listCollections(int $page = 1, int $perPage = 10): array
    {
        $this->ensureInitialized();
        return $this->fetchUrl(self::api(Endpoints::LIST_COLLECTIONS), 'GET', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function listFeaturedCollections(int $page = 1, int $perPage = 10): array
    {
        $this->ensureInitialized();
        return $this->fetchUrl(self::api(Endpoints::LIST_FEATURED_COLLECTIONS), 'GET', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function listCuratedCollections(int $page = 1, int $perPage = 10): array
    {
        $this->ensureInitialized();
        return $this->fetchUrl(self::api(Endpoints::LIST_CURATED_COLLECTIONS), 'GET', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getCollection(string $id): array
    {
        $this->ensureInitialized();
        $this->validateRequired($id, 'id');

        return $this->fetchUrl(self::api(self::replaceId(Endpoints::GET_COLLECTION, $id)), 'GET');
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getCuratedCollection(string $id): array
    {
        $this->ensureInitialized();
        $this->validateRequired($id, 'id');

        return $this->fetchUrl(self::api(self::replaceId(Endpoints::GET_CURATED_COLLECTION, $id)), 'GET');
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getCollectionPhotos(string $id, int $page = 1, int $perPage = 10): array
    {
        $this->ensureInitialized();
        $this->validateRequired($id, 'id');

        return $this->fetchUrl(self::api(self::replaceId(Endpoints::GET_COLLECTION_PHOTOS, $id)), 'GET', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getCuratedCollectionPhotos(string $id, int $page = 1, int $perPage = 10): array
    {
        $this->ensureInitialized();
        $this->validateRequired($id, 'id');

        return $this->fetchUrl(self::api(self::replaceId(Endpoints::GET_CURATED_COLLECTION_PHOTOS, $id)), 'GET', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function getRelatedCollections(string $id): array
    {
        $this->ensureInitialized();
        $this->validateRequired($id, 'id');

        return $this->fetchUrl(self::api(self::replaceId(Endpoints::LIST_RELATED_COLLECTION, $id)), 'GET');
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function createCollection(string $title, ?string $description = null, bool $private = false): array
    {
        $this->ensureInitialized();
        $this->validateRequired($title, 'title');

        return $this->fetchUrl(self::api(Endpoints::CREATE_NEW_COLLECTION), 'POST', [
            'title' => $title,
            'description' => $description,
            'private' => $private,
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function updateCollection(string $id, string $title, ?string $description = null, bool $private = false): array
    {
        $this->ensureInitialized();
        $this->validateRequired($id, 'id');
        $this->validateRequired($title, 'title');

        return $this->fetchUrl(self::api(self::replaceId(Endpoints::UPDATE_EXISTING_COLLECTION, $id)), 'PUT', [
            'title' => $title,
            'description' => $description,
            'private' => $private,
        ]);
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function deleteCollection(string $id): array
    {
        $this->ensureInitialized();
        $this->validateRequired($id, 'id');

        return $this->fetchUrl(self::api(self::replaceId(Endpoints::DELETE_COLLECTION, $id)), 'DELETE');
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function addPhotoToCollection(string $collectionId, string $photoId): array
    {
        $this->ensureInitialized();
        $this->validateRequired($collectionId, 'collection_id');
        $this->validateRequired($photoId, 'photo_id');

        return $this->fetchUrl(
            self::api(self::replaceCollectionId(Endpoints::ADD_PHOTO_TO_COLLECTION, $collectionId)),
            'POST',
            ['photo_id' => $photoId],
        );
    }

    /**
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    public function removePhotoFromCollection(string $collectionId, string $photoId): array
    {
        $this->ensureInitialized();
        $this->validateRequired($collectionId, 'collection_id');
        $this->validateRequired($photoId, 'photo_id');

        return $this->fetchUrl(
            self::api(self::replaceCollectionId(Endpoints::REMOVE_PHOTO_FROM_COLLECTION, $collectionId)),
            'DELETE',
            ['photo_id' => $photoId],
        );
    }

    // ================================================================
    // Private helpers
    // ================================================================

    private function computeHash(string $value): string
    {
        return hash('sha256', $value);
    }

    /**
     * @throws WrapSplashException
     */
    private function validateRequired(?string $value, string $fieldName): void
    {
        if ($value === null || $value === '') {
            $message = match ($fieldName) {
                'id' => 'Parameter : id is required!',
                'query' => 'Parameter : query is missing!',
                default => "Parameter : {$fieldName} is required and cannot be empty!",
            };
            throw new WrapSplashException($message);
        }
    }

    private function buildQuery(array $params): array
    {
        return array_filter($params, fn ($v) => $v !== null && $v !== '' && $v !== false);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function flattenNested(string $prefix, array $data, array $allowedKeys): array
    {
        $result = [];
        foreach ($allowedKeys as $key) {
            if (!empty($data[$key])) {
                $result["{$prefix}[{$key}]"] = $data[$key];
            }
        }
        return $result;
    }

    /**
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed>
     * @throws WrapSplashException
     */
    private function fetchUrl(string $url, string $method, array $queryParams = [], ?array $body = null): array
    {
        $query = $this->buildQuery($queryParams);

        $options = [
            'query' => $query,
            'headers' => $this->headers,
        ];
        if ($body !== null) {
            $options['json'] = $body;
        }

        $attempts = $this->retries + 1;
        $lastException = null;

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            try {
                $response = $this->httpClient->request($method, $url, $options);
                $statusCode = $response->getStatusCode();

                if ($statusCode === 204) {
                    return [
                        'status' => 204,
                        'statusText' => 'No Content',
                        'message' => 'Content Deleted',
                    ];
                }

                if ($statusCode === 403) {
                    return [
                        'status' => 403,
                        'statusText' => 'Forbidden',
                        'message' => 'Rate Limit Exceeded',
                    ];
                }

                $contents = (string) $response->getBody();
                if ($contents === '') {
                    return [];
                }

                $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
                return is_array($decoded) ? $decoded : ['data' => $decoded];

            } catch (GuzzleException $e) {
                $lastException = $e;
                if ($attempt < $attempts - 1 && $this->retryDelayMs > 0) {
                    usleep($this->retryDelayMs * 1000);
                }
            }
        }

        throw $this->createWrapSplashException($lastException);
    }

    /**
     * @throws WrapSplashException
     */
    private function createWrapSplashException(\Throwable $error): WrapSplashException
    {
        if ($error instanceof WrapSplashException) {
            return $error;
        }

        $statusCode = null;
        $statusText = null;

        if (method_exists($error, 'getResponse')) {
            $response = $error->getResponse();
            if ($response !== null) {
                $statusCode = $response->getStatusCode();
                $statusText = $response->getReasonPhrase();
            }
        }

        return new WrapSplashException(
            message: $error->getMessage(),
            code: $statusCode ?? 0,
            previous: $error,
            statusText: $statusText,
        );
    }

    private function ensureInitialized(): void
    {
        if (!$this->initialized) {
            throw new WrapSplashException('Client not initialized. Call init() or use a factory method.');
        }
    }

    private function ensureHttpClient(): void
    {
        if ($this->httpClient === null) {
            $this->httpClient = new Client([
                'base_uri' => Endpoints::API_LOCATION,
                'timeout' => $this->timeout / 1000,
            ]);
        }
    }

    private static function api(string $path): string
    {
        return $path;
    }

    private static function replaceId(string $path, string $id): string
    {
        return str_replace('{id}', $id, $path);
    }

    private static function replaceUsername(string $path, string $username): string
    {
        return str_replace('{username}', $username, $path);
    }

    private static function replaceCollectionId(string $path, string $collectionId): string
    {
        return str_replace('{collection_id}', $collectionId, $path);
    }
}
