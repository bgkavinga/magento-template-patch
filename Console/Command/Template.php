<?php
/**
 * Copyright © 2019 Netstarter. All rights reserved.
 *
 *
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2019 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 *
 * @link        http://netstarter.com.au
 */

namespace Netstarter\Deploy\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Template extends Command
{
    /** @var string */
    const TYPE = 'type';

    /** @var string */
    const TYPE_GENERATE = 'generate';

    /** @var string */
    const TYPE_APPLY = 'apply';

    /** @var string */
    const PARENT_THEME = 'parent_theme';

    /** @var string */
    const THEME = 'theme';

    /**
     * @var \Netstarter\Deploy\Service\TemplatePatch
     */
    private $templatePatch;

    /**
     * ThemeBuild constructor.
     * @param null $name
     * @param \Netstarter\Deploy\Service\TemplatePatch $templatePatch
     */
    public function __construct(\Netstarter\Deploy\Service\TemplatePatch $templatePatch, $name = null)
    {
        parent::__construct($name);
        $this->templatePatch = $templatePatch;
    }


    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('netstarter-deploy:template:patch')
            ->setDescription('Build theme using file templates.')
            ->setDefinition($this->getInputList());
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument(self::TYPE);
        $theme = $input->getArgument(self::THEME);
        $parentTheme = $input->getArgument(self::PARENT_THEME);
        if (!in_array($type, [self::TYPE_GENERATE, self::TYPE_APPLY])) {
            $output->writeln('type should be one of ' . self::TYPE_APPLY . '|' . self::TYPE_GENERATE);
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
        try {
            if ($type == self::TYPE_GENERATE) {
                $this->templatePatch->generate($theme, $parentTheme);
            }

            if ($type == self::TYPE_APPLY) {
                $this->templatePatch->apply($theme, $parentTheme);
            }

        } catch (LocalizedException $e) {
            $output->writeln($e->getMessage());
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

    }

    public function getInputList()
    {
        return [
            new InputArgument(
                self::TYPE,
                InputArgument::REQUIRED,
                'run type generate|apply'
            ),
            new InputArgument(
                self::THEME,
                InputArgument::REQUIRED,
                'theme name'
            ),
            new InputArgument(
                self::PARENT_THEME,
                InputArgument::OPTIONAL,
                'parent theme name'
            ),
        ];
    }
}