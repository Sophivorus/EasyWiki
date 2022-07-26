# EasyWiki

**EasyWiki** is a friendly PHP library to interact with [MediaWiki](https://mediawiki.org/).

## Motivation

EasyWiki is designed to be used both in and out the MediaWiki environment, by bots, scripts, extensions and other pieces of code.

Essentially, EasyWiki is a PHP client for the [MediaWiki Action API](https://www.mediawiki.org/wiki/API). However, when run in a MediaWiki environment, EasyWiki will [call the API internally](https://www.mediawiki.org/wiki/API:Calling_internally#From_application_code). This is much more efficient than calling an external API, though less efficient than calling PHP methods directly. However, unless extreme performance is needed, the overhead is more than compensated by the ease of use. Machines should make life easier for humans, not the other way round!

Using the API allows us to ignore any differences between doing things in or out the MediaWiki environment. Furthermore, given the very public nature of the API, it's way more stable and better documented than the internal way of doing things, so by using the API we can leave internal changes to the MediaWiki development team. Their priorities are generally in line with the necessities of Wikimedia projects and not of third-party projects and developers. As a consequence, developing in MediaWiki has become a daunting task: slots, singletons, contexts, factories and many other complexities. But it need not be so, EasyWiki promises a way out.

**Wiki developers of the world, unite!**

## Features

With EasyWiki you can easily:

- Create pages
- Edit pages
- Move (rename) pages
- Delete pages
- Categorize and uncategorize pages
- Get the wikitext of pages
- Get the HTML of pages
- Parse any wikitext into HTML
- Get data from the API
- Post data to the API

...and so much more!

## Quickstart

```php

// Clone this repo or copy-paste EasyWiki.php
require_once '/path/to/EasyWiki.php';

// Connect to the English Wikipedia API
$wiki = new EasyWiki( 'https://en.wikipedia.org/w/api.php' );

// Read data immediately
$text = $wiki->getWikitext( 'Science' );

// Authenticate to write data
$wiki->login( 'Your bot username', 'Your bot password' );

$wiki->edit( 'Wikipedia:Sandbox', 'Hello world!', [ 'summary' => 'Testing EasyWiki' ] );

```

## Overview

EasyWiki is a single PHP class with a very simple architecture, designed to be easily extensible:

- **Base methods** for interacting with the MediaWiki API:
    - `get()` makes a GET request to the API
    - `post()` makes a POST request to the API
    - `find()` extracts data from the results
- **Action methods** one per module:
    - `login()`
    - `logout()`
    - `query()`
    - `parse()`
    - `edit()`
    - `move()`
    - `delete()`
- **Shorthand methods** for frequent tasks:
    - `create()`
    - `append()`
    - `prepend()`
    - `getHTML()`
    - `getWikitext()`
    - `getCategories()`
    - `getInfo()`

If none of the shorthand methods serves your needs, you can use the action methods, and if none of those serves you either, you can fallback to the base methods.

Each method has one argument for each required parameter and an optional last argument for extra parameters.

## Manual

### Installation

1. Clone this repo or copy-paste the main EasyWiki.php file wherever you need it.
2. Include or require the main EasyWiki.php file in your PHP project.

```php

require_once '/path/to/EasyWiki.php';

```

### Initialization

Once EasyWiki is available, initialize it by specifying an API endpoint:

```php

// Create a EasyWiki instance connected to the English Wikipedia API
$wiki = new EasyWiki( 'https://en.wikipedia.org/w/api.php' );

```

### Authentication

If you only want to read data from a public wiki (such as Wikipedia) then **you don't need to authenticate**, you can move on to the next section.

However, if you want to write data to a public wiki, or read data from a private wiki, you'll need to authenticate **with a bot account**. Bot accounts are created from Special:BotPasswords and are the simplest way to get through the MediaWiki security mechanisms.

```php

// Be very careful not to publish your password by mistake!!!
$wiki->login( 'Your bot username', 'Your bot password' );

// You can also login when initializing
$wiki = new EasyWiki( 'https://en.wikipedia.org/w/api.php', 'Your bot username', 'Your bot password' );

```

### Reading

```php

// Get the wikitext of the page named 'Foo'
$wiki->getWikitext( 'Foo' );

// Get the HTML of the page named 'Foo'
$wiki->getHTML( 'Foo' );

// Get the categories of the page named 'Foo'
$wiki->getCategories( 'Foo' );

// Check if the page named 'Foo' is in the category named 'Bar'
$wiki->inCategory( 'Foo', 'Bar' );

```

If none of the available methods serves your needs, you can always query the API directly. EasyWiki provides handy methods for doing so and for extracting the desired data from the results, but some familiarity with the [MediaWiki API](https://www.mediawiki.org/wiki/API) is required.

```php

// Prepare a query (no need to specify the result format)
$query = [
    'titles' => 'Foo',
    'action' => 'query',
    'prop' => 'info'
];

// Get the results as an associative array
$data = $wiki->get( $query );

// Or magically extract the desired piece of data from the gazillion wrappers
$language = $wiki->get( $query, 'pagelanguage' );

// If the result contains more than one relevant piece of data, you'll get an array of values instead
$languages = $wiki->get( [ 'titles' => 'Foo|Bar|Baz', 'action' => 'query', 'prop' => 'info' ], 'pagelanguage' );
foreach ( $languages as $language ) {
    echo $language;
}

```

### Writing

```php

// Create a page named 'Foo' with content 'Hello world!'
$wiki->create( 'Foo', 'Hello world!' );

// Replace the content of the page named 'Foo' with 'Bye!'
$wiki->edit( 'Foo', 'Bye!' );

// Rename the page named 'Foo' to 'Bar'
$wiki->move( 'Foo', 'Bar' );

// Add the page named 'Foo' to the Category:Examples
$wiki->addCategory( 'Foo', 'Examples' );

// Remove the page named 'Foo' from the Category:Examples
$wiki->removeCategory( 'Foo', 'Examples' );

```

All methods have an optional last argument where you can specify extra parameters. For example:

```php

// Add a summary
$wiki->create( 'Foo', 'Hello world!', [ 'summary' => 'My first edit!' ] );

// Mark the edit as minor
$wiki->edit( 'Foo', 'Bye!', [ 'minor' => true ] );

// Don't leave a redirect behind and move all subpages too
$wiki->move( 'Foo', 'Bar', [ 'noredirect' => true, 'movesubpages' => true ] );

```

The available options are the same as the ones available in the relevant MediaWiki API action.

### More

Check the source code and the comments to learn about every available method.

EasyWiki does very minimal assumptions. The only parameters hardcoded are the ones specifying the result format (json), result version (2) and the error format (plaintext). You can override them through the optional last argument.
