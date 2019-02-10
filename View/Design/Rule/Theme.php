<?php
/**
 * Copyright © 2019 Netstarter. All rights reserved.
 *
 * @category    m230-loc.ns-staging.com.au
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2019 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 */

namespace Netstarter\Deploy\View\Design\Rule;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\Design\Fallback\Rule\RuleInterface;
use Magento\Framework\View\Design\Fallback\Rule\SimpleFactory;
use Magento\Framework\View\Design\Fallback\Rule\ThemeFactory;
use Magento\Framework\View\Design\Fallback\Rule\ModuleFactory;
use Magento\Framework\View\Design\Fallback\Rule\ModularSwitchFactory;

class Theme implements RuleInterface
{
    /**
     * Rule
     *
     * @var RuleInterface
     */
    protected $rule;

    /**
     * Component registrar
     *
     * @var ComponentRegistrarInterface
     */
    private $componentRegistrar;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var SimpleFactory
     */
    private $simpleFactory;
    /**
     * @var ThemeFactory
     */
    private $themeFactory;
    /**
     * @var ModuleFactory
     */
    private $moduleFactory;
    /**
     * @var ModularSwitchFactory
     */
    private $modularSwitchFactory;

    /**
     * Constructors
     *
     * @param RuleInterface $rule
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param Filesystem $filesystem
     * @param SimpleFactory $simpleFactory
     * @param ThemeFactory $themeFactory
     * @param ModuleFactory $moduleFactory
     * @param ModularSwitchFactory $modularSwitchFactory
     */
    public function __construct(
        RuleInterface $rule,
        ComponentRegistrarInterface $componentRegistrar,
        Filesystem $filesystem,
        SimpleFactory $simpleFactory,
        ThemeFactory $themeFactory,
        ModuleFactory $moduleFactory,
        ModularSwitchFactory $modularSwitchFactory
    ) {
        $this->rule = $rule;
        $this->componentRegistrar = $componentRegistrar;
        $this->filesystem = $filesystem;
        $this->simpleFactory = $simpleFactory;
        $this->themeFactory = $themeFactory;
        $this->moduleFactory = $moduleFactory;
        $this->modularSwitchFactory = $modularSwitchFactory;
    }

    /**
     * Get ordered list of folders to search for a file
     *
     * @param array $params Values to substitute placeholders with
     * @return array folders to perform a search
     */
    public function getPatternDirs(array $params)
    {
        if (!array_key_exists('theme', $params) || !$params['theme'] instanceof ThemeInterface) {
            throw new \InvalidArgumentException(
                'Parameter "theme" should be specified and should implement the theme interface.'
            );
        }
        $result = [];
        /** @var $theme ThemeInterface */
        $theme = $params['theme'];
        unset($params['theme']);
        while ($theme) {
            if ($theme->getFullPath()) {
                $params['theme_dir'] = $this->componentRegistrar->getPath(
                    ComponentRegistrar::THEME,
                    $theme->getFullPath()
                );

//                $params = $this->getThemePubStaticDir($theme, $params);
//                $result = array_merge($result, $this->rule->getPatternDirs($params));
            }
            $theme = $theme->getParentTheme();
        }
        return $result;
    }

    protected function createViewFileRule()
    {
        $libDir = rtrim($this->filesystem->getDirectoryRead(DirectoryList::LIB_WEB)->getAbsolutePath(), '/');
        return $this->modularSwitchFactory->create(
            [
                'ruleNonModular' => new Composite(
                    [
                        $this->themeFactory->create(
                            [
                                'rule' =>
                                    new Composite(
                                        [
                                            $this->simpleFactory
                                                ->create([
                                                    'pattern' => "<theme_dir>/web/i18n/<locale>",
                                                    'optionalParams' => ['locale']
                                                ]),
                                            $this->simpleFactory
                                                ->create(['pattern' => "<theme_dir>/web"]),
                                            $this->simpleFactory
                                                ->create([
                                                    'pattern' => "<theme_pubstatic_dir>",
                                                    'optionalParams' => ['theme_pubstatic_dir']
                                                ]),
                                        ]
                                    )
                            ]
                        ),
                        $this->simpleFactory->create(['pattern' => $libDir]),
                    ]
                ),
                'ruleModular' => new Composite(
                    [
                        $this->themeFactory->create(
                            [
                                'rule' =>
                                    new Composite(
                                        [
                                            $this->simpleFactory->create(
                                                [
                                                    'pattern' => "<theme_dir>/<module_name>/web/i18n/<locale>",
                                                    'optionalParams' => ['locale'],
                                                ]
                                            ),
                                            $this->simpleFactory->create(
                                                ['pattern' => "<theme_dir>/<module_name>/web"]
                                            ),
                                        ]
                                    )
                            ]
                        ),
                        $this->moduleFactory->create(
                            [
                                'rule' => $this->simpleFactory->create(
                                    [
                                        'pattern' => "<module_dir>/view/<area>/web/i18n/<locale>",
                                        'optionalParams' => ['locale']
                                    ]
                                )
                            ]
                        ),
                        $this->moduleFactory->create(
                            [
                                'rule' => $this->simpleFactory->create(
                                    [
                                        'pattern' => "<module_dir>/view/base/web/i18n/<locale>",
                                        'optionalParams' => ['locale']
                                    ]
                                )
                            ]
                        ),
                        $this->moduleFactory->create(
                            ['rule' => $this->simpleFactory->create(['pattern' => "<module_dir>/view/<area>/web"])]
                        ),
                        $this->moduleFactory->create(
                            ['rule' => $this->simpleFactory->create(['pattern' => "<module_dir>/view/base/web"])]
                        ),
                    ]
                )
            ]
        );
    }
}