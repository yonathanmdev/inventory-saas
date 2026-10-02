<?php

use DI\Container;

$container = new Container();

$container->set(PDO::class, getPDO());

return $container;