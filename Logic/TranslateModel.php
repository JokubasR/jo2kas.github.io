<?php
/**
 * @author   Jokūbas Ramanauskas
 * @since    4/2/14
 */

namespace Logic;


class TranslateModel {

    const MESSAGE_FILE = '../public/messages.lt.json';

    /**
     * Translates by keyword
     *
     * @param $keyword
     *
     * @return mixed
     */
    public static function translate($keyword)
    {
        $file = file_get_contents(VIEW_DIR . self::MESSAGE_FILE);

        $data = json_decode($file, true);

        return array_key_exists($keyword, $data)
                ? $data[$keyword]
                : $keyword;
    }

}