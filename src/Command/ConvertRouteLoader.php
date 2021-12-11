<?php

namespace App\Command;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

class ConvertRouteLoader extends YamlFileLoader
{
    public function load(mixed $resource, ?string $type = null) : RouteCollection
    {
        $collection = new RouteCollection();

       // $resource = '@AppBundle/Resources/config/import_routing.yml';
        $type = 'yaml';

        return parent::load($resource, $type);
    }

}
