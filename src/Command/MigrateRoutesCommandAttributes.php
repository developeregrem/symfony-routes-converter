<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Config\FileLocator;
use App\Command\ConvertRouteLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;

#[AsCommand(
            name: 'app:MigrateRoutesCommandAttributes',
            description: 'Converts your old routes.yaml routes to php attributes',
    )]
class MigrateRoutesCommandAttributes extends Command {

    protected function configure(): void {
        $this
                ->addArgument('resource', InputArgument::REQUIRED, 'The routes.yaml file to convert')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {       
        $io = new SymfonyStyle($input, $output);
        $io->info("Starting migration ... ");
        
        $fileLoader = new FileLocator(__DIR__ . '/../../');
        $loader = new ConvertRouteLoader($fileLoader);
        $routes = $loader->load($input->getArgument('resource'));

        $count = 0;
        foreach ($routes->all() as $name => $route) {
            $string = "#[Route('" . $route->getPath() . "', name: '" . $name . "'";

            $string = $this->methodsHandler($route, $string);
            $string = $this->requirementsHandler($route, $string);
            $string = $this->defaultsHandler($route, $string);

            $string .= ")]";

            $controller = $this->getController($route);
            $classname = get_class($controller[0]);
            $classShort = substr(strrchr($classname, "\\"), 1);
            $method = $controller[1];

            $content = file_get_contents('src/Controller/' . $classShort . '.php');
            
            $nl = $this->detectLineEnding($content);
            
            //simply add a new attribute above the method
            if (preg_match("/.*function " . $method . "[ ]*\(/", $content)) {       
                /*
                 * replace based on the following search:
                 *  - ([ \t]*) save tab or whitespaces so that new line will be alligned as the other lines
                 *  - ([a-z]+ function ".$method."[ ]*\() search for the function where the new route will be added above
                 */
                $content = preg_replace("/([ \t]*)([a-z]+ function " . $method . "[ ]*\()/",
                        "$1" . $string . $nl . "$1$2",
                        $content);
                $count++;
            } else {
                $io->warning("Method: " . $method . " in " . $classname . " not found.");
            }

            $content = $this->addUseStatement($content, $nl);

            file_put_contents('src/Controller/' . $classShort . '.php', $content);            
        }
        
        $io->success("Migrated " . $count . " routes to attributes.");
        
        return Command::SUCCESS;
    }

    private function methodsHandler(Route $route, $string) {
        if ($route->getMethods()) {
            $string .= ", methods: [";
            $i = 0;
            foreach ($route->getMethods() as $method) {
                if ($i != 0) {
                    $string .= ",";
                }
                $string .= "'" . $method . "'";
                $i++;
            }
            $string .= "]";
        }
        return $string;
    }

    private function requirementsHandler(Route $route, $string) {
        if ($route->getRequirements()) {
            $string .= ', requirements: [';
            $i = 0;
            foreach ($route->getRequirements() as $key => $value) {
                if ($i != 0) {
                    $string .= ", ";
                }
                $string .= "'" . $key . "' => '" . $value . "'";
                $i++;
            }
            $string .= ']';
        }
        return $string;
    }

    private function defaultsHandler(Route $route, $string) {
        if (count($route->getDefaults()) > 1) { // there always are a _controller field.
            $string .= ", defaults: [";
            $i = 0;
            foreach ($route->getDefaults() as $key => $value) {
                if ($key != '_controller') {
                    if ($i != 0) {
                        $string .= ", ";
                    }
                    $string .= "'" . $key . "' => '" . $value . "'";

                    $i++;
                }
            }
            $string .= "]";
        }
        return $string;
    }

    private function getController(Route $route) {
        $controllerName = $route->getDefault('_controller');
        $req = new Request([], [], array('_controller' => $controllerName));
        $controllerResolver = new ControllerResolver();
        $controller = $controllerResolver->getController($req);

        return $controller;
    }

    private function detectLineEnding(string $content): string {
        // Windows CRLF
        if (preg_match("/\r\n/", $content)) {
            $nl = "\r\n";
        } else if (preg_match("/\n/", $content)) { // Linux LF
            $nl = "\n";
        } else {    // Mac OS 9 CR
            $nl = "\r";
        }
        return $nl;
    }
    
    private function addUseStatement(string $content, $nl) : string {
        $routePattern = "use Symfony\\\Component\\\Routing\\\Annotation\\\Route;";
        // add use statement if it is missing
        if (!preg_match("/" . $routePattern . "/", $content)) {
            $content = preg_replace("/(.*use [a-zA-Z\\\]+;)/su",
                    "$1" . $nl . str_replace("\\\\", "\\", $routePattern),
                    $content);
        }
        return $content;
    }
}
