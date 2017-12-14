<?php
namespace traq\plugins;

use avalon\http\Router;
use avalon\http\Request;
use avalon\core\Kernel as Avalon;
use avalon\Autoloader;
use \FishHook;
use \HTML;

class Markdown extends \traq\libraries\Plugin
{
    protected static $markdown = null;

    protected static $info = array(
        'name'    => 'Markdown Improved',
        'version' => '1.0',
        'author'  => 'alex'
    );

    public static function init()
    {
        FishHook::add('template:layouts/global/head', array(get_called_class(), 'head'));
        FishHook::add('function:format_text', array(get_called_class(), 'format_text'));
    }
    
    public static function head()
    {
        echo HTML::js_inc(Request::base('/js.php?plugin=markdown&js=all'));
        echo HTML::css_link(Request::base('/css.php?plugin=markdown&css=all'));

        // Get the locale strings and set the editor strings
        if ($strings = Avalon::app()->locale->get_strings('editor')) {
            echo '<script>likeABoss.strings = '.json_encode($strings).'</script>';
        }
    }

    public static function format_text(&$text, $strip_html)
    {
        if (!static::$markdown) {
            require_once __DIR__ . '/Michelf/MarkdownInterface.php';
            require_once __DIR__ . '/Michelf/Markdown.php';
            require_once __DIR__ . '/Michelf/MarkdownExtra.php';

            static::$markdown = new \Michelf\MarkdownExtra;
            static::$markdown->no_markup = true;
			// Prism compatibility
            static::$markdown->code_class_prefix = 'language-';
        }

        if ($strip_html) {
            $text = htmlspecialchars_decode($text);
        }

        $text = static::$markdown->transform($text);
    }
}
