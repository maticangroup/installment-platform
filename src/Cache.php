<?php
/**
 * Created by PhpStorm.
 * User: hossein
 * Date: 06/08/19
 * Time: 13:53
 */

namespace App;


use Matican\Core\Transaction\Request;
use Matican\ResponseStatus;
use Symfony\Component\Filesystem\Filesystem;

class Cache
{
    public static function is_cached($server, $entity, $action): bool
    {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists(self::action_cache_file($server, $entity, $action))) {
            return true;
        } else {
            return false;
        }
    }

    private static function cache_directory()
    {
        return Params::project_root() . Params::CACHE_DIRECTORY;
    }

    public static function action_cache_file($server, $entity, $action)
    {
        return self::cache_directory() . $server . DIRECTORY_SEPARATOR . $entity . DIRECTORY_SEPARATOR . $action . '.txt';
    }

    private static function record_cache_file($server, $entity, $action, $id)
    {
        $var = self::cache_directory() . $server . DIRECTORY_SEPARATOR . $entity . DIRECTORY_SEPARATOR . $action . DIRECTORY_SEPARATOR . $id . '.txt';
        return $var;

    }

    public static function action_cache_directory($server, $entity)
    {
        return self::cache_directory() . $server . DIRECTORY_SEPARATOR . $entity . DIRECTORY_SEPARATOR;
    }

    public static function record_cache_directory($server, $entity, $action)
    {
        return self::cache_directory() . $server . DIRECTORY_SEPARATOR . $entity . DIRECTORY_SEPARATOR . $action . DIRECTORY_SEPARATOR;
    }

    public static function get_cached($server, $entity, $action)
    {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists(self::action_cache_file($server, $entity, $action))) {
            $fileContent = file_get_contents(self::action_cache_file($server, $entity, $action));
            return json_decode($fileContent, true);
        }
        return null;
    }

    public static function get_cached_record($server, $entity, $action, $id)
    {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists(self::record_cache_file($server, $entity, $action, $id))) {
            $fileContent = file_get_contents(self::record_cache_file($server, $entity, $action, $id));
            return json_decode($fileContent, true);
        }
        return null;
    }

    public static function cache_action($server, $entity, $action)
    {
        $request = new Request($server, $entity, $action);
        $response = $request->send();
        if ($response->getStatus() === ResponseStatus::successful) {
            $content = json_encode($response->getContent());
        } else {
            return false;
        }

        $fileSystem = new Filesystem();
        if (!$fileSystem->exists(self::action_cache_file($server, $entity, $action))) {
            $fileSystem->mkdir(self::action_cache_directory($server, $entity));
            $fileSystem->touch(self::action_cache_file($server, $entity, $action));

        } else {
            $fileSystem->remove(self::action_cache_file($server, $entity, $action));
            $fileSystem->touch(self::action_cache_file($server, $entity, $action));
        }
        $fileSystem->appendToFile(self::action_cache_file($server, $entity, $action), $content);
        return true;
    }


    public static function is_record_cached($server, $entity, $action, $id)
    {


        $fileSystem = new Filesystem();
        if ($fileSystem->exists(self::record_cache_file($server, $entity, $action, $id))) {
            return true;
        } else {
            return false;
        }
//        $fileSystem = new Filesystem();
//        if (!$fileSystem->exists(self::record_cache_file($server, $entity, $action, $id))) {
//            $fileSystem->mkdir(self::action_cache_directory($server, $entity, $action));
//            $fileSystem->touch(self::record_cache_file($server, $entity, $action, $id));
//        } else {
//            $fileSystem->remove(self::record_cache_file($server, $entity, $action, $id));
//            $fileSystem->touch(self::record_cache_file($server, $entity, $action, $id));
//        }
    }

    public static function cache_record($server, $entity, $action, $id, $record)
    {
        $content = json_encode($record);
        $fileSystem = new Filesystem();
        if (!$fileSystem->exists(self::record_cache_file($server, $entity, $action, $id))) {
            $fileSystem->mkdir(self::record_cache_directory($server, $entity, $action));
            $fileSystem->touch(self::record_cache_file($server, $entity, $action, $id));
        } else {
            $fileSystem->remove(self::record_cache_file($server, $entity, $action, $id));
            $fileSystem->touch(self::record_cache_file($server, $entity, $action, $id));
        }
        $fileSystem->appendToFile(self::record_cache_file($server, $entity, $action, $id), $content);
        return true;
    }
}