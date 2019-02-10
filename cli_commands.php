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
if (PHP_SAPI == 'cli') {
    \Magento\Framework\Console\CommandLocator::register(\Netstarter\Deploy\Console\CommandList::class);
}