<?php
namespace Middlewares;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\Middleware\MiddlewareInterface;
use Interop\Http\Middleware\DelegateInterface;
class TestMiddleware implements MiddlewareInterface
{
    /**
     * Process a request and return a response.
     *
     * @param RequestInterface  $request
     * @param DelegateInterface $delegate
     *
     * @return ResponseInterface
     */
    public function process(RequestInterface $request, DelegateInterface $delegate)
    {

        $response = $delegate->process($request); // delegate control to next middleware
        $response->getBody()->write("this is test middleware!");
        return $response;
    }
}