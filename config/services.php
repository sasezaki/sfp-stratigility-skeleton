<?php
use Backbeard\Dispatcher;

return (new Zend\ServiceManager\ServiceManager)
->setService('Config', include 'parameters.php')
->setFactory('application', function($container){
    return function ($req, $res, $next) use ($container) {
        $routingFactory = $container->get('routing-factory');
        $view =$container->get('view');
        $stringRouter =$container->get('string-router');

        $dispatcher = new Dispatcher($routingFactory($container), $view, $stringRouter);
        $dispatchResult = $dispatcher->dispatch($req, $res);
        if ($dispatchResult->isDispatched() !== false) {
            return $dispatchResult->getResponse();
        }
        return $next($req, $res);
    };
})
->setFactory('routing-factory', function($container){
    return function (Interop\Container\ContainerInterface $container) {
        yield from $container->get('module-foo');
        yield from $container->get('module-bar');
    };
})
->setFactory('module-foo', function($container){
    return (function() {
        yield '/foo' => function () {
            return 'hoge';
        };
    })();
})
->setFactory('module-bar', function($container){
    $module = function() use ($container) {
        yield '/' => [$container->get('HelloController'), 'helloAction'];
    };

    return $module();
})

->setFactory('HelloController', function($container) {
    return new Application\HelloController();           
})

->setFactory('view', function($container){
    return new Backbeard\View\Templating\SfpStreamView(new SfpStreamView\View(getcwd().'/views'));
})
->setFactory('string-router', function($container){
    return new Backbeard\Router\StringRouter(new \FastRoute\RouteParser\Std());
})
->setFactory('ErrorHandler', function($container){
    $displayErrors = ($container->get('Config')['env'] !== 'production');
    return new Application\ErrorHandler('views', $displayErrors);
});
