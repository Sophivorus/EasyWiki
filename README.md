# EasyWiki

**EasyWiki** is a friendly PHP client for the [MediaWiki Action API](https://www.mediawiki.org/wiki/API:Main_page).

## Quickstart

```
composer require sophivorus/easy-wiki
```

```php
require 'vendor/autoload.php';
use Sophivorus\EasyWiki;

// Connect to a MediaWiki Action API endpoint
$api = new EasyWiki( 'https://en.wikipedia.org/w/api.php' );

// Read data immediately
$wikitext = $api->getWikitext( 'Science' );
```

## Authentication

If you only want to read data from a public wiki (such as Wikipedia), then **you don't need to authenticate**, you can move on to the next section.

However, if you want to write data or read data from a private wiki, you'll need to authenticate **with a bot account**. Bot accounts are created from Special:BotPasswords and are the simplest way to get through the MediaWiki security mechanisms.

```php
// Be very careful not to publish your bot password by accident!!!
$api->login( 'Your bot username', 'Your bot password' );
```

## Reading

```php
// Get the wikitext of the page named 'Foo'
$api->getWikitext( 'Foo' );

// Get the HTML of the page named 'Foo'
$api->getHTML( 'Foo' );

// Get the categories of the page named 'Foo'
$api->getCategories( 'Foo' );
```

If none of the available methods serves your needs, you can always query the API directly. EasyWiki provides handy methods for doing so and for extracting the desired data from the results. Check out the [MediaWiki Action API documentation](https://www.mediawiki.org/wiki/API:Main_page) for the available parameters.

```php
// Get the results as an associative array
$data = $api->query( [ 'titles' => 'Foo', 'prop' => 'info' ] );

// Magically extract the desired piece of data from the gazillion wrappers
$language = $api->find( 'pagelanguage', $query );

// If the result contains more than one relevant piece of data, you'll get an array of values instead
$data = $api->query( [ 'titles' => 'Foo|Bar|Baz', 'prop' => 'info' ] );
$languages = $api->find( 'pagelanguage', $data );
foreach ( $languages as $language ) {
    echo $language;
}
```

## Writing

```php
// Create a page named 'Foo' with content 'Hello world!'
$api->create( 'Foo', 'Hello world!' );

// Replace the content of the page named 'Foo' with 'Bye!'
$api->edit( 'Foo', [ 'text' => 'Bye!' ] );

// Rename the page named 'Foo' to 'Bar'
$api->move( 'Foo', 'Bar' );

// Delete the page named 'Bar'
$api->delete( 'Bar' );
```

All methods have an optional last argument where you can specify extra parameters. For example:

```php
// Add a summary
$api->create( 'Foo', 'Hello world!', [ 'summary' => 'My first edit!' ] );

// Mark the edit as minor
$api->edit( 'Foo', 'Bye!', [ 'minor' => true ] );

// Don't leave a redirect behind and move all subpages too
$api->move( 'Foo', 'Bar', [ 'noredirect' => true, 'movesubpages' => true ] );
```

## Architecture

Check out the [source code](https://github.com/Sophivorus/EasyWiki/blob/main/EasyWiki.php) for the ultimate documentation.

- **Base methods** are the basic building blocks to interact with the MediaWiki Action API:
    - `get()` makes a GET request to the API
    - `post()` makes a POST request to the API
    - `find()` extracts data from the results
- **Action methods** use the base methods to interact with the API modules:
    - `login()`
    - `logout()`
    - `query()`
    - `parse()`
    - `edit()`
    - `move()`
    - `delete()`
- **Shorthand methods** simplify common requests:
    - `create()`
    - `append()`
    - `prepend()`
    - `getHTML()`
    - `getWikitext()`
    - `getCategories()`
    - `getPageInfo()`
    - `getSiteInfo()`
    - `getNamespaces()`
    - `getToken()`

If none of the existing methods serves your needs, you can always call the base methods directly.
