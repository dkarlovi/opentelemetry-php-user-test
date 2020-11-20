<?php

namespace App\Controller;

use OpenTelemetry\Sdk\Trace\Span;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HelloController extends AbstractController
{
    /**
     * @Route("/hello", name="hello")
     */
    public function index(): Response
    {
        global $tracer;
        if ($tracer) {
            /** @var Span $span */
            $span = $tracer->getActiveSpan();
            
            $span->setAttribute('foo', 'bar');
            $span->updateName('New name');

            $tracer->startAndActivateSpan('Child span');
            usleep(30000);
            $tracer->endActiveSpan();
        }
        usleep(30000);
        
        return new Response('Hello World');
    }
}
