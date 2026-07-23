<?php

declare(strict_types=1);

namespace SandeepV\WrapsplashPHP\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use SandeepV\WrapsplashPHP\Configuration;
use SandeepV\WrapsplashPHP\Enums\PhotoOrder;
use SandeepV\WrapsplashPHP\Enums\PhotoOrientation;
use SandeepV\WrapsplashPHP\WrapSplash;
use SandeepV\WrapsplashPHP\WrapSplashException;

class WrapSplashTest extends TestCase
{
    private function createClient(array $responses): WrapSplash
    {
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handler]);

        $client = new WrapSplash($httpClient);
        $client->init(new Configuration(bearerToken: 'test-bearer-token'));
        return $client;
    }

    private function createClientWithCredentials(array $responses): WrapSplash
    {
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handler]);

        $client = new WrapSplash($httpClient);
        $client->init(new Configuration(
            accessToken: 'test-access-key',
            secretKey: 'test-secret-key',
            redirectUri: 'https://example.com/callback',
            code: 'test-auth-code',
        ));
        return $client;
    }

    // ================================================================
    // Initialization tests
    // ================================================================

    public function testInitWithBearerTokenSetsCorrectHeaders(): void
    {
        $mock = new MockHandler([new Response(200, [], '{"user":"test"}')]);
        $handler = HandlerStack::create($mock);

        $captured = [];
        $handler->push(Middleware::tap(
            null,
            function ($request) use (&$captured) {
                $captured['auth'] = (string) $request->getHeader('Authorization')[0];
                $captured['wrap'] = (string) $request->getHeader('X-WrapSplash-Header')[0];
            }
        ));

        $httpClient = new Client(['handler' => $handler]);
        $client = new WrapSplash($httpClient);
        $client->init(new Configuration(bearerToken: 'my-token-123'));
        $client->getCurrentUserProfile();

        $this->assertEquals('Bearer my-token-123', $captured['auth']);
        $this->assertEquals(hash('sha256', 'my-token-123'), $captured['wrap']);
    }

    public function testInitWithCredentialsSetsClientIdHeader(): void
    {
        $mock = new MockHandler([new Response(200, [], '{}')]);
        $handler = HandlerStack::create($mock);

        $captured = [];
        $handler->push(Middleware::tap(
            null,
            function ($request) use (&$captured) {
                $captured['auth'] = (string) $request->getHeader('Authorization')[0];
                $captured['wrap'] = (string) $request->getHeader('X-WrapSplash-Header')[0];
            }
        ));

        $httpClient = new Client(['handler' => $handler]);
        $client = new WrapSplash($httpClient);
        $client->init(new Configuration(
            accessToken: 'my-access-key',
            secretKey: 'secret',
            redirectUri: 'https://example.com',
            code: 'auth-code',
        ));
        $client->getCurrentUserProfile();

        $this->assertEquals('Client-ID my-access-key', $captured['auth']);
        $this->assertEquals(hash('sha256', 'my-access-key'), $captured['wrap']);
    }

    public function testInitThrowsOnMissingCredentials(): void
    {
        $this->expectException(WrapSplashException::class);
        $client = new WrapSplash();
        $client->init(new Configuration(
            accessToken: 'key',
        ));
    }

    public function testInitThrowsOnNullConfig(): void
    {
        $this->expectException(WrapSplashException::class);
        $client = new WrapSplash();
        $client->init(null);
    }

    public function testWithBearerTokenFactoryMethod(): void
    {
        $mock = new MockHandler([new Response(200, [], '{"ok":true}')]);
        $handler = HandlerStack::create($mock);

        $captured = [];
        $handler->push(Middleware::tap(
            null,
            function ($request) use (&$captured) {
                $captured['auth'] = (string) $request->getHeader('Authorization')[0];
                $captured['wrap'] = (string) $request->getHeader('X-WrapSplash-Header')[0];
            }
        ));

        $httpClient = new Client(['handler' => $handler]);
        $client = new WrapSplash($httpClient);
        $client->init(new Configuration(bearerToken: 'factory-token'));
        $client->getCurrentUserProfile();

        $this->assertEquals('Bearer factory-token', $captured['auth']);
        $this->assertEquals(hash('sha256', 'factory-token'), $captured['wrap']);
    }

    public function testMethodThrowsWhenNotInitialized(): void
    {
        $this->expectException(WrapSplashException::class);
        $this->expectExceptionMessage('Client not initialized');
        $client = new WrapSplash();
        $client->getCurrentUserProfile();
    }

    // ================================================================
    // Validation tests
    // ================================================================

    public function testEmptyIdThrows(): void
    {
        $this->expectException(WrapSplashException::class);
        $this->expectExceptionMessage('Parameter : id is required!');
        $client = $this->createClient([]);
        $client->getPhoto('');
    }

    public function testEmptyQueryThrows(): void
    {
        $this->expectException(WrapSplashException::class);
        $this->expectExceptionMessage('Parameter : query is missing!');
        $client = $this->createClient([]);
        $client->searchPhotos('');
    }

    public function testEmptyUsernameThrows(): void
    {
        $this->expectException(WrapSplashException::class);
        $this->expectExceptionMessage('Parameter : username is required');
        $client = $this->createClient([]);
        $client->getUserPhotos('');
    }

    public function testEmptyTitleThrows(): void
    {
        $this->expectException(WrapSplashException::class);
        $this->expectExceptionMessage('Parameter : title is required');
        $client = $this->createClient([]);
        $client->createCollection('');
    }

    // ================================================================
    // Current User tests
    // ================================================================

    public function testGetCurrentUserProfile(): void
    {
        $client = $this->createClient([new Response(200, [], '{"id":"u1","username":"john"}')]);
        $result = $client->getCurrentUserProfile();
        $this->assertEquals('u1', $result['id']);
        $this->assertEquals('john', $result['username']);
    }

    public function testUpdateCurrentUserProfile(): void
    {
        $client = $this->createClient([new Response(200, [], '{"username":"updated"}')]);
        $result = $client->updateCurrentUserProfile(['username' => 'updated', 'bio' => 'Hello']);
        $this->assertEquals('updated', $result['username']);
    }

    // ================================================================
    // Users tests
    // ================================================================

    public function testGetPublicProfile(): void
    {
        $client = $this->createClient([new Response(200, [], '{"username":"testuser"}')]);
        $result = $client->getPublicProfile('testuser');
        $this->assertEquals('testuser', $result['username']);
    }

    public function testGetPublicProfileWithDimensions(): void
    {
        $mock = new MockHandler([new Response(200, [], '{}')]);
        $handler = HandlerStack::create($mock);

        $capturedUrl = '';
        $handler->push(Middleware::tap(
            null,
            function ($request) use (&$capturedUrl) {
                $capturedUrl = (string) $request->getUri();
            }
        ));

        $httpClient = new Client(['handler' => $handler]);
        $client = new WrapSplash($httpClient);
        $client->init(new Configuration(bearerToken: 'tok'));
        $client->getPublicProfile('alice', 200, 300);

        $this->assertStringContainsString('w=200', $capturedUrl);
        $this->assertStringContainsString('h=300', $capturedUrl);
    }

    public function testGetUserPortfolio(): void
    {
        $client = $this->createClient([new Response(200, [], '{"url":"https://example.com"}')]);
        $result = $client->getUserPortfolio('testuser');
        $this->assertEquals('https://example.com', $result['url']);
    }

    public function testGetUserPhotos(): void
    {
        $client = $this->createClient([new Response(200, [], '[{"id":"p1"}]')]);
        $result = $client->getUserPhotos('testuser');
        $this->assertCount(1, $result);
    }

    public function testGetUserPhotosWithParams(): void
    {
        $mock = new MockHandler([new Response(200, [], '[]')]);
        $handler = HandlerStack::create($mock);

        $capturedUrl = '';
        $handler->push(Middleware::tap(
            null,
            function ($request) use (&$capturedUrl) {
                $capturedUrl = (string) $request->getUri();
            }
        ));

        $httpClient = new Client(['handler' => $handler]);
        $client = new WrapSplash($httpClient);
        $client->init(new Configuration(bearerToken: 'tok'));
        $client->getUserPhotos('bob', page: 2, perPage: 25, orderBy: PhotoOrder::POPULAR);

        $this->assertStringContainsString('page=2', $capturedUrl);
        $this->assertStringContainsString('per_page=25', $capturedUrl);
        $this->assertStringContainsString('order_by=popular', $capturedUrl);
    }

    public function testGetUserLikedPhotos(): void
    {
        $client = $this->createClient([new Response(200, [], '[{"id":"lp1"}]')]);
        $result = $client->getUserLikedPhotos('testuser', orderBy: PhotoOrder::OLDEST);
        $this->assertCount(1, $result);
    }

    public function testGetUserCollections(): void
    {
        $client = $this->createClient([new Response(200, [], '[{"id":"c1"}]')]);
        $result = $client->getUserCollections('testuser');
        $this->assertCount(1, $result);
    }

    public function testGetUserStatistics(): void
    {
        $client = $this->createClient([new Response(200, [], '{"downloads":{"total":100}}')]);
        $result = $client->getUserStatistics('testuser');
        $this->assertArrayHasKey('downloads', $result);
    }

    // ================================================================
    // Photos tests
    // ================================================================

    public function testListPhotos(): void
    {
        $client = $this->createClient([new Response(200, [], '[{"id":"p1"},{"id":"p2"}]')]);
        $result = $client->listPhotos();
        $this->assertCount(2, $result);
    }

    public function testListCuratedPhotos(): void
    {
        $client = $this->createClient([new Response(200, [], '[{"id":"cp1"}]')]);
        $result = $client->listCuratedPhotos();
        $this->assertCount(1, $result);
    }

    public function testGetPhoto(): void
    {
        $client = $this->createClient([new Response(200, [], '{"id":"abc123","color":"#fff"}')]);
        $result = $client->getPhoto('abc123');
        $this->assertEquals('abc123', $result['id']);
    }

    public function testGetPhotoWithDimensionsAndRect(): void
    {
        $mock = new MockHandler([new Response(200, [], '{}')]);
        $handler = HandlerStack::create($mock);

        $capturedUrl = '';
        $handler->push(Middleware::tap(
            null,
            function ($request) use (&$capturedUrl) {
                $capturedUrl = (string) $request->getUri();
            }
        ));

        $httpClient = new Client(['handler' => $handler]);
        $client = new WrapSplash($httpClient);
        $client->init(new Configuration(bearerToken: 'tok'));
        $client->getPhoto('photo-id', 400, 300, '0,0,400,300');

        $this->assertStringContainsString('w=400', $capturedUrl);
        $this->assertStringContainsString('h=300', $capturedUrl);
        $this->assertStringContainsString('rect=0%2C0%2C400%2C300', $capturedUrl);
    }

    public function testGetRandomPhoto(): void
    {
        $client = $this->createClient([new Response(200, [], '{"id":"rand1"}')]);
        $result = $client->getRandomPhoto();
        $this->assertEquals('rand1', $result['id']);
    }

    public function testGetRandomPhotoWithParams(): void
    {
        $mock = new MockHandler([new Response(200, [], '{}')]);
        $handler = HandlerStack::create($mock);

        $capturedUrl = '';
        $handler->push(Middleware::tap(
            null,
            function ($request) use (&$capturedUrl) {
                $capturedUrl = (string) $request->getUri();
            }
        ));

        $httpClient = new Client(['handler' => $handler]);
        $client = new WrapSplash($httpClient);
        $client->init(new Configuration(bearerToken: 'tok'));
        $client->getRandomPhoto(
            collections: '12345',
            featured: true,
            username: 'photographer',
            query: 'nature',
            width: 1920,
            height: 1080,
            orientation: PhotoOrientation::PORTRAIT,
            count: 5,
        );

        $this->assertStringContainsString('collections=12345', $capturedUrl);
        $this->assertStringContainsString('featured=1', $capturedUrl);
        $this->assertStringContainsString('username=photographer', $capturedUrl);
        $this->assertStringContainsString('query=nature', $capturedUrl);
        $this->assertStringContainsString('width=1920', $capturedUrl);
        $this->assertStringContainsString('height=1080', $capturedUrl);
        $this->assertStringContainsString('orientation=portrait', $capturedUrl);
        $this->assertStringContainsString('count=5', $capturedUrl);
    }

    public function testGetPhotoStatistics(): void
    {
        $client = $this->createClient([new Response(200, [], '{"id":"stat1","downloads":{"total":50}}')]);
        $result = $client->getPhotoStatistics('stat1');
        $this->assertArrayHasKey('downloads', $result);
    }

    public function testGetPhotoDownloadLink(): void
    {
        $client = $this->createClient([new Response(200, [], '{"url":"https://unsplash.com/photos/dl/123"}')]);
        $result = $client->getPhotoDownloadLink('dl123');
        $this->assertEquals('https://unsplash.com/photos/dl/123', $result['url']);
    }

    public function testUpdatePhoto(): void
    {
        $mock = new MockHandler([new Response(200, [], '{}')]);
        $handler = HandlerStack::create($mock);

        $capturedUrl = '';
        $handler->push(Middleware::tap(
            function ($request) use (&$capturedUrl) {
                $capturedUrl = (string) $request->getUri();
            }
        ));

        $httpClient = new Client(['handler' => $handler]);
        $client = new WrapSplash($httpClient);
        $client->init(new Configuration(bearerToken: 'tok'));
        $client->updatePhoto('photo1', [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'name' => 'New York',
            'city' => 'New York City',
            'country' => 'US',
        ], [
            'make' => 'Canon',
            'model' => 'EOS R5',
        ]);

        $this->assertStringContainsString('location%5Blatitude%5D=40.7128', $capturedUrl);
        $this->assertStringContainsString('location%5Blongitude%5D=-74', $capturedUrl);
        $this->assertStringContainsString('location%5Bname%5D=New', $capturedUrl);
        $this->assertStringContainsString('location%5Bcity%5D=New', $capturedUrl);
        $this->assertStringContainsString('location%5Bcountry%5D=US', $capturedUrl);
        $this->assertStringContainsString('exif%5Bmake%5D=Canon', $capturedUrl);
        $this->assertStringContainsString('exif%5Bmodel%5D=EOS', $capturedUrl);
    }

    public function testLikePhoto(): void
    {
        $client = $this->createClient([new Response(200, [], '{"photo":{"id":"p1"},"user":{"id":"u1"}}')]);
        $result = $client->likePhoto('p1');
        $this->assertArrayHasKey('photo', $result);
    }

    public function testUnlikePhoto(): void
    {
        $client = $this->createClient([new Response(200, [], '{"photo":{"id":"p1"}}')]);
        $result = $client->unlikePhoto('p1');
        $this->assertArrayHasKey('photo', $result);
    }

    // ================================================================
    // Search tests
    // ================================================================

    public function testSearchPhotos(): void
    {
        $client = $this->createClient([new Response(200, [], '{"results":[{"id":"s1"}],"total":1}')]);
        $result = $client->searchPhotos('mountains');
        $this->assertEquals(1, $result['total']);
    }

    public function testSearchCollections(): void
    {
        $client = $this->createClient([new Response(200, [], '{"results":[{"id":"sc1"}],"total":1}')]);
        $result = $client->searchCollections('nature');
        $this->assertEquals(1, $result['total']);
    }

    public function testSearchUsers(): void
    {
        $client = $this->createClient([new Response(200, [], '{"results":[{"id":"su1"}],"total":1}')]);
        $result = $client->searchUsers('alice');
        $this->assertEquals(1, $result['total']);
    }

    // ================================================================
    // Stats tests
    // ================================================================

    public function testGetStatsTotals(): void
    {
        $client = $this->createClient([new Response(200, [], '{"downloads":1000}')]);
        $result = $client->getStatsTotals();
        $this->assertEquals(1000, $result['downloads']);
    }

    public function testGetStatsMonth(): void
    {
        $client = $this->createClient([new Response(200, [], '{"downloads":100}')]);
        $result = $client->getStatsMonth();
        $this->assertEquals(100, $result['downloads']);
    }

    // ================================================================
    // Collections tests
    // ================================================================

    public function testListCollections(): void
    {
        $client = $this->createClient([new Response(200, [], '[{"id":"col1"}]')]);
        $result = $client->listCollections();
        $this->assertCount(1, $result);
    }

    public function testListFeaturedCollections(): void
    {
        $client = $this->createClient([new Response(200, [], '[{"id":"fc1"}]')]);
        $result = $client->listFeaturedCollections();
        $this->assertCount(1, $result);
    }

    public function testListCuratedCollections(): void
    {
        $client = $this->createClient([new Response(200, [], '[{"id":"cc1"}]')]);
        $result = $client->listCuratedCollections();
        $this->assertCount(1, $result);
    }

    public function testGetCollection(): void
    {
        $client = $this->createClient([new Response(200, [], '{"id":"col1","title":"My Collection"}')]);
        $result = $client->getCollection('col1');
        $this->assertEquals('My Collection', $result['title']);
    }

    public function testGetCuratedCollection(): void
    {
        $client = $this->createClient([new Response(200, [], '{"id":"cc1","title":"Curated"}')]);
        $result = $client->getCuratedCollection('cc1');
        $this->assertEquals('Curated', $result['title']);
    }

    public function testGetCollectionPhotos(): void
    {
        $client = $this->createClient([new Response(200, [], '[{"id":"p1"}]')]);
        $result = $client->getCollectionPhotos('col1');
        $this->assertCount(1, $result);
    }

    public function testGetCuratedCollectionPhotos(): void
    {
        $client = $this->createClient([new Response(200, [], '[{"id":"cp1"}]')]);
        $result = $client->getCuratedCollectionPhotos('cc1');
        $this->assertCount(1, $result);
    }

    public function testGetRelatedCollections(): void
    {
        $client = $this->createClient([new Response(200, [], '[{"id":"rc1"}]')]);
        $result = $client->getRelatedCollections('col1');
        $this->assertCount(1, $result);
    }

    public function testCreateCollection(): void
    {
        $client = $this->createClient([new Response(200, [], '{"id":"newcol","title":"New Collection"}')]);
        $result = $client->createCollection('New Collection', 'A description', true);
        $this->assertEquals('New Collection', $result['title']);
    }

    public function testUpdateCollection(): void
    {
        $client = $this->createClient([new Response(200, [], '{"id":"col1","title":"Updated"}')]);
        $result = $client->updateCollection('col1', 'Updated', 'New desc', false);
        $this->assertEquals('Updated', $result['title']);
    }

    public function testDeleteCollection(): void
    {
        $client = $this->createClient([new Response(200, [], '{"id":"col1"}')]);
        $result = $client->deleteCollection('col1');
        $this->assertArrayHasKey('id', $result);
    }

    public function testAddPhotoToCollection(): void
    {
        $client = $this->createClient([new Response(200, [], '{"id":"col1","photo_id":"p1"}')]);
        $result = $client->addPhotoToCollection('col1', 'p1');
        $this->assertArrayHasKey('photo_id', $result);
    }

    public function testRemovePhotoFromCollection(): void
    {
        $client = $this->createClient([new Response(200, [], '{"id":"col1","photo_id":"p1"}')]);
        $result = $client->removePhotoFromCollection('col1', 'p1');
        $this->assertArrayHasKey('photo_id', $result);
    }

    // ================================================================
    // Bearer Token tests
    // ================================================================

    public function testGenerateBearerToken(): void
    {
        $mock = new MockHandler([new Response(200, [], '{"access_token":"new-token","token_type":"bearer"}')]);
        $handler = HandlerStack::create($mock);

        $capturedUrl = '';
        $handler->push(Middleware::tap(
            function ($request) use (&$capturedUrl) {
                $capturedUrl = (string) $request->getUri();
            }
        ));

        $httpClient = new Client(['handler' => $handler]);
        $client = new WrapSplash($httpClient);
        $client->init(new Configuration(
            accessToken: 'access-key',
            secretKey: 'secret-key',
            redirectUri: 'https://example.com/callback',
            code: 'auth-code-123',
        ));
        $result = $client->generateBearerToken();

        $this->assertEquals('new-token', $result['access_token']);
        $this->assertStringContainsString('client_id=access-key', $capturedUrl);
        $this->assertStringContainsString('client_secret=secret-key', $capturedUrl);
        $this->assertStringContainsString('redirect_uri=https%3A%2F%2Fexample.com%2Fcallback', $capturedUrl);
        $this->assertStringContainsString('code=auth-code-123', $capturedUrl);
        $this->assertStringContainsString('grant_type=authorization_code', $capturedUrl);
    }

    // ================================================================
    // Error handling tests
    // ================================================================

    public function testServerExceptionBecomesWrapSplashException(): void
    {
        $error = new ServerException('Server Error', new Request('GET', 'test'), new Response(500));
        $mock = new MockHandler([$error, $error, $error]);
        $handler = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handler]);

        $client = new WrapSplash($httpClient);
        $client->init(new Configuration(bearerToken: 'tok', retries: 2, retryDelayMs: 1));

        $this->expectException(WrapSplashException::class);
        $client->getCurrentUserProfile();
    }

    public function testEmptyResponseReturnsEmptyArray(): void
    {
        $client = $this->createClient([new Response(200, [], '')]);
        $result = $client->getStatsTotals();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testRetryOnFailureThenSuccess(): void
    {
        $mock = new MockHandler([
            new ServerException('Temporary Error', new Request('GET', 'test'), new Response(502)),
            new Response(200, [], '{"success":true}'),
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handler]);

        $client = new WrapSplash($httpClient);
        $client->init(new Configuration(bearerToken: 'tok', retryDelayMs: 1));

        $result = $client->getStatsTotals();
        $this->assertTrue($result['success']);
    }

    public function testFailsAfterMaxRetries(): void
    {
        $mock = new MockHandler([
            new ServerException('Error 1', new Request('GET', 'test'), new Response(502)),
            new ServerException('Error 2', new Request('GET', 'test'), new Response(502)),
            new ServerException('Error 3', new Request('GET', 'test'), new Response(502)),
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handler]);

        $client = new WrapSplash($httpClient);
        $client->init(new Configuration(bearerToken: 'tok', retries: 2, retryDelayMs: 1));

        $this->expectException(WrapSplashException::class);
        $client->getStatsTotals();
    }

    // ================================================================
    // Enum tests
    // ================================================================

    public function testPhotoOrderEnumValues(): void
    {
        $this->assertEquals(['latest', 'oldest', 'popular'], PhotoOrder::values());
    }

    public function testPhotoOrientationEnumValues(): void
    {
        $this->assertEquals(['landscape', 'portrait', 'squarish'], PhotoOrientation::values());
    }
}
