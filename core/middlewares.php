<?php
/* TODO: convert to middleware class? */
use Interop\Http\Middleware\DelegateInterface;
use Leap\Core\Middleware\TestMiddleware;
use Psr\Http\Message\ServerRequestInterface;

return [
    function (ServerRequestInterface $request, DelegateInterface $delegate) {
        $response = $delegate->process($request); // delegate control to next middleware
        //$response->getBody()->write("this is test middleware");
        return $response;
    },
    new TestMiddleware()
];
