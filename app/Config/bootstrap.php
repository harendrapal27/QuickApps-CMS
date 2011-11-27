<?php
/**
 * This file is loaded automatically by the app/webroot/index.php file after core.php
 *
 * This file should load/create any application wide configuration settings, such as 
 * Caching, Logging, loading additional configuration files.
 *
 * You should also use this file to include any files that provide global functions/constants
 * that your application uses.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.config
 * @since         CakePHP(tm) v 0.10.8.2117
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

// Setup a 'default' cache configuration for use in the application.
Cache::config('default', array('engine' => 'File'));

/**
 * The settings below can be used to set additional paths to models, views and controllers.
 *
 * App::build(array(
 *     'plugins' => array('/full/path/to/plugins/', '/next/full/path/to/plugins/'),
 *     'models' =>  array('/full/path/to/models/', '/next/full/path/to/models/'),
 *     'views' => array('/full/path/to/views/', '/next/full/path/to/views/'),
 *     'controllers' => array('/full/path/to/controllers/', '/next/full/path/to/controllers/'),
 *     'datasources' => array('/full/path/to/datasources/', '/next/full/path/to/datasources/'),
 *     'behaviors' => array('/full/path/to/behaviors/', '/next/full/path/to/behaviors/'),
 *     'components' => array('/full/path/to/components/', '/next/full/path/to/components/'),
 *     'helpers' => array('/full/path/to/helpers/', '/next/full/path/to/helpers/'),
 *     'vendors' => array('/full/path/to/vendors/', '/next/full/path/to/vendors/'),
 *     'shells' => array('/full/path/to/shells/', '/next/full/path/to/shells/'),
 *     'locales' => array('/full/path/to/locale/', '/next/full/path/to/locale/')
 * ));
 *
 */

/**
 * As of 1.3, additional rules for the inflector are added below
 *
 * Inflector::rules('singular', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 * Inflector::rules('plural', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 *
 */
    define('THEMES', ROOT  . DS . 'Themes' . DS . 'Themed' . DS);
    App::uses('Spyc', 'vendors');
    App::uses('Folder', 'Utility');

/**
 * Load themes as plugin
 *
 */
    $__plugin_paths = Cache::read('plugin_paths');

    if (!$__plugin_paths) {
        $folder = new Folder;
        $folder->path = THEMES;

        $__themes = $folder->read();
        $__themes = $__themes[0];

        foreach ($__themes as $__tname) {
            $__plugin_paths[] = THEMES . $__tname . DS . 'app' . DS;
        }

        $__plugin_paths[] = ROOT . DS . 'Modules' . DS;

        App::build(array('plugins' => $__plugin_paths));

        $plugins = App::objects('plugins', null, false);

        foreach ($plugins as $plugin) {
            CakePlugin::load($plugin, array('bootstrap' => true, 'routes' => true));

            $__ppath = CakePlugin::path($plugin);
            $__ppath = str_replace(DS . $plugin . DS, DS . $plugin . DS, $__ppath);

            if (file_exists($__ppath . 'Fields' . DS)) {
                $__plugin_paths[] = $__ppath . 'Fields' . DS;
            }
        }

        Cache::write('plugin_paths', $__plugin_paths);
        unset($__themes, $__tname, $folder, $__ppath);
    }

    App::build(
        array(
            'views' => ROOT  . DS . 'Themes' . DS, 
            'plugins' => $__plugin_paths,
            'Model/Behavior' => ROOT . DS . 'Hooks' . DS . 'Behavior' . DS,
            'View/Helper' => ROOT . DS . 'Hooks' . DS . 'Helper' . DS,
            'Controller/Component' => ROOT . DS . 'Hooks' . DS . 'Component' . DS
        )
    );

    $plugins = App::objects('plugins', null, false);

    foreach($plugins as $plugin) {
        if (!CakePlugin::loaded($plugin)) {
            CakePlugin::load($plugin, array('bootstrap' => true, 'routes' => true) );
        }
    }

    unset($__plugin_paths, $plugins, $plugin);

/**
 * Return only the methods for the object you indicate. It will strip out the inherited methods
 * web: http://php.net/manual/es/function.get-class-methods.php
 * author: onesimus at cox dot net 
 * date: 19-Jun-2004 09:32
 */
    function get_this_class_methods($class){
        $array1 = get_class_methods($class);

        if ($parent_class = get_parent_class($class)) {
            $array2 = get_class_methods($parent_class);
            $array3 = array_diff($array1, $array2);
        } else {
            $array3 = $array1;
        }

        return($array3);
    }

/**
 * Translation function, domain search order:
 * 1º Current plugin
 * 2º Default
 * 3º Translatable entries cache
 *
 * @param string $singular String to translate
 * @return string the translated string
 */
    function __t($singular, $args = null) {
        if (!$singular) {
            return;
        }

        App::uses('I18n', 'I18n');
        $route = Router::getParams();
        $translated = I18n::translate($singular, null, $route['plugin']); # 1º look in plugin 

        if ($translated === $singular) { # 2º look in default
            $translated = I18n::translate($singular, null, 'default');
        }

        if ($translated === $singular) { # 3º look in transtalion db-cache
            $cache = Cache::read(md5($singular) . '_' . Configure::read('Config.language'), 'i18n');
            $translated = $cache ? $cache: $singular;
        }

        if ($args === null) {
            return $translated;
        } elseif (!is_array($args)) {
            $args = array_slice(func_get_args(), 1);
        }

        return vsprintf($translated, $args);    
    }

/**
 * Create Unique Arrays using an md5 hash
 *
 * @param array $array
 * @return array
 */
    function arrayUnique($array, $preserveKeys = false) {
        $arrayRewrite = array();
        $arrayHashes = array();

        foreach ($array as $key => $item) {
            $hash = md5(serialize($item));

            if (!isset($arrayHashes[$hash])) {
                $arrayHashes[$hash] = $hash;

                if ($preserveKeys) {
                    $arrayRewrite[$key] = $item;
                } else {
                    $arrayRewrite[] = $item;
                }
            }
        }

        return $arrayRewrite;
    }

/**
 * replace the first ocurrence only
 *
 * @param string $str_pattern what to find for
 * @param string $str_replacement the replacement for $str_pattern
 * @param string $string the original to find and replace
 * @return string
 */    
    function str_replace_once($str_pattern, $str_replacement, $string) {
        if (strpos($string, $str_pattern) !== false) {
            $occurrence = strpos($string, $str_pattern);
            return substr_replace($string, $str_replacement, strpos($string, $str_pattern), strlen($str_pattern));
        }

        return $string;
    }

    include_once ROOT . DS . 'Config' . DS . 'bootstrap.php';