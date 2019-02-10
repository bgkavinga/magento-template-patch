<?php
/**
 * Netstarter Pty Ltd.
 * @category    m219-loc.ns-staging.com.au
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2019 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */

namespace Netstarter\Deploy\Service;


use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Io\File;
use Netstarter\Deploy\Diff\SequenceMatcher;
use Netstarter\Deploy\Diff\Builder;


class GeneratePatch
{
    /** @var int identical content */
    const UNMODIFIED = 0;
    /** @var int deleted */
    const DELETED = 1;
    /** @var int inserted */
    const INSERTED = 2;
    /** @var string */
    const FIND_DIR = '__find';
    /** @var string */
    const REPLACE_DIR = '__replace';

    /**
     * GeneratePatch -> fileNameTemplate
     * @var string
     */
    private $fileNameTemplate = '%s%sgenerated_%s.txt';

    /**
     * GeneratePatch -> parentTemplate
     * @var string
     */
    private $parentTemplate;

    /**
     * GeneratePatch -> childTemplate
     * @var string
     */
    private $childTemplate;
    /**
     * GeneratePatch -> file
     * @var File
     */
    private $file;

    /**
     * GeneratePatch -> childTemplateContent
     * @var string
     */
    private $childTemplateContent;


    /**
     * GeneratePatch -> parentTemplateContent
     * @var string
     */
    private $parentTemplateContent;

    /**
     * GeneratePatch -> textLines
     * @var array
     */
    private $textLines = [];


    /**
     * GeneratePatch constructor.
     * @param File $file
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * Function generatePatches
     * @throws LocalizedException
     */
    public function generatePatches()
    {
        if (!($this->childTemplate && $this->parentTemplate)) {
            throw new LocalizedException(new \Magento\Framework\Phrase('parent and child template files should be set.'));
        }
        $diffs = $this->compareFiles();
        $this->compareDiffs($diffs);


    }

    /**
     * Function compareDiffs
     * @param $diffs
     * @throws \Exception
     */
    private function compareDiffs($diffs)
    {
        $find = '';
        $replace = '';
        $patchCount = 0;
        $this->textLines = [];
        // extract directory name
        $baseName = str_replace(ApplyPatch::FILE_EXTENSION, '', $this->childTemplate);
        $findPath = $baseName . $this->file->dirsep() . self::FIND_DIR;
        $replacePath = $baseName . $this->file->dirsep() . self::REPLACE_DIR;
        // check and create folders
        $this->file->checkAndCreateFolder($findPath);
        $this->file->checkAndCreateFolder($replacePath);


        foreach ($diffs as $diff) {

            $line = $diff[0];
            $code = $diff[1];


            if ($code == GeneratePatch::DELETED) {
                $find .= $line . PHP_EOL;
                continue;
            }
            if ($code == GeneratePatch::INSERTED) {
                $replace .= $line . PHP_EOL;
                $this->textLines[] = $line;
                continue;
            }

            if ($find || $replace) {
                list($find, $replace) = $this->findUniqueMatch($find, $replace);
            }

            if ($code == GeneratePatch::UNMODIFIED && $find) {
                $this->patchFind($find, $replace, $patchCount);
                $find = '';
                $replace = '';
                $patchCount += 1;
            } elseif ($code == GeneratePatch::UNMODIFIED && $replace) {
                $this->patchReplace($replace, $patchCount);
                $find = '';
                $replace = '';
                $patchCount += 1;

            }
            if ($code == GeneratePatch::UNMODIFIED) {
                $this->textLines[] = $line;
            }
        }
        list($find, $replace) = $this->findUniqueMatch($find, $replace);
        // process leftovers
        if ($find) {
            $this->patchFind($find, $replace, $patchCount);
        } elseif ($replace) {
            $this->patchReplace($replace, $patchCount);
        }
    }

