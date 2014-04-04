<?php
/**
 * @author   Jokūbas Ramanauskas
 * @since    3/30/14
 */


    define('VIEW_DIR', __DIR__ . '/View/');


    function __autoload($class){
        $class = str_replace('\\', '/', $class);
        $filename = __DIR__. '/' . $class . ".php";
        include_once($filename);
    }

    function d($data){
        echo "<pre>";
        var_dump($data);
        echo "</pre>";
    }

    function dd($data){
        d($data);
        die();
    }

    /**
     * Translates by keyword
     * @param $message
     *
     * @return mixed
     */
    function translate($message){
        return \Logic\TranslateModel::translate($message);
    }

    /*
     * Router
     */

    $indexController = new Controller\IndexController();

    $query = $_SERVER['REQUEST_URI'];

    /*
     * Mini router
     */

    try{
        switch(true){
            case strpos($query, 'points'):
                $indexController->pointsAction();
                break;
            case strpos($query, 'best-team'):
                $indexController->bestTeamAction();
                break;
            default:
                $indexController->defaultAction();
        }
    } catch (Exception $exception) {
        die('Something went bad!');
    }

