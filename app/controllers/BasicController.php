<?php

namespace Leap\App\Controllers;

use Leap\Core\Controller;
use Leap\Core\Template;
use Psr\Http\Message\ServerRequestInterface;

class BasicController extends Controller
{
    public function renderPage(ServerRequestInterface $request = null, $parameters)
    {
        $template = new Template($this->route, $this->hooks, $this->config);
        if (isset($parameters['title'])) {
            $template->set('title', $parameters['title']);
        } else {
            $tmp_page = explode("/", explode(".", $parameters['page'])[0]);
            $template->set('title', ucfirst(end($tmp_page)));
        }
        $page = $parameters['page'] ?? null;
        return $template->render($page);
    }

    public function hasAccess(): bool
    {
        return true;
    }

}