    private function patchReplace($replace, $patchCount)
    {
        $baseName = str_replace(ApplyPatch::FILE_EXTENSION, '', $this->childTemplate);
        $findPath = $baseName . $this->file->dirsep() . self::FIND_DIR;
        $replacePath = $baseName . $this->file->dirsep() . self::REPLACE_DIR;
        $patchFileFind = sprintf($this->fileNameTemplate, $findPath, $this->file->dirsep(), $patchCount);
        $patchFileReplace = sprintf($this->fileNameTemplate, $replacePath, $this->file->dirsep(), $patchCount);
        $findText = '';


        $replaces = explode("\n", $replace);
        $replacesCount = count($replaces) - 1;
        $validLines = array_slice($this->textLines, 0, count($this->textLines) - $replacesCount);
        $validFiveLines = array_slice($validLines, -5, 5);


        foreach ($validFiveLines as $index => $lastFiveLine) {
            $findText .= $lastFiveLine . PHP_EOL;
        }
        $replace = $findText . $replace;

        $this->file->write($patchFileFind, $findText);
        $this->file->write($patchFileReplace, $replace);
    }


    private function patchFind(
        $find,
        $replace,
        $patchCount
    ) {
        $baseName = str_replace(ApplyPatch::FILE_EXTENSION, '', $this->childTemplate);
        $findPath = $baseName . $this->file->dirsep() . self::FIND_DIR;
        $replacePath = $baseName . $this->file->dirsep() . self::REPLACE_DIR;
        $patchFileFind = sprintf($this->fileNameTemplate, $findPath, $this->file->dirsep(), $patchCount);
        $patchFileReplace = sprintf($this->fileNameTemplate, $replacePath, $this->file->dirsep(), $patchCount);
        $this->file->write($patchFileFind, $find);
        $this->file->write($patchFileReplace, $replace);
    }

    private function findReplaceContent($replace)
    {
        $replaces = explode("\n", $replace);
        $replacesCount = count($replaces) - 1;
        $validLines = array_slice($this->textLines, 0, count($this->textLines) - $replacesCount);
        $validFiveLines = array_slice($validLines, -5, 5);

        $findText = '';
        foreach ($validFiveLines as $index => $lastFiveLine) {
            $findText .= $lastFiveLine . PHP_EOL;
        }

        return $findText;
    }

    private function findUniqueMatch($find, $replace)
    {
        $replaces = explode("\n", $replace);
        $replacesCount = count($replaces) - 1;
        $validLines = array_slice($this->textLines, 0, count($this->textLines) - $replacesCount);
        $compareFileContent = $this->getCompareFileContent();
        return $this->match($find, $replace, $compareFileContent, $validLines);

    }

    private function match($find, $replace, $compareFileContent, $validLines)
    {
        str_replace($find, $replace, $compareFileContent, $count);
        if ($count > 1) {
            $append = array_pop($validLines) . "\n";
            $find = $append . $find;
            $replace = $append . $replace;
            return $this->match($find, $replace, $compareFileContent, $validLines);

        }
        return [$find, $replace];
    }

    /**
     * Function getCompareFileContent
     * @return string
     */
    private function getCompareFileContent()
    {
        return $this->getParentTemplateContent();
    }

    /**
     * @return string
     */
    public function getChildTemplateContent()
    {
        if (empty($this->childTemplateContent)) {
            $this->childTemplateContent = $this->file->read($this->childTemplate);
        }
        return $this->childTemplateContent;
    }

    /**
     * Function getParentTemplateContent
     * @return bool|string
     */
    public function getParentTemplateContent()
    {
        if (empty($this->parentTemplateContent)) {
            $this->parentTemplateContent = $this->file->read($this->parentTemplate);
        }
        return $this->parentTemplateContent;
    }


    /**
     * Function compareFiles
     * @param $file1
     * @param $file2
     * @param bool $compareCharacters
     * @return array
     */
    public function compareFiles(
        $compareCharacters = false
    ) {

        // return the diff of the files
        return $this->compare(
            file_get_contents($this->parentTemplate),
            file_get_contents($this->childTemplate),
            $compareCharacters);

    }

