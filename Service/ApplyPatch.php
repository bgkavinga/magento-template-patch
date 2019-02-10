<?php
/**
 * Netstarter Pty Ltd.
 * @category    m219-loc.ns-staging.com.au
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2019 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */

namespace Netstarter\Deploy\Service;

use Magento\Framework\Filesystem\Io\File;

class ApplyPatch
{
    /** @var string */
    const PATCHED_FILE_PREFIX = '-patched';

    /** @var string */
    const FILE_EXTENSION = '.phtml';

    /**
     * ApplyPatch -> file
     * @var File
     */
    private $file;

    /**
     * ApplyPatch -> parentFile
     * @var string
     */
    private $parentFile;

    /**
     * ApplyPatch -> findDirPath
     * @var
     */
    private $findDirPath;

    /**
     * ApplyPatch -> replaceDirPath
     * @var
     */
    private $replaceDirPath;

    /**
     * ApplyPatch -> parentDirectory
     * @var
     */
    private $parentDirectory;

    /**
     * ApplyPatch constructor.
     * @param File $file
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * Function applyPatch
     * @throws \Exception
     */
    public function applyPatch()
    {
        $content = $this->file->read($this->parentFile);
        $this->file->open(['path' => $this->findDirPath]);
        $patchFiles = $this->file->ls(File::GREP_FILES);
        $this->file->close();
        $count = 0;
        $errorCount = 0;
        $patchCount = count($patchFiles);

        usort($patchFiles, [$this, 'sortFiles']);

        foreach ($patchFiles as $patchFile) {
            $count++;
            $replacements = null;
            $findFilePath = $this->findDirPath . $this->file->dirsep() . $patchFile['text'];
            $replaceFilePath = str_replace(GeneratePatch::FIND_DIR, GeneratePatch::REPLACE_DIR, $findFilePath);
            $find = $this->file->read($findFilePath);

            $replace = $this->file->read($replaceFilePath);
            $content = $this->strReplace($find, $replace, $content, $replacements);
            // try removing extra line feeds. If it is the end of the file extra line feed will not be available
            if ($replacements == 0) {
                $find = substr_replace($find, "", -1);
                $replace = substr_replace($replace, "", -1);
                $content = $this->strReplace($find, $replace, $content, $replacements);
            }
            if ($replacements == 0 || $replacements > 1) {
                echo '=========== REPLACE COUNT (' . $replacements . ') ===========' . PHP_EOL;
                echo $findFilePath . PHP_EOL;
                echo $replaceFilePath . PHP_EOL;
                echo '======================' . PHP_EOL;
                $errorCount++;
            }
        }

//        echo "Failures " . $errorCount . PHP_EOL;
//        echo "Successes " . ($count - $errorCount) . PHP_EOL;
//        echo "Processed " . $count . PHP_EOL;

        // find template file name to write
        $fileInfo = $this->file->getPathInfo($this->parentDirectory);
        $templateFile = $fileInfo['dirname'] . $this->file->dirsep() . $fileInfo['basename'] . self::PATCHED_FILE_PREFIX . self::FILE_EXTENSION;
        $this->file->write($templateFile, $content);
    }

    private function strReplace($search, $replace, $subject, &$replacements)
    {

        return str_replace($search, $replace, $subject, $replacements);
        $pos = strrpos($subject, $search);

        if ($pos !== false) {
            $bottomHalf = substr($subject, $pos);
            $subject = str_replace($search, $replace, $subject, $replacements);
//            $subject = substr_replace($subject, $replace, $pos);
        }

        return $subject;
    }

    /**
     * Function sortFiles
     * @param $f1
     * @param $f2
     * @return bool
     */
    public function sortFiles($f1, $f2)
    {
        $t1 = $f1['text'];
        $t2 = $f2['text'];
        // find numbers in file name for sorting
        preg_match_all('!\d+!', $t1, $matches1);
        preg_match_all('!\d+!', $t2, $matches2);
        if (isset($matches1[0][0])) {
            $t1 = $matches1[0][0];
        }
        if (isset($matches2[0][0])) {
            $t2 = $matches2[0][0];
        }

        return $t1 > $t2;
    }

    /**
     * @param string $parentFile
     * @return ApplyPatch
     */
    public function setParentFile($parentFile)
    {
        $this->parentFile = $parentFile;
        return $this;
    }

    /**
     * @param mixed $findDirPath
     * @return ApplyPatch
     */
    public function setFindDirPath($findDirPath)
    {
        $this->findDirPath = $findDirPath;
        $this->parentDirectory = $this->file->dirname($this->findDirPath);
        $this->replaceDirPath = $this->parentDirectory . $this->file->dirsep() . GeneratePatch::REPLACE_DIR;
        return $this;
    }


}