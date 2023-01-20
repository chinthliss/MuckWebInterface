<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class Lexicon
{
    /**
     * @var null|array[string]
     */
    private static $dictionary = null;

    public static function loadDictionaryIfRequired()
    {
        if (!is_null(self::$dictionary)) return;
        self::$dictionary = config('lexicon');
        //Special cases taken from other parts of the config, so they can be passed to the JS side easily
        self::$dictionary['app_name'] = config('app.name');
        if (!self::$dictionary['app_name']) Log::warning("Lexicon couldn't find something for app_name.");
        self::$dictionary['game_name'] = config('muck.name');
        if (!self::$dictionary['game_name']) Log::warning("Lexicon couldn't find something for game_name.");
        self::$dictionary['game_code'] = config('muck.code');
        if (!self::$dictionary['game_code']) Log::warning("Lexicon couldn't find something for game_code.");
    }
    /**
     * Translates a word into whatever this particular game uses
     * @param string $word
     * @return string $translatedWord
     */
    public static function get(string $word) : string
    {
        self::loadDictionaryIfRequired();
        if (array_key_exists($word, self::$dictionary)) return self::$dictionary[$word];
        Log::debug("Lexicon - An attempt was made to translate an unrecognized phrase/word: {$word}");
        return $word;
    }

    /**
     * Function to export, so it can be loaded into JS
     * @return array
     */
    public static function toArray() : array
    {
        self::loadDictionaryIfRequired();
        return self::$dictionary;
    }
}
