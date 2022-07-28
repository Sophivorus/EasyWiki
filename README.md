# EasyWiki

**EasyWiki** is a friendly PHP library to interact with [MediaWiki](https://mediawiki.org/). It's designed to be used both in and out the MediaWiki environment, to develop bots, scripts, extensions and other tools.

Essentially, EasyWiki is a client for the [MediaWiki API](https://www.mediawiki.org/wiki/API). However, when run in a MediaWiki environment (for example as part of an extension), EasyWiki will [call the API internally](https://www.mediawiki.org/wiki/API:Calling_internally#From_application_code). This makes it an interesting option when developing locally because the API is way more stable, easier to use and better documented than the internal way of doing things (see [Motivation](#motivation)).

EasyWiki is a single, simple, beautiful PHP class, so [checking the source code directly](https://github.com/Sophivorus/EasyWiki/blob/main/EasyWiki.php) is the best way to learn all about it. You can also check the [autogenerated documentation](https://sophivorus.github.io/EasyWiki/classes/EasyWiki.html) for details about every available method.

## Quickstart

```php

// Copy-paste EasyWiki.php to your environment
require_once '/path/to/EasyWiki.php';

// Connect to the English Wikipedia API
$wikipedia = new EasyWiki( 'https://en.wikipedia.org/w/api.php' );

// Read data immediately
$wikitext = $wikipedia->getWikitext( 'Science' );

// Connect to your local wiki
$wiki = new EasyWiki;

// Write data immediately
$wiki->create( 'Science', $wikitext );

```

## Overview

EasyWiki is a single PHP class with a very simple architecture and minimal assumptions:

- **Base methods** are the basic building blocks to interact with the MediaWiki API:
    - `get()` makes a GET request to the API
    - `post()` makes a POST request to the API
    - `find()` extracts data from the results
- **Shorthand methods** use the base methods to simplify common requests:
    - `login()`
    - `logout()`
    - `query()`
    - `parse()`
    - `edit()`
    - `move()`
    - `delete()`
    - `create()`
    - `append()`
    - `prepend()`
    - `getHTML()`
    - `getWikitext()`
    - `getCategories()`
    - `getInfo()`
    - `getSiteInfo()`
    - `getNamespaces()`
    - `getToken()`

If none of the shorthand methods serves your needs, you can always call the base methods directly.

## Installation

### Manual

1. Clone this repo or copy-paste the main EasyWiki.php file somewhere in your PHP project.
2. Require the EasyWiki.php file wherever you need it with:

```php
require '/path/to/EasyWiki.php';
```

### Composer

1. Require EasyWiki with `composer require sophivorus/easy-wiki` or add it as a dependency to your composer.json or composer.local.json as `"sophivorus/easy-wiki": "^1.0"`
2. Load the EasyWiki class wherever you need it with:

```php
require '/path/to/autoload.php'; // This may not be necessary
use Sophivorus\EasyWiki;
```

## Initialization

Once EasyWiki is available, initialize it by specifying an API endpoint:

```php
// Create a EasyWiki instance connected to a remote wiki
$wiki = new EasyWiki( 'https://en.wikipedia.org/w/api.php' );
```

If you're in a MediaWiki environment and want to connect to the local wiki, just omit the API endpoint:

```php
// Create a EasyWiki instance connected to the local wiki
$wiki = new EasyWiki;
```

If you're not in a MediaWiki environment and want to connect to the local wiki, the easiest way is to connect as if it were a remote wiki:

```php
// Create a EasyWiki instance connected to the local wiki
$wiki = new EasyWiki( 'https://www.yourwiki.org/w/api.php' );
```

However, if your code runs intensively, you may need a direct connection. If your code is run from the browser, you can do:

```php
// Initialize MediaWiki
require '/path/to/wiki/includes/WebStart.php';

// Create a EasyWiki instance connected to the local wiki
$wiki = new EasyWiki;
```

But if your code is run internally, for example by a cronjob, you can wrap it in a [maintenance script](https://www.mediawiki.org/wiki/Manual:Writing_maintenance_scripts):

```php
// Initialize MediaWiki
require '/path/to/wiki/maintenance/Maintenance.php';

class EasyWikiScript extends Maintenance {

	public function execute() {
		// Create a EasyWiki instance connected to the local wiki
		$wiki = new EasyWiki;
	}
}

$maintClass = EasyWikiScript::class;
require RUN_MAINTENANCE_IF_MAIN;
```

## Authentication

If you only want to read data from a public wiki (such as Wikipedia) then **you don't need to authenticate**, you can move on to the next section.

However, if you want to write data to a public wiki, or read data from a private wiki, you'll need to authenticate **with a bot account**. Bot accounts are created from Special:BotPasswords and are the simplest way to get through the MediaWiki security mechanisms.

```php

// Be very careful not to publish your bot password by mistake!!!
$wiki->login( 'Your bot username', 'Your bot password' );

```

## Reading

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

## Writing

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

## Motivation

MediaWiki is ugly, very ugly. Not only ugly, but difficult. For example, to simply create a page, the current, minimal internal routine would be:

```php
$title = Title::newFromText( 'Foo' );
$user = User::newSystemUser( 'Bot' );
$timestamp = wfTimestampNow();
$config = HashConfig::newInstance();
$revision = new WikiRevision( $config );
$revision->setTitle( $title );
$revision->setModel( 'wikitext' );
$revision->setText( 'Hello world!' );
$revision->setUserObj( $user );
$revision->setTimestamp( $timestamp );
$revision->importOldRevision(); // ...and this method is deprecated now
```

Similarly, if you wanted to simply edit a page:

```php
$title = Title::newFromText( 'Foo' )
$user = User::newSystemUser( 'Bot' );
$page = WikiPage::factory( $title );
$content = ContentHandler::makeContent( 'Bye world!', $title );
$comment = CommentStoreComment::newUnsavedComment( '' );
$updater = $page->newPageUpdater( user );
$updater->setContent( 'main', $content );
$updater->saveRevision( $comment );
```

This ugliness is partly because the priorities of the MediaWiki development team are generally those of Wikipedia and other Wikimedia projects, not of third-party projects and developers. In any case, developing in MediaWiki has become a daunting task: slots, singletons, contexts, factories and many other complexities. But it need not be so, EasyWiki promises a way out!

```php
$wiki = new EasyWiki;
$wiki->create( 'Foo', 'Hello world!' );
$wiki->edit( 'Foo', 'Bye world!' );
```

This does exactly the same as the previous two routines, with minimal performance overhead.
