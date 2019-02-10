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

namespace Netstarter\Deploy\Console;

use Magento\Framework\ObjectManagerInterface;

class CommandList implements \Magento\Framework\Console\CommandListInterface
{
    /**
     * Object Manager
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Gets list of command classes
     *
     * @return string[]
     */
    protected function getCommandsClasses()
    {
        return [
            \Netstarter\Deploy\Console\Command\Build::class,
            \Netstarter\Deploy\Console\Command\Patch::class,
            \Netstarter\Deploy\Console\Command\Template::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getCommands()
    {
        $commands = [];
        foreach ($this->getCommandsClasses() as $class) {
            if (class_exists($class)) {
                $commands[] = $this->objectManager->get($class);
            } else {
                throw new \Exception('Class ' . $class . ' does not exist');
            }
        }
        return $commands;
    }
}