# WrapSplashPHP v1.0.0

A simple, synchronous API wrapper for the popular [Unsplash](https://unsplash.com/) platform, written in **PHP**. This is a direct port of [WrapSplashTS](https://github.com/SandeepVattapparambil/wrapsplash) for the PHP ecosystem.

Unsplash provides beautiful high quality free images and photos that you can download and use for any project without any attribution.

Before using the Unsplash API, you need to **register as a developer** and **read the API Guidelines.**

> **Note:**  Every application must abide by the [API Guidelines](https://unsplash.com/documentation). Specifically, remember to hotlink images and trigger a download when appropriate.

## Table of Contents
<!--ts-->
* [About](#wrapsplashphp-v100)
* [Requirements](#requirements)
* [Installation](#installation)
* [Sample Usage](#sample-usage)
* [Development](#development)
* [Dependency](#dependency)
* [API Documentation](#api-documentation)
    * [Schema](#schema)
        * [Location](#location)
        * [Summary Objects](#summary-objects)
        * [Error Messages](#error-messages)
    * [Authorization](#authorization)
        * [Public Actions](#public-actions)
        * [User Authentication](#user-authentication)
        * [Wrapsplash init()](#wrapsplash-init)
        * [Generate Bearer Token](#generate-bearer-token)
    * [Users APIs](#users-apis)
        * [Get User's Public Profile](#get-users-public-profile)
        * [Get User's Portfolio Link](#get-users-portfolio-link)
        * [Get User's Photos](#get-users-photos)
        * [Get User Liked Photos](#get-user-liked-photos)
        * [Get User's Collections](#get-users-collections)
        * [Get User's Statistics](#get-users-statistics)
    * [Photos APIs](#photos-apis)
        * [List Photos](#list-photos)
        * [List Curated Photos](#list-curated-photos)
        * [Get a Photo by Id](#get-a-photo-by-id)
        * [Get a Random Photo](#get-a-random-photo)
        * [Get a Photo's Statistics](#get-a-photos-statistics)
        * [Get a Photo's Download Link](#get-a-photos-download-link)
        * [Update a Photo](#update-a-photo)
        * [Like a Photo](#like-a-photo)
        * [Unlike a Photo](#unlike-a-photo)
    * [Search APIs](#search-apis)
        * [Search Photos](#search-photos)
        * [Search Collections](#search-collections)
        * [Search Users](#search-users)
    * [Current User APIs](#current-user-apis)
        * [Get the user's profile](#get-users-profile)
        * [Update User's Profile](#update-users-profile)
    * [Stats APIs](#stats-apis)
        * [Totals](#stats-totals)
        * [Months](#stats-month)
    * [Collections APIs](#collections-apis)
        * [Link Relations](#link-relations)
        * [List Collections](#list-collections)
        * [List Featured Collections](#list-featured-collections)
        * [List Curated Collections](#list-curated-collections)
        * [Get a Collection](#get-a-collection)
        * [Get a Curated Collection](#get-a-curated-collection)
        * [Get a Collection's Photos](#get-a-collections-photos)
        * [Get a Curated Collection's Photos](#get-a-curated-collections-photos)
        * [List a Collection's Related Collections](#list-a-collections-related-collections)
        * [Create a New Collection](#create-a-new-collection)
        * [Update an Existing Collection](#update-an-existing-collection)
        * [Delete a Collection](#delete-a-collection)
        * [Add a Photo to a Collection](#add-a-photo-to-a-collection)
        * [Remove a Photo from a Collection](#remove-a-photo-from-a-collection)
* [Tests](#tests)
* [License](#license)
* [Acknowledgements](#acknowledgements)
<!--te-->

## Requirements

- PHP 8.1+
- [Guzzle 7](https://docs.guzzlephp.org/) (HTTP client)

## Installation

Install the package from Composer
```sh
composer require sandeepv/wrapsplash-php
```

### Sample usage
```php
<?php

use SandeepV\WrapsplashPHP\WrapSplash;
use SandeepV\WrapsplashPHP\Configuration;

$client = new WrapSplash();

$client->init(new Configuration(
    bearerToken: '<bearer-token>',
));

$result = $client->getPhoto('<photo-id>');
print_r($result);
```

Or use the convenience factory methods:
```php
<?php

use SandeepV\WrapsplashPHP\WrapSplash;

// With bearer token
$client = WrapSplash::withBearerToken('<bearer-token>');

// With full credentials
$client = WrapSplash::withCredentials(
    accessToken: '<api-key>',
    secretKey: '<secret-key>',
    redirectUri: '<callback-url>',
    code: '<authorization-code>',
);

$result = $client->getPhoto('<photo-id>');
print_r($result);
```

### Development
```sh
composer install
vendor/bin/phpunit              # Run all tests
vendor/bin/phpunit --coverage-html coverage/  # Generate coverage report
```

### Dependency
This library depends on [Guzzle](https://docs.guzzlephp.org/) to make HTTP requests to the [Unsplash API](https://unsplash.com/documentation).

### Configuration Options

| Option | Type | Default | Description |
|---|---|---|---|
| `bearerToken` | `?string` | `null` | OAuth bearer token for authentication |
| `accessToken` | `?string` | `null` | Unsplash API access key |
| `secretKey` | `?string` | `null` | Unsplash API secret key |
| `redirectUri` | `?string` | `null` | OAuth callback URL |
| `code` | `?string` | `null` | Authorization code from OAuth flow |
| `timeout` | `int` | `10000` | HTTP request timeout in milliseconds |
| `retries` | `int` | `2` | Number of retry attempts on failure |
| `retryDelayMs` | `int` | `100` | Delay between retries in milliseconds |

### API Documentation

### Schema
#### Location
The API we are using is ```https://api.unsplash.com/```. Responses are sent as JSON.

#### Summary objects
When retrieving a list of objects, an abbreviated or summary version of that object is returned - i.e., a subset of its attributes. To get a full detailed version of that object, fetch it individually.

#### Error messages
If an error occurs, whether on the server or client side, the error message(s) will be returned in an ```errors``` array.
For example:
```sh
422 Unprocessable Entity
```
```json
{
  "errors": ["Username is missing", "Password cannot be blank"]
}
```

### Authorization
#### Public Actions
Many actions can be performed without requiring authentication from a specific user. For example, downloading a photo does not require a user to log in.
To authenticate requests in this way, pass your application's access key via the HTTP ```Authorization``` header:
```sh
Authorization: Client-ID YOUR_ACCESS_KEY
```
You can also pass this value using a ```client_id``` query parameter:
```sh
https://api.unsplash.com/photos/?client_id=YOUR_ACCESS_KEY
```
If only your access key is sent, attempting to perform non-public actions that require user authorization will result in a ```401 Unauthorized response```.

#### User Authentication
The Unsplash API uses OAuth2 to authenticate and authorize Unsplash users. Unsplash's OAuth2 paths live at ```https://unsplash.com/oauth/```.

Before using wrapsplash-php:
- Developers are required to create a developer account from [Unsplash](https://unsplash.com/developers).
- Create a new App from Your Apps page.
- Get the ```Access Key```, ```Secret key```, ```Callback URLs```, and ```Authorization code```.
- If you have a Bearer Token, then its super, or else you can generate it using **wrapsplash-php**.
> **Note:** ```Authorization code``` can be obtained by clicking the ```Authorize``` link  next to ```Callback URLs```. Also ```Authorization code``` is a one-time use code, you have to generate it again, if the action fails!.

#### Wrapsplash init()
Wrapsplash instance has to be initialized with your credentials obtained from Unsplash developer account for programatic access. These credentials are passed in to the `init()` function as a `Configuration` object. The following example shows all the available options.

```php
use SandeepV\WrapsplashPHP\WrapSplash;
use SandeepV\WrapsplashPHP\Configuration;

$client = new WrapSplash();

$client->init(new Configuration(
    accessToken: '<api-key>',
    secretKey: '<secret-key>',
    redirectUri: '<callback-url>',
    code: '<authorization-code>',
    bearerToken: '<bearer-token>',
    timeout: 10000,
    retries: 2,
    retryDelayMs: 100,
));
```
If you have a `bearerToken`, then only the bearer token has to be passed in.
```php
$client->init(new Configuration(
    bearerToken: '<bearer-token>',
));
```

You can also use the static factory methods:
```php
// Bearer token only
$client = WrapSplash::withBearerToken('<bearer-token>');

// Full credentials
$client = WrapSplash::withCredentials(
    accessToken: '<api-key>',
    secretKey: '<secret-key>',
    redirectUri: '<callback-url>',
    code: '<authorization-code>',
);
```

#### Generate Bearer Token
A method to generate a Bearer Token for ```write_access``` to private data.
The ```init()``` method in this case requires `accessToken`, `secretKey`, `redirectUri`, and `code` to generate the bearer token.
> **Note:** No Parameters are required for this function.

```php
<?php

use SandeepV\WrapsplashPHP\WrapSplash;
use SandeepV\WrapsplashPHP\Configuration;

$client = new WrapSplash();

$client->init(new Configuration(
    accessToken: '<api-key>',
    secretKey: '<secret-key>',
    redirectUri: '<callback-url>',
    code: '<authorization-code>',
));

$result = $client->generateBearerToken();
print_r($result);
```
If successful, the response body will be a JSON representation of your user's access token a.k.a bearer token:

```json
{
   "access_token": "091343ce13c8ae780065ecb3b13dc903475dd22cb78a05503c2e0c69c5e98044",
   "token_type": "bearer",
   "scope": "public read_photos write_photos",
   "created_at": 1436544465
}
```
and once you have your ```bearer_token``` you can use it in your app like this:
```php
$client->init(new Configuration(
    bearerToken: '<bearer-token>',
));
```

### Users APIs
#### Get User's Public Profile
Retrieve public details on a given user.
```
GET /users/:username
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **username** | *string* | The username of the particular user | no |
| **width** | *int* | Width of the profile picture in pixels | yes |
| **height** | *int* | Height of the profile picture in pixels | yes |

> **Note:**  When optional **height** & **width** are specified the profile image will be included in the "profile_image" object as "custom".

```php
$client->getPublicProfile('<username>', 600, 600);
```

#### Get User's Portfolio Link
Retrieve a single user's portfolio link.
```
GET /users/:username/portfolio
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **username** | *string* | The username of the particular user | no |

```php
$client->getUserPortfolio('<username>');
```

#### Get User's Photos
Get a list of photos uploaded by a particular user.
```
GET /users/:username/photos
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **username** | *string* | The username of the particular user | no |
| **page** | *int* | Page number to retrieve | yes | 1
| **perPage** | *int* | Number of items per page | yes | 10
| **stats** | *bool* | Show the stats for each user's photo | yes | false
| **resolution** | *string* | The frequency of the stats | yes | days
| **quantity** | *int* | The amount of for each stat | yes | 30
| **orderBy** | *PhotoOrder* | How to sort the photos.(```Valid values: PhotoOrder::LATEST, PhotoOrder::OLDEST, PhotoOrder::POPULAR```) | yes | PhotoOrder::LATEST

```php
use SandeepV\WrapsplashPHP\Enums\PhotoOrder;

$client->getUserPhotos('<username>', page: 1, perPage: 10, orderBy: PhotoOrder::LATEST);
```

#### Get User Liked Photos
Get a list of photos liked by a user.
```
GET /users/:username/likes
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **username** | *string* | The username of the particular user | no |
| **page** | *int* | Page number to retrieve | yes | 1
| **perPage** | *int* | Number of items per page | yes | 10
| **orderBy** | *PhotoOrder* | How to sort the photos.(```Valid values: PhotoOrder::LATEST, PhotoOrder::OLDEST, PhotoOrder::POPULAR```) | yes | PhotoOrder::LATEST

```php
$client->getUserLikedPhotos('<username>', orderBy: PhotoOrder::LATEST);
```

#### Get User's Collections
Get a list of collections created by the user.
```
GET /users/:username/collections
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **username** | *string* | The username of the particular user | no |
| **page** | *int* | Page number to retrieve | yes | 1
| **perPage** | *int* | Number of items per page | yes | 10

```php
$client->getUserCollections('<username>', 1, 10);
```

#### Get User's Statistics
Get a user's account statistics.
```
GET /users/:username/statistics
```

##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **username** | *string* | The username of the particular user | no |
| **resolution** | *string* | The frequency of the stats | yes | days
| **quantity** | *int* | The amount of for each stat | yes | 30

```php
$client->getUserStatistics('<username>', 'days', 30);
```

### Photos APIs
#### List Photos
Get a single page from the list of all photos.
```
GET /photos
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **page** | *int* | Page number to retrieve | yes | 1
| **perPage** | *int* | Number of items per page | yes | 10
| **orderBy** | *PhotoOrder* | How to sort the photos.(```Valid values: PhotoOrder::LATEST, PhotoOrder::OLDEST, PhotoOrder::POPULAR```) | yes | PhotoOrder::LATEST

```php
$client->listPhotos(page: 1, perPage: 10, orderBy: PhotoOrder::LATEST);
```

#### List Curated Photos
Get a single page from the list of the curated photos.
```
GET /photos/curated
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **page** | *int* | Page number to retrieve | yes | 1
| **perPage** | *int* | Number of items per page | yes | 10
| **orderBy** | *PhotoOrder* | How to sort the photos.(```Valid values: PhotoOrder::LATEST, PhotoOrder::OLDEST, PhotoOrder::POPULAR```) | yes | PhotoOrder::LATEST

```php
$client->listCuratedPhotos(page: 1, perPage: 10, orderBy: PhotoOrder::LATEST);
```

#### Get a Photo by Id
Retrieve a single photo.
```
GET /photos/:id
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **id** | *string* | The photo's ID | no |
| **width** | *int* | Image width in pixels | yes |
| **height** | *int* | Image height in pixels | yes |
| **rect** | *string* |4 comma-separated integers representing x, y, width, height of the cropped rectangle | yes |

> **Note:** Supplying the optional **width** or **height** parameters will result in the custom photo URL being added to the urls object.

```php
$client->getPhoto('<id of the photo>', 500, 500, 'x, y, width, height');
```

#### Get a Random Photo
Retrieve a single random photo, given optional filters.
```
GET /photos/random
```
##### Parameters
> **Note:** All parameters are optional, and can be combined to narrow the pool of photos from which a random one will be chosen.

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **collections** | *string* | The public collection ID('s) to filter selection. If multiple, comma-separated | yes |
| **featured** | *bool* | Limit selection to featured photos | yes | false
| **username** | *string* | Limit selection to a single user | yes |
| **query** | *string* | Limit selection to photos matching a search term | yes |
| **width** | *int* | The Image width in pixels | yes |
| **height** | *int* | The Image height in pixels | yes |
| **orientation** | *PhotoOrientation* | Filter search results by photo orientation. (```Valid values are PhotoOrientation::LANDSCAPE, PhotoOrientation::PORTRAIT, and PhotoOrientation::SQUARE```) | yes | PhotoOrientation::LANDSCAPE
| **count** | *int* | The number of photos to return. (```max: 30```) | yes | 1

> **Note:** You can't use the collections and query parameters in the same request.
> When supplying a **count** parameter - and only then - the response will be an array of photos, even if the value of **count** is 1.

```php
use SandeepV\WrapsplashPHP\Enums\PhotoOrientation;

$client->getRandomPhoto();
```

#### Get a Photo's Statistics
Retrieve total number of downloads, views and likes of a single photo, as well as the historical breakdown of these stats in a specific timeframe (default is 30 days).
```
GET /photos/:id/statistics
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **id** | *string* | The photo's ID | no |
| **resolution** | *string* | The frequency of the stats | yes | days
| **quantity** | *int* | The amount of for each stat | yes | 30

> **Note:** Currently, the only resolution param supported is "days". The quantity param can be any number between 1 and 30.

```php
$client->getPhotoStatistics('<photo-id>', 'days', 10);
```

#### Get a Photo's Download Link
Retrieve a single photo's download link. Preferably hit this endpoint if a photo is downloaded in your application for use (example: to be displayed on a blog article, to be shared on social media, to be remixed, etc).
```
GET /photos/:id/download
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **id** | *string* | The photo's ID | no |

> **Note:** This is different than the concept of a view, which is tracked automatically when you hotlink an image.

```php
$client->getPhotoDownloadLink('<photo-id>');
```

#### Update a Photo
Update a photo on behalf of the logged-in user. This requires the ```write_photos``` scope and ```bearer_token```.
```
PUT /photos/:id
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **id** | *string* | The photo's ID | no |
| **location** | *array* | The location array holding location data | yes |
| **exif** | *array* | The exif array holding exif data | yes |

> **Note:** **Exchangeable image file format** (officially Exif, according to JEIDA/JEITA/CIPA specifications) is a standard that specifies the formats for images, sound, and ancillary tags used by digital cameras (including smartphones), scanners and other systems handling image and sound files recorded by digital cameras. [Readmore](https://en.wikipedia.org/wiki/Exif)

##### location & exif arrays

| array[key] | Description |
| ----- | ----------- |
| location['latitude'] | The photo location's latitude (Optional) |
| location['longitude'] | The photo location's longitude (Optional) |
| location['name'] | The photo location's name (Optional) |
| location['city'] | The photo location's city (Optional) |
| location['country'] | The photo location's country (Optional) |
| location['confidential'] | The photo location's confidentiality (Optional) |
| exif['make'] | Camera's brand (Optional) |
| exif['model'] | Camera's model (Optional) |
| exif['exposure_time'] | Camera's exposure time (Optional) |
| exif['aperture_value'] | Camera's aperture value (Optional) |
| exif['focal_length'] | Camera's focal length (Optional) |
| exif['iso_speed_ratings'] | Camera's iso (Optional) |

```php
$client->updatePhoto('<photo-id>',
    location: ['country' => 'INDIA'],
    exif: ['make' => 'Redmi Note 3'],
);
```

#### Like a Photo
Like a photo on behalf of the logged-in user. This requires the ```write_likes``` scope.
```
POST /photos/:id/like
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **id** | *string* | The photo's ID | no |

> **Note:**  This action is idempotent; sending the POST request to a single photo multiple times has no additional effect.

```php
$client->likePhoto('<photo-id>');
```

#### Unlike a Photo
Remove a user's like of a photo.
```
DELETE /photos/:id/like
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **id** | *string* | The photo's ID | no |

> **Note:** This action is idempotent; sending the DELETE request to a single photo multiple times has no additional effect.

```php
$client->unlikePhoto('<photo-id>');
```

### Search APIs
#### Search Photos
Get a single page of photo results for a particular query.
```
GET /search/photos
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **query** | *string* | The search query | no |
| **page** | *int* | Page number to retrieve | yes | 1
| **perPage** | *int* | Number of items per page | yes | 10
| **collections** | *string* | Collection ID('s) to narrow search. If multiple, comma-separated. | yes |
| **orientation** | *PhotoOrientation* | Filter search results by photo orientation. (```Valid values are PhotoOrientation::LANDSCAPE, PhotoOrientation::PORTRAIT, and PhotoOrientation::SQUARE.```) | yes |

```php
$client->searchPhotos('cars', page: 1, perPage: 10, orientation: PhotoOrientation::LANDSCAPE);
```

#### Search Collections
Get a single page of collection results for a query.
```
GET /search/collections
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **query** | *string* | The search query | no |
| **page** | *int* | Page number to retrieve | yes | 1
| **perPage** | *int* | Number of items per page | yes | 10

```php
$client->searchCollections('cars', 1, 10);
```

#### Search Users
Get a single page of user results for a query.
```
GET /search/users
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **query** | *string* | The search query | no |
| **page** | *int* | Page number to retrieve | yes | 1
| **perPage** | *int* | Number of items per page | yes | 10

```php
$client->searchUsers('<search-keyword>', 1, 10);
```

### Current User APIs
#### Get User's Profile
Get the current User's profile. To access a user's private data, the user is required to authorize the ```read_user``` scope. Without it, this request will return a ```403 Forbidden response```.
```
GET /me
```
> **Note:** No Parameters are required.

> **Note:**  Without a Bearer token (i.e. using a ```Client-ID token```) this request will return a ```401 Unauthorized``` response.

```php
$client->getCurrentUserProfile();
```

#### Update User's Profile
Update the current User's profile.
```
PUT /me
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| params['username'] | *string* | The username of the current user | yes |
| params['first_name'] | *string* | The first name of the current user | yes |
| params['last_name'] | *string* | The last name of the current user | yes |
| params['email'] | *string* | The email id of the current user | yes |
| params['url'] | *string* | The Portfolio/personal URL of the current user | yes |
| params['location'] | *string* | The location of the current user | yes |
| params['bio'] | *string* | The About/bio of the current user | yes |
| params['instagram_username'] | *string* | The Instagram username of the current user | yes |

> **Note:** This action requires the ```write_user scope```. Without it, it will return a ```403 Forbidden response```.

```php
$client->updateCurrentUserProfile([
    'username' => '<username>',
    'first_name' => '<first_name>',
    'last_name' => '<last_name>',
    'email' => '<email>',
    'url' => '<url>',
    'location' => '<location>',
    'bio' => '<bio>',
    'instagram_username' => '<instagram_username>',
]);
```

### Stats APIs
#### Stats Totals
Get a list of counts for all of Unsplash.
```
GET /stats/total
```
```php
$client->getStatsTotals();
```
#### Response
```sh
200 OK
```
```json
{
  "total_stats": {
    "photos": 10000,
    "downloads": 2000,
    "views": 5000,
    "likes": 800,
    "photographers": 100,
    "pixels": 200000,
    "downloads_per_second": 10,
    "views_per_second": 20,
    "developers": 20,
    "applications": 50,
    "requests": 8000
  }
}
```
#### Stats Month
Get the overall Unsplash stats for the past 30 days.
```
GET /stats/month
```
```php
$client->getStatsMonth();
```
#### Response
```sh
200 OK
```
```json
{
  "month_stats": {
    "downloads": 20,
    "views": 200,
    "likes": 60,
    "new_photos": 10,
    "new_photographers": 5,
    "new_pixels": 2000,
    "new_developers": 8,
    "new_applications": 5,
    "new_requests": 100
  }
}
```

### Collections APIs
#### Link Relations
Collections have the following link relations:

| rel | Description |
| --- | ----------- |
| ```self``` | API location of this collection |
| ```html``` | HTML location of this collection |
| ```photos``` | API location of this collection's photos |
| ```related``` | API location of this collection's related collections (Non-curated collections only) |
| ```download``` | Download location of this collection's zip file (Curated collections only) |

#### List Collections
Get a single page from the list of all collections.
```
GET /collections
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **page** | *int* | Page number to retrieve | yes | 1
| **perPage** | *int* | Number of items per page | yes | 10

```php
$client->listCollections();
```

#### List Featured Collections
Get a single page from the list of featured collections.
```
GET /collections/featured
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **page** | *int* | Page number to retrieve | yes | 1
| **perPage** | *int* | Number of items per page | yes | 10

```php
$client->listFeaturedCollections();
```

#### List Curated Collections
Get a single page from the list of curated collections.
```
GET /collections/curated
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **page** | *int* | Page number to retrieve | yes | 1
| **perPage** | *int* | Number of items per page | yes | 10

```php
$client->listCuratedCollections();
```

#### Get a Collection
Retrieve a single collection. To view a user's private collections, the ```read_collections``` scope is required.
```
GET /collections/:id
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **id** | *string* | The Collection ID  | no |

```php
$client->getCollection('<collection-id>');
```

#### Get a Curated Collection
Retrieve a single curated collection. To view a user's private collections, the ```read_collections``` scope is required.
```
GET /collections/curated/:id
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **id** | *string* | The Collection ID  | no |

```php
$client->getCuratedCollection('<curated-collection-id>');
```

#### Get a Collection's Photos
Retrieve a collection's photos.
```
GET /collections/:id/photos
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **id** | *string* | The Collection ID  | no |
| **page** | *int* | Page number to retrieve | yes | 1
| **perPage** | *int* | Number of items per page | yes | 10

```php
$client->getCollectionPhotos('<collection-id>', 1, 10);
```

#### Get a Curated Collection's Photos
Retrieve a curated collection's photos.
```
GET /collections/curated/:id/photos
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **id** | *string* | The Collection ID  | no |
| **page** | *int* | Page number to retrieve | yes | 1
| **perPage** | *int* | Number of items per page | yes | 10

```php
$client->getCuratedCollectionPhotos('<curated-collection-id>', 1, 10);
```

#### List a Collection's Related Collections
Retrieve a list of collections related to this one.
```
GET /collections/:id/related
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **id** | *string* | The Collection ID  | no |

```php
$client->getRelatedCollections('<collection-id>');
```

#### Create a New Collection
Create a new collection. This requires the ```write_collections``` scope.
```
POST /collections
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **title** | *string* | The title of the collection | no |
| **description** | *string* | The collection's description | yes |
| **private** | *bool* | Whether to make this collection private | yes | false

```php
$client->createCollection('<collection-name>', '<description>', private: false);
```

#### Update an Existing Collection
Update an existing collection belonging to the logged-in user. This requires the ```write_collections``` scope.
```
PUT /collections/:id
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **id** | *string* | The collection id | no |
| **title** | *string* | The title of the collection | yes |
| **description** | *string* | The collection's description | yes |
| **private** | *bool* | Whether to make this collection private | yes | false

```php
$client->updateCollection('<collection-id>', '<collection-name>', '<description>', private: false);
```

#### Delete a Collection
Delete a collection belonging to the logged-in user. This requires the ```write_collections``` scope.
```
DELETE /collections/:id
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **id** | *string* | The Collection ID  | no |

```php
$client->deleteCollection('<collection-id>');
```

#### Add a Photo to a Collection
Add a photo to one of the logged-in user's collections. Requires the ```write_collections``` scope.
```
POST /collections/:collection_id/add
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **collection_id** | *string* | The Collection ID  | no |
| **photo_id** | *string* | The Photo ID  | no |

> **Note:**  If the photo is already in the collection, this action has no effect.

```php
$client->addPhotoToCollection('<collection-id>', '<photo-id>');
```

#### Remove a Photo from a Collection
Remove a photo from one of the logged-in user's collections. Requires the ```write_collections``` scope.
```
DELETE /collections/:collection_id/remove
```
##### Parameters

| Parameter | Type | Description | Optional | Default |
| ----- | ---- | ----------- | -------- | ------- |
| **collection_id** | *string* | The Collection ID  | no |
| **photo_id** | *string* | The Photo ID  | no |

```php
$client->removePhotoFromCollection('<collection-id>', '<photo-id>');
```

### Error Handling

All API errors throw `SandeepV\WrapsplashPHP\WrapSplashException`:

```php
use SandeepV\WrapsplashPHP\WrapSplashException;

try {
    $photo = $client->getPhoto('invalid-id');
} catch (WrapSplashException $e) {
    echo $e->getMessage();    // Error message
    echo $e->getCode();       // HTTP status code (if available)
    echo $e->statusText;      // HTTP status text (if available)
}
```

### Enums

```php
use SandeepV\WrapsplashPHP\Enums\PhotoOrder;
use SandeepV\WrapsplashPHP\Enums\PhotoOrientation;

PhotoOrder::LATEST;    // 'latest'
PhotoOrder::OLDEST;    // 'oldest'
PhotoOrder::POPULAR;   // 'popular'

PhotoOrientation::LANDSCAPE;  // 'landscape'
PhotoOrientation::PORTRAIT;   // 'portrait'
PhotoOrientation::SQUARE;     // 'squarish'
```

### Tests
WrapSplashPHP uses [PHPUnit](https://phpunit.de/) as the testing framework. Test files are available in the `tests/` folder.

```sh
vendor/bin/phpunit
```

### License
The MIT License

Copyright (c) 2026 Sandeep Vattapparambil

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.


### Acknowledgements
Thanks, and Kudos to team [Unsplash](https://unsplash.com/) for creating a wonderful platform for sharing
beautiful high quality free images and photos.

Made with :heart: by [Sandeep Vattapparambil](https://github.com/SandeepVattapparambil).

PHP port of [WrapSplashTS](https://github.com/SandeepVattapparambil/wrapsplash).
