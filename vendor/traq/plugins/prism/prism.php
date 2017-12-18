<?php
namespace traq\plugins;

use avalon\http\Router;
use avalon\http\Request;
use avalon\Autoloader;
use \FishHook;
use \HTML;

class Prism extends \traq\libraries\Plugin
{
    protected static $info = array(
        'name'    => 'Prism.js',
        'version' => '1.0',
        'author'  => 'alex'
    );

    /**
     * Handles the startup of the plugin.
     */
    public static function init()
    {
        FishHook::add('template:layouts/global/head', array(static::class, 'insert'));
    }

    public static function insert()
    {
		static $inserted = false;

		if ($inserted == false) {
            echo HTML::js_inc(Request::base('/js.php?plugin=prism&js=all'));
            echo HTML::css_link(Request::base('/css.php?plugin=prism&css=all'));
			$inserted = true;
		}
    }
}
