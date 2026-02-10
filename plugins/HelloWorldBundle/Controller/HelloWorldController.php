<?php

namespace KimaiPlugin\HelloWorldBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class HelloWorldController
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function index(): Response
    {
        $loremIpsum = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
        
        $content = $this->twig->render('@HelloWorld/hello.html.twig', [
            'content' => $loremIpsum,
        ]);
        
        return new Response($content);
    }
}
