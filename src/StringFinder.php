<?php

namespace GeneroWP\TextReplacements;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use CallbackFilterIterator;

class StringFinder
{
    /** @var string */
    const REGEX_ARG = '[\s]*[\'\"](.*?)[\'\"][\s]*';

    /** @var array */
    protected $file_extensions = [
        'php',
        'inc',
        'twig',
    ];

    protected $ignore_paths = [
        'node_modules/',
        'vendor/',
    ];

    /** @var array */
    protected $domains = [];

    public function __construct()
    {
    }

    public function getFileExtensions()
    {
        return $this->file_extensions;
    }

    public function setFileExtensions(array $extensions)
    {
        $this->file_extensions = $extensions;
    }

    public function setDomains(array $domains)
    {
        $this->domains = $domains;
    }

    /**
     * Find and register all strings in the theme.
     *
     * @param array $dirs
     */
    public function scan(array $dirs)
    {
        $files = [];
        foreach ($dirs as $dir) {
            $files = array_merge($files, $this->getFilesFromDir($dir));
        }

        $strings = $this->getStrings($files);
        return $strings;
    }

    /**
     * Get all matching files from directory.
     *
     * @param  string $dir
     * @return string[]
     */
    public function getFilesFromDir($dir)
    {
        $di = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($di);
        $iterator = new CallbackFilterIterator($iterator, [$this, 'filterPaths']);
        $results = [];
        foreach ($iterator as $file) {
            if (!in_array(pathinfo($file, PATHINFO_EXTENSION), $this->file_extensions)) {
                continue;
            }
            $result[] = $file;
        }
        return $result;
    }

    /**
     * Get all translatable strings from the list of files.
     *
     * @param  string[] $files
     * @return array
     */
    public function getStrings($files)
    {
        $strings = [];
        foreach ($files as $file) {
            $regex = '/__\(' . self::REGEX_ARG . '(?:,' . self::REGEX_ARG . ')?\)/s';
            preg_match_all($regex, file_get_contents($file), $matches);
            if (empty($matches[1])) {
                continue;
            }

            foreach ($matches[1] as $idx => $string) {
                $domain = !empty($matches[2][$idx]) ? $matches[2][$idx] : '';

                if (!in_array($domain, $this->domains)) {
                    continue;
                }

                $strings["$domain:$string"] = [
                    'search' => $string,
                    'replace' => null,
                    'domain' => $domain,
                ];
            }
        }
        return $strings;
    }

    public function filterPaths($file)
    {
        foreach ($this->ignore_paths as $path) {
            if (strpos($file, $path) === true) {
                return false;
            }
        }
        return true;
    }
}
