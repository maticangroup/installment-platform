<?php
/**
 * Created by PhpStorm.
 * User: hossein
 * Date: 22/07/19
 * Time: 12:10
 */

namespace App;


class Params
{
    const REPOSITORY_ITEM_CACHE_DIRECTORY = 'REPOSITORY_ITEM_CACHE_DIRECTORY';
    const CACHE_DIRECTORY = 'cache/';
    const REPOSITORY_ITEM_CACHE_STORAGE = 'repository/item/';
    const REPOSITORY_ITEM_ALL_CACHE_FILE = 'repository/item/all.txt';


    public static function get($param)
    {


        if ($param == 'PERMISSION_CACHE_FILE') {
            $public = DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
            $serverRoot = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR;
            $cacheDir = 'cache' . DIRECTORY_SEPARATOR . 'Permissions' . DIRECTORY_SEPARATOR;
            $cacheFile = 'rolePermissions.txt';
            return str_replace($public, '', $serverRoot) . DIRECTORY_SEPARATOR . $cacheDir . $cacheFile;
        }


        if ($param == 'PERMISSION_CACHE_DIR') {
            $public = DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
            $serverRoot = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR;
            $cacheDir = 'cache' . DIRECTORY_SEPARATOR . 'Permissions' . DIRECTORY_SEPARATOR;
            return str_replace($public, '', $serverRoot) . DIRECTORY_SEPARATOR . $cacheDir;
        }


        if ($param == 'APPLICATION_DOMAIN') {
//            return 'test_domain';
            return $_SERVER['HTTP_HOST'];
        }
        if ($param === self::REPOSITORY_ITEM_CACHE_DIRECTORY) {

            $projectRoot = self::project_root();
            $cacheDirectory = $projectRoot . self::CACHE_DIRECTORY . self::REPOSITORY_ITEM_CACHE_STORAGE;
            return $cacheDirectory;

        }
    }

    public static function loginPageUrl()
    {
        return "/login";
    }

    public static function project_root()
    {
        $public = DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
        $serverRoot = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR;
        $projectRoot = str_replace($public, '', $serverRoot) . '/';
        return $projectRoot;
    }


}