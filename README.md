# EasyWiki

**EasyWiki** is a friendly PHP library to interact with [MediaWiki](https://mediawiki.org/). It's designed to be used both in and out the MediaWiki environment, to develop bots, scripts, extensions and other tools.

Essentially, EasyWiki is a client for the [MediaWiki API](https://www.mediawiki.org/wiki/API). However, when run in a MediaWiki environment (for example in an extension), EasyWiki will [call the API internally](https://www.mediawiki.org/wiki/API:Calling_internally#From_application_code), thus avoiding slow and unnecessary HTTP requests. This makes it an interesting option when developing locally, especially because the API is way more stable, easier and better documented than the internal way of doing things (see [Motivation](#motivation)).

EasyWiki is a single, simple, beautiful PHP class, so [checking the source code directly](https://github.com/Sophivorus/EasyWiki/blob/main/EasyWiki.php) is the best way to learn all about it. You can also check the [autogenerated documentation](https://sophivorus.github.io/EasyWiki/classes/EasyWiki.html) for details about every available method.

## Quickstart

```php

// Copy-paste EasyWiki.php to your environment
require_once '/path/to/EasyWiki.php';

// Connect to the English Wikipedia API
$wiki = new EasyWiki( 'https://en.wikipedia.org/w/api.php' );

// Read data immediately
$text = $wiki->getWikitext( 'Science' );

// Authenticate to write data
$wiki->login( 'Your bot username', 'Your bot password' );

$wiki->edit( 'Wikipedia:Sandbox', $text, [ 'summary' => 'Testing EasyWiki' ] );

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

## Motivation

MediaWiki is ugly, very ugly. Not only ugly, but difficult. For example, to simply create a page, the current, minimal internal routine would be:

```php
$Title = Title::newFromText( 'Foo' );
$User = User::newSystemUser( 'Bot' );
$timestamp = wfTimestampNow();
$Config = HashConfig::newInstance();
$Revision = new WikiRevision( $Config );
$Revision->setTitle( $Title );
$Revision->setModel( 'wikitext' );
$Revision->setText( 'Hello world!' );
$Revision->setUserObj( $User );
$Revision->setTimestamp( $timestamp );
$Revision->importOldRevision(); // ...and this method is deprecated now
```

Meanwhile, with EasyWiki:

```php
$wiki = new EasyWiki;
$wiki->create( 'Foo', 'Hello world!' );
```

Similarly, if you wanted to simply edit a page:

```php
$Title = Title::newFromText( 'Foo' )
$User = User::newSystemUser( 'Bot' );
$Page = WikiPage::factory( $Title );
$Content = ContentHandler::makeContent( $text, $Title );
$Comment = CommentStoreComment::newUnsavedComment( '' );
$Updater = $Page->newPageUpdater( $User );
$Updater->setContent( 'main', $Content );
$Updater->saveRevision( $Comment );
```

But with EasyWiki:

```php
$wiki = new EasyWiki;
$wiki->edit( 'Foo', 'Bye world!' );
```

This is partly because the priorities of the MediaWiki development team are generally those of Wikipedia and other Wikimedia projects, not of third-party projects and developers. In any case, developing in MediaWiki has become a daunting task: slots, singletons, contexts, factories and many other complexities. But it need not be so, EasyWiki promises a way out!
