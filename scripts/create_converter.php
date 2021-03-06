<?php

require __DIR__ . '/../config/config.php';

global $bluePrint;
$bluePrint = <<<EOL
<?php
/* auto-generated converter class via `uniconv/scripts/create_converter.php` */

declare(strict_types=1);

%NAMESPACE%
class %CLASSNAME% extends %BASE_CLASSNAME%
{
}


EOL;


function createConverterClasses(array $from, array $to, string $baseClass, string $targetFolder, ?string $namespace = null)
{
    global $bluePrint;
    if (!realpath($targetFolder) && !(file_exists(realpath($targetFolder)))) {
        throw new Exception("Target folder '$targetFolder' does not exists. Please provide an existing path");
    }
    foreach($from as $sourceExtension) {
        foreach ($to as $targetExtension) {
            $fromClassName = $sourceExtension;
            if (is_numeric($fromClassName[0])) {
                $fromClassName = 'Converter'.ucfirst($sourceExtension);
            }
            $className = ucfirst($fromClassName) . 'To' . ucfirst($targetExtension);
            $content = strtr($bluePrint, [
                '%NAMESPACE%' => (!empty($namespace)) ? "namespace $namespace;\n" : '',
                '%CLASSNAME%' => $className,
                '%BASE_CLASSNAME%' => $baseClass
            ]);
            $outputFile = realpath($targetFolder)."/$className.php";
            echo "-> $outputFile\n";
            if (!file_put_contents($outputFile, $content)) {
                throw new \Exception("Could not write file $outputFile");
            }
        }
    }
}

$router = new Clue\Commander\Router();
$router->add('<from> <to> <baseClass> <targetFolder> [<namespace>]', function (array $args) {
    createConverterClasses(
        explode(',',$args['from']),
        explode(',',$args['to']),
        $args['baseClass'],
        $args['targetFolder'],
        $args['namespace']
    );
});

$router->add('[--help | -h]', function () use ($router) {
    echo 'Usage:' . PHP_EOL;
    foreach ($router->getRoutes() as $route) {
        echo '  ' .$route . PHP_EOL;
    }
});
// create webm,mkv

try {
    $router->handleArgv();
} catch (Clue\Commander\NoRouteFoundException $e) {
    echo 'Usage error: ' . $e->getMessage() . PHP_EOL;
    echo 'Run without arguments if you need help with usage' . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
