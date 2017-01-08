<?php
/* TODO: convert to middleware class? */
use Psr\Http\Message\ServerRequestInterface;

return [
    function (ServerRequestInterface $request, callable $next) {
        $response = $next($request); // delegate control to next middleware
        //$response->getBody()->write("this is test middleware");
        return $response;
    },
];
