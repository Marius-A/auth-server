<?php

$container->setParameter(
    'jwt.cert',
    file_get_contents($container->getParameter('kernel.root_dir') . "/Resources/jwt/cert.pem")
);
$container->setParameter(
    'jwt.prv_key',
    file_get_contents($container->getParameter('kernel.root_dir') . "/Resources/jwt/key.pem")
);
$container->setParameter(
    'jwt.alg',
    'RS256'
);
$container->setParameter(
    'jwt.kty',
    'RSA'
);