
# Symfony Routes Converter

Converts your routing.yaml files to PHP 8 native attributes for Symfony.
This work was inspired by the [routing-converter](https://github.com/Stoakes/routing-converter) repository but this one was not working with latest Symfony and PHP versions. Furthermore, instead of converting it to annotations it will convert all old routes to use new PHP 8 native attributes.

## Usage

Copy the file `ConvertRouteLoader.php` and `MigrateRoutesCommandAttributes`into the Command folder of your project you want to convert (`src/Command`).
Run the following command to execute the conversation:

    php bin/console app:MigrateRoutesCommandAttributes config/routes.yml

## Example 

**Before**

````yaml
# config/routes.yaml

app_homepage:
    path:   /home/{id}
    controller: App\Controller\HomeController::indexAction
    requirements:
        id:  \d+
    methods:  [GET, POST]
````
**After**

````php
<?php
# src/Controller/HomeController.php

use Symfony\Component\Routing\Annotation\Route;

class HomeController{
    
    #[Route('/home/{id}', name: 'app_homepage', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function indexAction($id) {
    
    }  
}
````

## How does it works

The Symfony command reads all old routes saved in your routes.yaml file and adds a new route attribute just before the method of the coresponding controller. Furthermore, it will add a use statement after the last existing use statement if the Routes import is missing in the controller file.

## Tests

I've used it for my Symfony project and tested it with Symfony 5.4 and 6.0 and PHP 8. If you wan't to use it please review the code and check whether it fits to your needs. Feel free to adopt it.
