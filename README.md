Occipital
=========

![Occipital](https://googledrive.com/host/0Bxo4bHbWEkMscmJNYkx6YXctaWM/occipital.png)

Occipital is a [**Brain**](http://giuseppe-mazzapica.github.io/Brain/) module for **style and scripts management**.

It allows a better way to enqueue scripts and styles in WordPress.

Occipital, like others Brain modules is **not** a full-plugin, its a package to be embedded in larger projects.

However, embed in a theme or in a plugin is just matter of, literally, 3 lines of code and there is a [step-by-step guide](/occipital-in-theme-guide.md) to include Occipital in a theme.


----------


#Features

- Possibility to define, for each asset, which other assets it **provides**, so that WordPress can skip loading them decreasing number of HTTP requests per page.

- Easy to use API that allows to add assets and additional data in one single place, with no need to use 3 different functions like `wp_register_script`, `wp_enqueue_script` and `wp_localize_script` (or `wp_add_inline_style` for styles).

- Conditional callback with rich context: for every asset is possible to set a "condition" as a callback: the asset is printed to page only if the callback returns true. That callback receives as argument current main [`WP_Query`](https://developer.wordpress.org/reference/classes/wp_query/) object in frontend and current [`WP_Screen`](https://developer.wordpress.org/reference/classes/wp_screen/) on backend, alongside current logged user (if any).

- Rich API to get and modify assets properties (url, dependencies, provided assets, version...) *on the fly*

- Custom hooks fired before and after an asset is printed to markup, for a great flexibility.

- Correct handling of styles in `<head>` for login / register page.

- Just like all Brain packages, it is coded using all modern OOP PHP code and fully unit-tested.


----------


#Quick Start

All the things in Occipital can be done via API class: **`Brain\Assets`**.

Before do any call, you should wait for `'brain_loaded'` hook, or `'init'` if you prefer core hooks.

Add a script is just a matter of:

``` php
<?php
Brain\Assets::addScript( 'my-script', 'front' )
  ->src( "//example.com/path/to/style.js" )
  ->deps( [ "jquery" ] )
  ->provide( "bootstrap-tooltip", "lightbox", "plugin-script" )
  ->ver( "20141007" )
  ->isFooter( TRUE )
  ->localizeData( [ 'name' => 'MyData', 'data' => [ 'foo' => 'bar' ] ] )
  ->condition( function( $query, $user ) {
  return $query->is_front_page() && user_can( $user, 'edit_pages' );
});
```

**`addScript`** method allows to add script in frontend, backend and login pages. To add asset in a specific place is possible:

- use the second argument for `addScript`, like so:
``` php
Brain\Assets::addScript( 'my-script', 'front' ) // or 'admin' or 'login'
```
- use a dedicaded API method like `addFrontScript`, `addAdminScript` or `addLoginScript`
- call the API method on a specific hook, like `"wp_enqueue_scripts"` or `"admin_enqueue_scripts"`. Occipital also provide custom alternative hooks, see [Occipital hooks](#occipital-hooks) for further details.

API methods return the instance of asset object jsu added, or a `WP_Error` if something goes wrong.

For styles there are similar methods: **`addStyle`**, **`addFrontStyle`**... and so on.

Improvements against core are:

- "`provide`" arguments that let's save http request
- one API to rule all functions
- assets properties get/update after addition made easy
- expressive fluent API: non need to remember exact order of arguments
- condition callback with rich context (`WP_Query` + `WP_User` in frontend, WP_Screen + `WP_User` in backend)
- OOP code: easy to embed in OOP projects and easy testing with mocks in unit tests.


----------


#Requirements

- PHP 5.4+
- WordPress 3.9+
- [Composer](https://getcomposer.org/) to install


----------


#Installation

You need **Composer** to install the package. It is hosted on Packagist, so the only thing needed is insert `"brain/occipital": "dev-master"` in your `composer.json` require object:

``` js
{
  "require": {
     "php": ">=5.4",
     "brain/occipital": "dev-master"
  }
}
```

After that, in your console, navigate to package folder and type

``` bash
composer install --no-dev
```

Don't forget the `--no-dev` flag before using in production, otherwise all the dev dependencies with related autoload stuff will be loaded on every page load, substantially slowing down your page loading.


----------


#Documentation

- [API class](#api-class)
- [Bootstrap](#bootstrap)
- [Adding assets](#adding-assets)
    - [Arguments summary](#arguments-summary)
    - [Fluent interface](#fluent-interface)
    - [Condition callback](#condition-callback)
    - [Provide](#provide)
        - [Gotchas for "provided" styles on login pages](#gotchas-for-provided-styles-on-login-pages)
  - [Big warning for developers](#big-warning-for-developers)
- [Removing assets](#removing-assets)
- [Assets classes](#assets-classes)
- [Get and set assets properties](#get-and-set-assets-properties)
     - [Get info for non-occipital assests](#get-info-for-non-occipital-assests)
- [Error handling](#error-handling)
- [Occipital hooks](#occipital-hooks)
    - [Ready hooks](#ready-hooks)
    - [Printing hooks](#printing-hooks)
- [License](#license)


----------


##API class

Occipital comes with an API that ease its usage, without having to get, instantiate or digging into package objects. The API class is **`Brain\Assets`**.

All operations in Occipital can be done via its API, as static methods, e.g.

``` php
Brain\Assets::addStyle( 'foo' );
```

`addStyle` above (just like all the other API methods) is not a "real" static method: API class works just as a *proxy* to proper instantiated objects methods. It's the same concepts behind [Laravel facades](http://laravel.com/docs/4.2/facades), but instead of Laravel IoC, Occipital uses [Brain](https://github.com/Giuseppe-Mazzapica/Brain) module, that is a [Pimple](http://pimple.sensiolabs.org/) container with some WordPress-specific *sugars*.


----------


##Bootstrap

Before being able to use any Occipital API method we have to be sure that it is fully loaded. As a Brain module, we can do that using specific `"brain_loaded"` hook, but core `"init"` is fine too.

Moreover, we need to bootstrap Occipital, just after Composer autoload has been loaded. This is all you need to do before to use Occipital API:

``` php
<?php
require_once '/path/to/vendor/autoload.php';

Brain\Occipital::boot();

add_action( 'brain_loaded', function() {
  // here go all the API methods
} );
```


----------


##Adding assets

Occipital supports adding assets (styles and scripts) in 3 places: frontend, backend and login page.

Main API methods to add assets are: **`addStyle`** and **`addScript`**.

These methods signature is:

``` php
addStyle( $handle, $args, $where )
```

**`$handle`** is the style / script id, it must be unique, and is the only required argument.

**`$args`** is an array of arguments for the assets, more on this [later in the page](#arguments-summary).

**`$where`** is the specific place where you want to add the asset, it can be:
- "admin" (aliases: "back", "backend")
- "front" (aliases: "frontend", "public")
- "login" (alias: "register")
- "all" (alias: "*")

If nothing is given for `$where` than "all" is assumed, so the asset will be added in frontend, in backend and in login pages.

To add assets on a specific place, there are specific methods:

**`addFrontStyle`** / **`addFrontScript`** (for frontend)
**`addAdminStyle`** / **`addAdminScript`** (for backend)
**`addLoginStyle`** / **`addLoginScript`** (for login page)
**`addSiteStyle`** / **`addSiteScript`** (for site-wide assets, i.e. everywhere)


###Arguments summary

Second argument for `add*` methods is `$args` where is possible to set all the arguments for the asset.

Below there is the complete list of supported keys for it:

- **`src`** (string) Asset full url
- **`deps`** (array) Array of asset dependencies
- **`ver`** (string|int|void) Asset version, `NULL` to not add any version
- **`condition`** (callable) This is a callback that runs before the asset is added. If the callback return a falsey value the asset is not added. More about this argument [later in the page](#condition-callback)
- **`provide`** (array) Array of assets that are *included* in the asset being added. More about this argument [later in the page](#provide).
- **`media`** (string) Only for styles. The "media" attribute for css
- **`footer`** (boolean) Only for scripts. If true, script is added in footer
- **`after`** (string) Only for styles. Allows to add inline styles after the css is added to page. To use this argument is an alternative to call  [`wp_add_inline_style`](https://developer.wordpress.org/reference/functions/wp_add_inline_style/)
- **`localizeData`** (array) Only for scripts. Set a data object to be passed to javascript. The array must contain 2 keys: "name" with the name of the javascript object and "data" with the data itself. To use this argument is an alternative to call [`wp_localize_script`](https://developer.wordpress.org/reference/functions/wp_localize_script/)

As you can see, most of the arguments pairs with core arguments for `wp_register_*` and `wp_enqueue_*` functions, or with other core functions (`wp_add_inline_style`, `wp_localize_script`).

Only *new* arguments are "condition" and "provide" that are further explained later in this page.


###Fluent interface

Passing configuration arguments to a function via an array is a very common task in Wordpress world. However I for myself, and probably others, thinks that fluent interface used by some popular PHP frameworks is very easy to use and read. This is the main (but not the only) reason why Occipital supports this pattern.

Essentially, there is one setter method for each argument (named in the exact way of related key in arguments array) and every setter return the asset object itself, allowing to call another setter in a "chained" way (do you know jQuery? Something like that)..

Example:

``` php
<?php
Brain\Assets::addFrontScript( 'my-script' )
  ->src( "//example.com/path/to/script.js" )
  ->deps( [ "jquery" ] )
  ->provide( "bootstrap-tooltip", "lightbox", "plugin-script" )
  ->ver( "20141007" )
  ->footer( TRUE )
  ->localizeData( [ 'name' => 'MyData', 'data' => [ 'foo' => 'bar' ] ] )
  ->condition( function( $query, $user ) {
    return $query->is_front_page() && user_can( $user, 'edit_pages' );
  });
```

`localizeData` (or `after` for styles) can be called more than once, to send more objects to javascript, example:

``` php
<?php
Brain\Assets::addFrontScript( 'my-script' )
  ->src( "//example.com/path/to/script.js" )
  ->localizeData( [ 'name' => 'MyData1', 'data' => [ 'id' => '1' ] ] )
  ->localizeData( [ 'name' => 'MyData2', 'data' => [ 'id' => '2' ] ] )
  ->localizeData( [ 'name' => 'MyData3', 'data' => [ 'id' => '3' ] ] );
```

Consider that setter methods have **not** to be called in a "chained" way, they can be called to an instance of asset object at anytime.

Thanks to the fact that an asset object can be retrieved in every part of the code, (how-to is explained [later in this page](#get-info-for-non-occipital-assests)) this pattern gives a lot of flexibility that the "array way" can't give.

Only note that calling setters *after* an asset is printed to page, make no sense and has no effect, of course.


###Condition callback

A lot of times, an asset should be added only under specific conditions, this is main reason why assets in WordPress have to be added hooking specific actions: to be sure that a context is available to choose if add the asset or not.

Occipital solve the problem with a different approach: it allow to set a callback as condition, and it is evaluated only at right timing, no matter when the asset is added to the stack. In this way is possible to avoid the addition of asset in 2 separate function `wp_register_*` and `wp_enqueue_*` and is also possible pass a context to condition callback to facilitate developer works.

This is how a condition may look like for frontend:

``` php
<?php
$args['condition'] = function( WP_Query $query, $user ) {
  return $query->is_page( 'special_page' ) && user_can( $user, 'edit_pages')
}
```

and for backend

``` php
<?php
$args['condition'] = function( WP_Screen $screen, WP_User $user ) {
  return $screen->base === 'post' && user_can( $user, 'edit_pages')
}
```

The condition callback receives 3 arguments:

 - first argument is the main query object in frontend requests, and the current [`WP_Screen`](https://developer.wordpress.org/reference/classes/wp_screen/) object in backend, `FALSE` in login page
 - second argument is the current user object, `FALSE` if no user is logged. Always `FALSE` on login pages.
 - third argument is an integer used internally to identify which is the current "side" (frontend, backend or login). Avoid to use this, use first argument if you need to identify the right context.


###Provide

Provide allows to set an array of assets that are "contained" in the assed being added, it will avoid WordPress to load that files while ensuring compatibility with third party code.

Big problem with WordPress assets is the high number of http requests a typical page has (have you read the [blog post](http://gm.zoomlab.it/2014/whats-wrong-with-styles-and-scripts-in-wordpress/)?).

If an user have 20 plugins installed and half of them add a script and a style, and a couple of styles and scripts are added by theme, a page loading will require 24 http requests to load everything.

That's quite crazy.

Occipital approach is simple: it allows site owners to enqueue *concatenated* scripts and styles, and declare wich assets are shipped in the "big" file, to ensure compatibility with any other code added later.

As example, let's assume in a site header there is

``` html
<link rel='stylesheet' id='open-sans-css'  href='//fonts.googleapis.com/css?family=Open+Sans' type='text/css' media='all' />

<link rel='stylesheet' id='theme-style-css'  href='//example.com/path/to/style.css' type='text/css' media='all' />

<link rel='stylesheet' id='plugin1-style-css'  href='//example.com/path/to/plugin1.css' type='text/css' media='all' />

<link rel='stylesheet' id='plugin2-style-css'  href='//example.com/path/to/plugin2.css' type='text/css' media='all' />
```

site owner can use Occipital to do something like this:

``` php
<?php
Brain\Assets::addFrontStyle( 'mystyle' )
  ->src( '//cdn.example.com/path/to/mystyle.css' )
  ->provide([ 'open-sans', 'theme-style', 'plugin1-style', 'plugin2-style' ]);
```

And, like a magic, 4 http requests turned 1 coming from a CDN.

Of course concatenated style is **not** forced to contain the styles declared as provided in the exact version shipped by plugins, and actually is not forced to contain them at all, it's just a way to force WordPress to not add them, even if other code will enqueue them or declare them as dependency.

E.g. let's assume in the same site, the owner want to try a plugin and this plugin has in its code:

``` php
<?php
wp_enqueue_style( 'plugin3-style', $url, array( 'plugin1-style', 'thickbox') );
```

The style from this plugin will be enqueued as expected, `thickbox` will be added because declared as dependency and not provided by any other style, but `'plugin1-style'` will not be added, because declared as provided.


####Gotchas for "provided" styles on login pages

If a site owner use `provide` Occipital feature to include in a custom concatenated file styles provided by core for **login** page (their handles are `'buttons'`, `'open-sans'`, `'dashicons'`, `'login'`) it will **not work**. Reason is that core styles in login pages are printed with a direct call to [`wp_admin_css`](https://developer.wordpress.org/reference/functions/wp_admin_css/) that can't be filtered, and also run *before* `"login_enqueue_scripts"` so any style added using that hook are taken into account when core styles are already printed. The `provide` feature works as expected for custom styles in login pages.


###Big warning for developers

Until here I always said that `provide` feature should be used by site owners. Never mentioned plugin / theme developers.

Reason is that **plugin / theme developers should use `provide` feature with a lot of care**.

Let's assume a plugin uses following code:

``` php
<?php
Brain\Assets::addFrontScript( 'pluginscript' )
  ->src( $scripturl )
  ->provide([ 'jquery', 'jquery-ui-core' ]);
```

It means that the script added by plugin contains a version of jQuery and jQuery UI.

What happen if another plugin provides that scripts *again*? Only thing Occipital can do is to enqueue again those scripts, because it can't be able to remove a portion from a concatenated file...

However, developers can benefit from `provide` feature in assets management for own plugins.

Let's clarify with an example.

Let's assume a plugin have a free version and premium addon. Free version needs a style and a script, added like so:

``` php
<?php
Brain\Assets::addFrontStyle( 'awesome_plugin_free_style' )
  ->src( $style_url );

Brain\Assets::addFrontScript( 'awesome_plugin_free_script' )
  ->src( $script_url );
```

Premium version can do:

``` php
<?php
Brain\Assets::addFrontStyle( 'awesome_plugin_premium_style' )
  ->src( $style_url )
  ->provide( [ 'awesome_plugin_free_style' ] );

Brain\Assets::addFrontScript( 'awesome_plugin_premium_script' )
  ->src( $script_url )
  ->provide( [ 'awesome_plugin_free_script' ] );
```

In this way, users of premium addon, instead of 4 http request will have only 2. Awesome, isn't it?

Sure, the owner of the site where plugin is installed can embed `'awesome_plugin_premium_script'` in a big concatenated file, together with other plugins scripts, but great majority of users will not do that, and halve HTTP requests for a plugin is a good thing, anyway.

In summary, **developers should use the "provide" feature to reduce HTTP requests of own plugins assets, but *never* use it to ship core assets**.


----------


##Removing assets

Sometimes one needs to remove assets. In Occipital you can remove only assets added using Occipital.

That is done via **`removeStyle`** and **`removeScript`** API methods.

Only argument accepted is the asset handle. Of course to remove an asset, one needs to be sure that the callback that adds it already ran. Unlike WordPress, Occipital provides a specific hook for the scope (or better, 4 hooks): `"brain_assets_remove"`that is fired in all "sides" (frontend, backend and login pages) and other three side-specific hooks. More info in the [Occipital hooks](#occipital-hooks) paragraph.


----------


##Get and set assets properties

Let's assume somewhere in a plugin code there's an asset added like so

``` php
<?php
Brain\Assets::addAdminScript( 'awesome_script', [ 'src' => $script_url ] );
```

Everywhere (same plugin, another plugin, theme) is possible to get the asset that previous line added and retrieve information about it and also modify it, if needed.

That is done with the 2 API methods: **`getStyle`** and **`getScript`**.

In following example I'll get the script added above and

 - I'll change its url
 - I'll add a condition in a way that the script will not be added on a page with slug 'not-here'

This is the Occipital code

``` php
<?php
$script = Brain\Assets::getScript( 'awesome_script' );
$script->setSrc( str_replace( "example.com", "foo.com", $script->getSrc() ) )
  ->setCondition( function( WP_Query $query ) {
    return ! $query->is_page( 'not-here' );
  });
```

Doing same thing using core would required:

``` php
<?php
add_action( 'wp_print_scripts', function() {
  wp_dequeue_script( 'awesome_script' );
  if ( is_page('not-here') ) {
    return;
  }
  global $wp_scripts;
  $script = $wp_scripts->registered['awesome_script'];
  $args = get_object_properties( $script );
  $in_footer = isset( $args['extra']['group'] ) && $args['extra']['group'];
  wp_deregister_script( 'awesome_script' );
  wp_enqueue_script(
    'awesome_script', $args['src'], $args['deps'], $args['ver'], $in_footer
  );
}, 1 );
```

Difference is not the number of lines of code, difference is also in

 - readability
 - the fact that to obtain same result in WordPress is needed to use global variables with undocumented properties, e.g. did you know that a script is printed in footer if the `extra['group']` argument is set to 1? (even if you knew that, sure you didn't learn it from documentation). Even the class of the object we access is named [`_WP_Dependency`](https://developer.wordpress.org/reference/classes/_wp_dependency/) suggesting it is a "private" class. On the contrary, in Occipital everything is done using an expressive, public and documented API.
 - the fact that to make it works in core we need to use a specific hook, where in Occipital there’s a lot of flexibility regarding *timing*.

Regarding Occipital API, in the example above there is a **`getSrc()`** method: it is only one of the getter available for assets objects, there is a getter for every setter, so we have:

 - `setHandle()` ---> `getHandle()`
 - `setSrc()`  ---> `getSrc()`
 - `setDeps()` ---> `getDeps()`
 - `setVer()` ---> `getVer()`
 - `setCondition()`---> `getCondition()`
 - `setProvided()` ---> `getProvided()`
 - `setMedia()` ---> `getMedia()` (only for styles)
 - `setAfter()` ---> `getAfter()` (only for styles)
 - `setFooter()` ---> `getFooter()` (only for scripts)
 - `setLocalizeData()` ---> `getLocalizeData()` (only for scripts)

In "[Fluent interface](#fluent-interface)" paragraph there are different names for setters, that's because all setters have a shortened alias without the leading `set` and with first letter lowercased. However, to use "full" setter names in fluent interface is perfectly fair.


----------


##Assets classes

In different parts of this page I refer to "asset class", and "asset object" in fact, in Occipital, every assets added is an object (just like in core, to be honest).

The default class for styles is `Brain\Occipital\Style`, for scripts is `Brain\Occipital\Script`.

I said "default" because is possible for a developer write a custom implementation of the 2 interfaces: `Brain\Occipital\StyleInterface` and  `Brain\Occipital\ScriptInterface`.

Both extends `Brain\Occipital\EnqueuableInterface` and all of them are pretty documented in code with phpDoc comments.


----------


###Get info for non-Occipital assets

As proven in previous paragraph, getting information for enqueued assets in WordPress is not very easy nor straightforward.
Occipital provides a way to create an Occipital asset object starting from any non-Occipital registered asset, in that way is possible to use Occipital getters to get information.

That is done instantiating an Occipital [asset object](#assets-classes) and calling the **`fillFromRegistered()`** method on it.

As example, somewhere there is a piece of code like the following:

``` php
<?php
wp_register_script(
  'foo',
  'http://example.com/path/to/script.js',
  array( 'jQuery' ),
  '20141008',
  TRUE
);

wp_localize_script(
  'foo',
  'FooData',
  array( 'foo'=>'bar', 'bar'=>'baz' )
);
```

Having Occipital installed we can:

``` php
<?php
$script = new Brain\Occipital\Script;
$script->fillFromRegistered( 'foo' );
$data = $script->getLocalizeData();
```

`$data` will be an **array** with a single element: a plain object (`stdClass`) with 2 properties:

 - `name` that will be equal to `"FooData"`
 - `data` that will be equal to `array( 'foo'=>'bar', 'bar'=>'baz' )`

Reason why it is an array is that `wp_localize_script` (just like `setLocalizeData` in Occipital) can be called more than once on scripts handle, in that case the array will contain more objects.

I can assure that doing same thing with core functions is **not** so easy: did you know that WP stores the localization data for scripts in **string** form (a new-line separated string, in case there were more than one `wp_localize_script` scripts)?

In current Occipital version **only getters can be used** (any setter will change properties on the created object but will not affect behavior of the asset enqueued).


----------


##Error handling

All the `add*` API methods return an asset object (see previous paragraph)  that can be used for all purposes: (debug, getting info, editing...).

Of course, all the "chainable" setter methods return the same instance (internally they returns `$this`).

Hower when something goes wrong, any method may return a [`WP_Error`](https://developer.wordpress.org/reference/classes/wp_error/) object.

You may reasonably think that when using [fluent interface](#fluent-interface) if a method at start or in the middle of the methods chain returns an error object, next method will cause a fatal error: well, that **not** true.

This little "magic" is done thanks to a custom Error class that extends `WP_Error` making it "chainable": everytime a non-existing method is called on it, an error message is added to object errors stack (`WP_Error` supports multiple errors) then the object return itself.

Thank to the fact that custom error class extends `WP_Error`, it can be checked via [`is_wp_error`](https://developer.wordpress.org/reference/functions/is_wp_error/) and is capable of run all its methods.


----------


##Occipital hooks

There are several custom action hooks fired by Occipital, they can be divided into 2 groups "ready hooks" and "Printing hook".


###Ready hooks

They are:

 - `"brain_assets_ready_front"`
 - `"brain_assets_ready_admin"`
 - `"brain_assets_ready_login"`
 - `"brain_assets_ready"`
 - `"brain_assets_remove_front"`
 - `"brain_assets_remove_admin"`
 - `"brain_assets_remove_login"`
 - `"brain_assets_remove"`
 - `"brain_assets_done"`

**First three** are specular to WordPress `"wp_enqueue_scripts"`, `"admin_enqueue_scripts"` and `"login_enqueue_scripts"`.
However, when using Occipital API is possible to add assets in any time that goes from `init` to `wp_print_styles` (when assets going to be printed) so there is no need to use one of this hooks, reason for their existence is that all of them (just like the generic **`"brain_assets_ready"`**, that runs just before any of the first three) pass to hooking callbacks the instance of assets container class `Brain\Occipital\Container` that can be used to add hooks without use the API and other (currently undocumented) advanced operations.

**`"brain_assets_remove"`** and the three  **`"brain_assets_remove_*"`** hooks, as guessable, can be used to remove added assets.
In fact, to remove an asset, we need to be sure that the function that adds it has been processed. In WordPress, usually, a safe place is `wp_print_styles`, Occipital provides a specific hooks for the scope. The generic one is available everywhere in addition there is one hook for any supported "side".

Last hook in the series, "brain_assets_done" is fired when the enqueuing process has been completed. It’s mostly used internally, but it is a safe place to get information about enqueued assets, as example assets whose condition callback have returned a false value have been discarded when that hook is fired.


###Printing hooks

They are:

 - `"brain_doing_style"`
 - `"brain_style_done"`
 - `"brain_doing_script"`
 - `"brain_script_done"`

**`"brain_doing_style"`** is fired immediately **before** a style `<link>` tag is printed to page, and **`"brain_style_done"`** is fired immediate **after** that.

**`"brain_doing_script"`** is fired immediately **before** a script `<script>` tag is printed to page, and **`"brain_script_done"`** is fired immediate **after** that.


----------


##License

Occipital own code is licensed under **GPLv2+**. Through Composer, it installs code from:

- [Composer](https://getcomposer.org/) (MIT)
- [Brain](http://giuseppe-mazzapica.github.io/Brain/) (GPLv2+)
- [Pimple](http://pimple.sensiolabs.org/) (MIT) - required by Brain -
- [PHPUnit](https://phpunit.de/) (BSD-3-Clause) - only dev install -
- [Mockery](https://github.com/padraic/mockery) (BSD-3-Clause) - only dev install -
- [WP_Mock](https://github.com/10up/wp_mock) (GPLv2+) - only dev install -
