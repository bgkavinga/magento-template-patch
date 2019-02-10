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

namespace Netstarter\Deploy;


class Environment
{
    const MAGENTO_ROOT = __DIR__ . '/../../../../../../';

    public function execute($command)
    {
        $this->log('Command:'.$command);

        exec(
            $command,
            $output,
            $status
        );

        $this->log('Status:'.var_export($status, true));
        $this->log('Output:'.var_export($output, true));

        if ($status != 0) {
            throw new \RuntimeException("Command $command returned code $status", $status);
        }

        return $output;
    }

    public function log($message)
    {
        echo sprintf('[%s] %s', date("Y-m-d H:i:s"), $message) . PHP_EOL;
    }
}