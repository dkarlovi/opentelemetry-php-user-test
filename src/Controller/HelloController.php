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
            try {
                throw new \Exception('Ruh roh');
            } catch (\Exception $exception) {
                $span->setSpanStatus($exception->getCode(), $exception->getMessage());
            }
            $tracer->endActiveSpan();
        }
        usleep(30000);
        
        return new Response('Hello World');
    }

    /**
     * @Route("/exception", name="exc")
     */
    public function exception(): Response
    {
        global $tracer;
        if ($tracer) {
            /** @var Span $span */
            $span = $tracer->getActiveSpan();

            $span->setAttribute('foo', 'bar');
            $span->updateName('New name');
            usleep(30000);

            $tracer->startAndActivateSpan('Child span');
            usleep(30000);
            
            throw new \Exception('Ruh roh');
        }
    }
}