    /**
     * Function compare
     * @param $string1
     * @param $string2
     * @param bool $compareCharacters
     * @return array
     */
    public function compare(
        $string1,
        $string2,
        $compareCharacters = false
    ) {

        // initialise the sequences and comparison start and end positions
        $start = 0;
        if ($compareCharacters) {
            $sequence1 = $string1;
            $sequence2 = $string2;
            $end1 = strlen($string1) - 1;
            $end2 = strlen($string2) - 1;
        } else {
            $sequence1 = preg_split('/\R/', $string1);
            $sequence2 = preg_split('/\R/', $string2);
            $end1 = count($sequence1) - 1;
            $end2 = count($sequence2) - 1;
        }

        // skip any common prefix
        while ($start <= $end1 && $start <= $end2
            && $sequence1[$start] == $sequence2[$start]) {
            $start++;
        }

        // skip any common suffix
        while ($end1 >= $start && $end2 >= $start
            && $sequence1[$end1] == $sequence2[$end2]) {
            $end1--;
            $end2--;
        }

        // compute the table of longest common subsequence lengths
        $table = $this->computeTable($sequence1, $sequence2, $start, $end1, $end2);

        // generate the partial diff
        $partialDiff =
            $this->generatePartialDiff($table, $sequence1, $sequence2, $start);

        // generate the full diff
        $diff = [];
        for ($index = 0; $index < $start; $index++) {
            $diff[] = [$sequence1[$index], self::UNMODIFIED];
        }
        while (count($partialDiff) > 0) {
            $diff[] = array_pop($partialDiff);
        }
        for ($index = $end1 + 1;
             $index < ($compareCharacters ? strlen($sequence1) : count($sequence1));
             $index++) {
            $diff[] = [$sequence1[$index], self::UNMODIFIED];
        }

        // return the diff
        return $diff;

    }

    /**
     * Function computeTable
     * @param $sequence1
     * @param $sequence2
     * @param $start
     * @param $end1
     * @param $end2
     * @return array
     */
    private function computeTable(
        $sequence1,
        $sequence2,
        $start,
        $end1,
        $end2
    ) {

        // determine the lengths to be compared
        $length1 = $end1 - $start + 1;
        $length2 = $end2 - $start + 1;

        // initialise the table
        $table = [array_fill(0, $length2 + 1, 0)];

        // loop over the rows
        for ($index1 = 1; $index1 <= $length1; $index1++) {

            // create the new row
            $table[$index1] = [0];

            // loop over the columns
            for ($index2 = 1; $index2 <= $length2; $index2++) {

                // store the longest common subsequence length
                if ($sequence1[$index1 + $start - 1]
                    == $sequence2[$index2 + $start - 1]) {
                    $table[$index1][$index2] = $table[$index1 - 1][$index2 - 1] + 1;
                } else {
                    $table[$index1][$index2] =
                        max($table[$index1 - 1][$index2], $table[$index1][$index2 - 1]);
                }

            }
        }

        // return the table
        return $table;

    }

    /**
     * Function generatePartialDiff
     * @param $table
     * @param $sequence1
     * @param $sequence2
     * @param $start
     * @return array
     */
    private function generatePartialDiff(
        $table,
        $sequence1,
        $sequence2,
        $start
    ) {

        //  initialise the diff
        $diff = [];

        // initialise the indices
        $index1 = count($table) - 1;
        $index2 = count($table[0]) - 1;

        // loop until there are no items remaining in either sequence
        while ($index1 > 0 || $index2 > 0) {

            // check what has happened to the items at these indices
            if ($index1 > 0 && $index2 > 0
                && $sequence1[$index1 + $start - 1]
                == $sequence2[$index2 + $start - 1]) {

                // update the diff and the indices
                $diff[] = [$sequence1[$index1 + $start - 1], self::UNMODIFIED];
                $index1--;
                $index2--;

            } elseif ($index2 > 0
                && $table[$index1][$index2] == $table[$index1][$index2 - 1]) {

                // update the diff and the indices
                $diff[] = [$sequence2[$index2 + $start - 1], self::INSERTED];
                $index2--;

            } else {

                // update the diff and the indices
                $diff[] = [$sequence1[$index1 + $start - 1], self::DELETED];
                $index1--;

            }

        }

        // return the diff
        return $diff;

    }

    /**
     * Function setParentTemplate
     * @param $parentTemplate
     * @return $this
     */
    public function setParentTemplate($parentTemplate)
    {
        $this->parentTemplate = $parentTemplate;
        return $this;
    }

    /**
     * Function setChildTemplate
     * @param $childTemplate
     * @return $this
     */
    public function setChildTemplate($childTemplate)
    {
        $this->childTemplate = $childTemplate;
        return $this;
    }

    /**
     * @return string
     */
    public function getFileNameTemplate()
    {
        return $this->fileNameTemplate;
    }


}