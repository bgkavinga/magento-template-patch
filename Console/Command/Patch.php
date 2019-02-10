<?php
/**
 * Copyright © 2015 Netstarter. All rights reserved.
 *
 *
 * @author      Netstarter Team <contact@netstarter.com>
 * @copyright   Copyright (c) 2015 Netstarter Pty Ltd. (http://www.netstarter.com.au)
 *
 * @link        http://netstarter.com.au
 */

namespace Netstarter\Deploy\Console\Command;

use Netstarter\Deploy\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Patch  extends Command
{
    /**
     * @var Environment
     */
    protected $env;

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('netstarter-deploy:patch:apply')
            ->setDescription('Apply patches.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->env = new Environment();
        $this->applyPatches();

    }

    protected function applyPatches()
    {
        $this->env->log("Applying patches...");
        $this->env->execute('php ' . Environment::MAGENTO_ROOT . 'vendor/netstarter/deploy/patch.php');
    }
}