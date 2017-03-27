<?php
/*
 * This file is part of Auth package.
 *
 * (c) Dante International
 */

$container->setParameter(
    'cache.namespace',
    sprintf(
        '%s_',
        substr(sha1($container->getParameter('kernel.cache_dir')), 0, 8)
    )
);