Integrate Occipital in a theme
========================

###A step-by-step guide

##0 - Readme first

This guide is for those who wants to integrate Occipital in an average WordPress theme, that usually are not Composer enabled. If your theme has already Composer support (you should see a `composer.json` file in the root folder of the theme) you need to adapt this guide.

Note that to optimize HTTP requests this guide suggests to concatenate and compress your styles and scripts. This guide does **not** explain how to do that: there are a lot of tools you can use, and there are dozen of tutorials and articles on the topic out there.

Moreover, I **strongly** suggest you to test this guide in a local copy of your site, and not directly in production. I'll assume for the guide that you are following this suggestion.

If you are able to run a local copy of your site and to concatenate your assets in a single compressed file, then you will be definitely able to follow steps in this guide: most complex of them implies to copy and paste few lines of PHP into your `functions.php.`

If the yours is a theme you didn't write by yourself, I suggest you to create a child theme and use `functions.php` from child theme (create it, if needed). In that case, when you’ll read “theme folder” or “theme root” refer to child theme folder.

The guide can be used to integrate Occipital in plugins, just use the main plugin file where in the guide refers to functions.php. If you are developing a plugin to be shared or sold, please read the repo README, paying specia attention to the [“Big warning for developers”](/README.md#big-warning-for-developers) section.


##1 - Ingredients

First of all you need to assure that your system has all the requirement of Occipital.

 - PHP 5.4+
 - WordPress 3.9+
 - Composer (this is needed only in local site, to install Occipital. There is no need to have it in remote
   server)

If you are using [Vagrant](https://www.vagrantup.com/) with one of the WordPress-oriented configuration like [Varying Vagrant Vagrants](https://github.com/Varying-Vagrant-Vagrants/VVV) (or similar), you already have all the things you need, and you can skip to step 2.

### 1.1 Check PHP version

If you don't know which PHP version is installed in your system, open your console and type:

``` bash
php -v
```

Output of the command should start with something like `PHP X.X.X` where the "X" actually are numbers. If first number is 5 and second is >= 4 you version is fine.

Note that PHP version must be 5.4+ also in your production server, if you don't know which version is installed in your hosting, go check hosting details, and maybe ask support.

Consider that:

 - PHP 5.3 and older are not maintained anymore, so even no security updates will be released for those versions
 - newer versions of PHP are faster than older, so if your hosting still stuck with 5.3 or older, consider to switch hosting: the fact that you can't install Occipital is last of your problems if you have a so-old PHP version.


###1.2 WordPress version

You should always install last version of WordPress. Log in into WordPress backend and look for any upgrade available. If needed, run the update.


###1.3 Composer

If in your local system is not present Composer, you need it to install Occipital. Please note that, unlike for PHP version, you need composer only locally, having it on remote server is not needed at all.

If you need to install Composer just follow the instruction on their site. (for [*nix,](https://getcomposer.org/doc/00-intro.md#installation-nix) for [Windows](https://getcomposer.org/doc/00-intro.md#installation-windows)).

Once you successfully installed Composer you are ready to go on.


##2 - Install Occipital


### 2.1 - prepare `composer.json`

Open an editor of choice, create a new file and put there:

``` js
{
  "require": {
    "php": ">=5.4",
    "brain/occipital": "dev-master"
  }
}
```

This is the absolutely basic content, you can insert a lot of other informations, check [documentation](https://getcomposer.org/doc/01-basic-usage.md#composer-json-project-setup).

Save this file as "**`composer.json`**" in the theme root folder.


### 2.2 - Install

Open your console, navigate to the theme folder and type

``` bash
composer install --no-dev
```

`--no-dev` is important if you want to use Occipital in production sites, without that your page loading time will increase substantially.

Now composer should start to download the package and prepare the autoload files, be patient a few moments. When Composer has done its work, Occipital is installed.

You should see in your theme root a folder named "`vendor`"  and inside it a file named `autoload.php` and three subfolders, one of them named "`brain`".


## 3.  - Integrate in theme

Now we need to make use of Occipital. Open theme `functions.php` and somewhere (position doesn't matter) put:

``` php
<?php
if ( file_exists( dirname( __FILE__ ) . 'vendor/autoload.php' ) ) {
  require dirname( __FILE__ ) . 'vendor/autoload.php';
  if ( class_exists( 'Brain\Occipital' ) ) \Brain\Occipital::boot();
}
```

That’s all, Occipital is now integrated in your theme, however it does nothing at the moment, because you didn’t say it what it should do :)


## 4. - Optimize HTTP requests


###4.1 - Know your enemies

First of all you need to know which styles and script you should compress, and where to find them.

If you already know that or you know how to do, skip to 4.2.

Open your `functions.php` and put somewhere:

``` php
<?php
// DON'T DO THIS IN PRODUCTION!
add_action( 'wp_print_styles', function() {
  if ( is_admin() ) return;
  global $wp_styles, $wp_scripts;
  echo '<table border="1px">';
  echo '<tr><th colspan="2">Styles</th><tr>';
  echo '<tr><th>Handle</th><th>Url</th><tr>';
  if ( $wp_styles instanceof \WP_Styles) {
    foreach( $wp_styles->queue as $id ) {
      printf(
        '<tr><td>%s</td><td>%s</td></th>',
        $id,
        $wp_styles->registered[$id]->src
    	 );
    }
  }
  echo '<tr><th colspan="2">Scripts</th><tr>';  
  echo '<tr><th>Handle</th><th>Url</th><tr>';  
  if ( $wp_scripts instanceof \WP_Scripts) {
    foreach( $wp_scripts->queue as $id ) {
      printf(
        '<tr><td>%s</td><td>%s</td></th>',
        $id,
        $wp_scripts->registered[$id]->src
      );
    }
  }
  die('</table>');
}, 99 );
```

Now open your local site url. Instead of see your site home page, you should see a big table, showing all the styles and the scripts that are enqueued in that page of your site. Take note.

Repeat the operation visiting different pages and archives url.

Once you can't access to site navigation, you may login in backend to get proper url for your pages.

If you have plugins that act on specific pages or posts (e.g. with shortcodes or widgets) look at those pages and posts urls.

When finished, completely **delete** code snippet above from `functions.php`.


###4.2 - Fight our enemies

In previous step you should have built a list of all assets your site uses.

Now you should choose which merge and compress.

My suggestion is:

 - skip assets with external urls, like fonts from Google, etc..
 - skip admin scripts and styles
 - include plugins and theme scripts and styles

Take note of all the handles of the scripts and the styles you are going to merge.

Once you know the url you where to find them, take a copy, **concatenate and minify them**. As said in introduction I'll not explain here how to do. There are tons of tutorials out there on this topic.

I'll just assume in next step that  in root of your theme you have built a file named `"my-styles.css"` with concatenated styles and another named `"my-scripts.js"` with concatenated scripts.

If names of url of your file are different just edit code in next step accordingly.

###4.3 - Defeat your enemies

So, you have a the 2 "big" files, one for scripts and one for styles, and you also have a list of all the handles of styles and scripts you merged.

Open again your `functions.php` and type somewhere:

``` php
<?php
add_action( 'brain_loaded', function() {

  $uri = get_stylesheet_directory_uri();
  $path = get_stylesheet_directory();

  \Brain\Assets::addFrontStyle( 'optimized-styles' )
    // ensure the url is correct
    ->src( $uri . '/my-styles.css' )
    // replace following with real ids of the style you merged
    ->provide([ 'style1', 'style2', 'style3', 'style4' ])
    // ensure the path is correct
    ->ver( @filemtime( $path . '/my-styles.css' ) ? : NULL );

  \Brain\Assets::addFrontScript( 'optimized-scripts' )
    // ensure the url is correct
    ->src( $uri . '/my-scripts.css' )
    // replace following with real ids of the scripts you merged
    ->provide([ 'script1', 'script2', 'script3', 'script4' ])
    // ensure the path is correct
    ->ver( @filemtime( $path . '/my-scripts.css' ) ? : NULL )
    ->footer( TRUE );
});
```

Ok, we are done.


###Enjoy the winning

Open your site again, refresh the page, look at source and see how many HTTP request you saved :)
