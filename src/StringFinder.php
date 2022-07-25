<?php

namespace GeneroWP\TextReplacements;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use CallbackFilterIterator;

class StringFinder
{
    /** @var string */
    const REGEX_ARG = '[\s]*[\'\"](.*?)[\'\"][\s]*';

    /** @var string[] */
    protected array $file_extensions = [
        'php',
        'inc',
        'twig',
    ];

    /** @var string[] */
    protected array $ignore_paths = [
        'node_modules/',
        'vendor/',
    ];

    /** @var string[] */
    protected $domains = [];

    public function __construct()
    {
    }

    /**
     * @return string[]
     */
    public function getFileExtensions(): array
    {
        return $this->file_extensions;
    }

    /**
     * @param string[] $extensions
     */
    public function setFileExtensions(array $extensions): void
    {
        $this->file_extensions = $extensions;
    }

    /**
     * @param string[] $domains
     */
    public function setDomains(array $domains): void
    {
        $this->domains = $domains;
    }

    /**
     * Find and register all strings in the theme.
     *
     * @param string[] $dirs
     * @return array<string,array{search:string,replace:?string,domain:string}>
     */
    public function scan(array $dirs): array
    {
        $files = [];
        foreach ($dirs as $dir) {
            if (is_file($dir)) {
                $files = array_merge($files, [$dir]);
                continue;
            }
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
        $result = [];
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
     * @return array<string,array{search:string,replace:?string,domain:string}>
     */
    public function getStrings($files): array
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
                $id = $domain . ':' . esc_attr($string);

                if (!in_array($domain, $this->domains)) {
                    continue;
                }
                $strings[$id] = [
                    'search' => $string,
                    'replace' => null,
                    'domain' => $domain,
                ];
            }
        }
        return $strings;
    }

    public function filterPaths(string $file): bool
    {
        foreach ($this->ignore_paths as $path) {
            if (strpos($file, $path) !== false) {
                return false;
            }
        }
        return true;
    }
}
