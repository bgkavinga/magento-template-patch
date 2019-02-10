<?php
/**
 * Copyright © 2019 Netstarter. All rights reserved.
 *
 * @category    m230-loc.ns-staging.com.au
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2019 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */

namespace Netstarter\Deploy\Service;

use Magento\Framework\App\State;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\View\FileSystem;
use Magento\Framework\App\Utility\Files;
use Magento\Framework\Filesystem\Glob;


class TemplatePatch
{
    /**
     * @var Files
     */
    private $filesUtils;

    /**
     * @var FileSystem
     */
    private $viewFileSystem;
    /**
     * @var State
     */
    private $state;
    /**
     * ThemeBuild -> generatePatch
     * @var GeneratePatch
     */
    private $generatePatch;
    /**
     * ThemeBuild -> applyPatch
     * @var ApplyPatch
     */
    private $applyPatch;
    /**
     * TemplatePatch -> componentRegistrar
     * @var ComponentRegistrar
     */
    private $componentRegistrar;

    /**
     * @param Files $filesUtils
     * @param FileSystem $viewFileSystem
     * @param State $state
     * @param GeneratePatch $generatePatch
     * @param ApplyPatch $applyPatch
     * @param ComponentRegistrar $componentRegistrar
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Files $filesUtils,
        FileSystem $viewFileSystem,
        State $state,
        GeneratePatch $generatePatch,
        ApplyPatch $applyPatch,
        ComponentRegistrar $componentRegistrar
    ) {
        $this->filesUtils = $filesUtils;
        $this->viewFileSystem = $viewFileSystem;
        $this->state = $state;
        $this->generatePatch = $generatePatch;
        $this->applyPatch = $applyPatch;
        $this->componentRegistrar = $componentRegistrar;
        $this->state->setAreaCode('frontend');
    }

    /**
     * Minify template files
     *
     * @param string $areaCode
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function build($type, $theme, $parentTheme = 'Magento/blank', $areaCode = 'frontend')
    {
        $this->state->setAreaCode($areaCode);

        $find = 'Magento_Catalog::category/image.phtml';

        $template1 = $this->viewFileSystem->getTemplateFileName($find,
            ['module' => 'Magento_Catalog', 'area' => 'frontend', 'theme' => 'Magento/blank']);
        $template2 = $this->viewFileSystem->getTemplateFileName($find,
            ['module' => 'Magento_Catalog', 'area' => 'frontend', 'theme' => 'Netstarter/hector']);

        $findDirPath =
            '/home/www/m219-loc.ns-staging.com.au/app/design/frontend/Netstarter/hector/Magento_Catalog/templates/category/image/__find';

        $this->generatePatches($template1, $template2);
        $this->applyPatches($template1, $findDirPath);


        echo '';
    }

    public function generate($theme, $parentTheme)
    {
        $path = $this->componentRegistrar->getPath(ComponentRegistrar::THEME, "frontend/{$theme}");
        $themeFiles = $this->filesUtils->getFiles([$path], '*.phtml');

        foreach ($themeFiles as $themeFile) {
            preg_match("/(.*\/)(.*)(\/templates\/)(.*)/", $themeFile, $matches);
            if (isset($matches[2]) && isset($matches[4])) {
                $module = $matches[2];
                $find = $matches[4];
                $template1 = $this->viewFileSystem->getTemplateFileName($find,
                    ['module' => $module, 'area' => 'frontend', 'theme' => $parentTheme]);
                $template2 = $this->viewFileSystem->getTemplateFileName($find,
                    ['module' => $module, 'area' => 'frontend', 'theme' => $theme]);
                if ($template1 && $template2) {
                    $this->generatePatches($template1, $template2);
                }

            }
        }
    }

    public function apply($theme, $parentTheme)
    {
        $path = $this->componentRegistrar->getPath(ComponentRegistrar::THEME, "frontend/{$theme}");
        $fileNameTemplate = sprintf($this->generatePatch->getFileNameTemplate(), '', '', '*');
        $files = $this->filesUtils->getFiles(["{$path}/*_*/templates"], $fileNameTemplate);
        $pattern = sprintf('/(.*)\/(%s)\/(%s)/', GeneratePatch::FIND_DIR,
            sprintf($this->generatePatch->getFileNameTemplate(), '', '', '[0-9]*'));
        $findPaths = [];
        foreach ($files as $file) {
            preg_match($pattern, $file, $matches);
            if (isset($matches[1])) {
                $pathToScan = sprintf('%s/%s', $matches[1], GeneratePatch::FIND_DIR);
                $findPaths[$pathToScan] = $pathToScan;
            }
        }

        //'(\/Netstarter\/hector\/)(.*)(\/templates\/)(.*)(\/__find)';
        $pattern = sprintf("/(%s\/)(.*)(\/templates\/)(.*)(\/%s)/", str_replace('/', '\/', $theme),
            GeneratePatch::FIND_DIR);
        foreach ($findPaths as $findPath) {
            preg_match($pattern, $findPath, $matches);
            if (isset($matches[2]) && isset($matches[4])) {
                $find = sprintf('%s::%s.phtml', $matches[2], $matches[4]);
                $template1 = $this->viewFileSystem->getTemplateFileName($find,
                    ['module' => $matches[2], 'area' => 'frontend', 'theme' => $parentTheme]);
                $this->applyPatches($template1, $findPath);
            }
        }

    }


    private function generatePatches($template1, $template2)
    {
        $this->generatePatch->setParentTemplate($template1)
            ->setChildTemplate($template2)
            ->generatePatches();
    }

    private function applyPatches($template1, $findDirPath)
    {
        $this->applyPatch->setParentFile($template1)
            ->setFindDirPath($findDirPath)
            ->applyPatch();
    }

    private function findFileList($theme)
    {

    }


}