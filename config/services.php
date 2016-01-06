<?php
use Backbeard\Dispatcher;

return (new Zend\ServiceManager\ServiceManager)
->setService('Config', include 'parameters.php')
->setFactory('application', function($sm){
    return function ($req, $res, $next) use ($sm) {
        $routingFactory = $sm->get('routing-factory');
        $view =$sm->get('view');
        $stringRouter =$sm->get('string-router');

        $dispatcher = new Dispatcher($routingFactory->run($req), $view, $stringRouter);
        $dispatchResult = $dispatcher->dispatch($req, $res);
        if ($dispatchResult->isDispatched() !== false) {
            return $dispatchResult->getResponse();
        }
        return $next($req, $res);
    };
})
->setFactory('routing-factory', function($sm){
    return new class($sm) {
        private $container;
        public function __construct(Interop\Container\ContainerInterface $container) 
        {
            $this->container = $container;
        }

        public function run(Psr\Http\Message\ServerRequestInterface $req)
        {
            if (stripos($req->getUri()->getPath(), '/bar') === 0) {
                goto bar;
            }

            foo:
            yield from $this->container->get('module-foo');

            bar:
            yield from $this->container->get('module-bar');
        }

    };
})
->setFactory('module-foo', function($sm){
    return (function() {
        yield '/foo' => function () {
            return 'hoge';
        };
    })();
})
->setFactory('module-bar', function($sm){
    $module = function() use ($sm) {
        yield '/' => [$sm->get('HelloController'), 'helloAction'];
    };

    return $module();
})

->setFactory('HelloController', function($sm) {
    return new Application\HelloController();           
})

->setFactory('view', function($sm){
    return new Backbeard\View\Templating\SfpStreamView(new SfpStreamView\View(getcwd().'/views'));
})
->setFactory('string-router', function($sm){
    return new Backbeard\Router\StringRouter(new \FastRoute\RouteParser\Std());
})
->setFactory('ErrorHandler', function($sm){
    $displayErrors = ($sm->get('Config')['env'] !== 'production');
    return new Application\ErrorHandler('views', $displayErrors);
});
