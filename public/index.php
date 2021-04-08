<?php

use App\Kernel;
use Nyholm\Psr7\Factory\Psr17Factory;
use OpenTelemetry\Contrib\Jaeger\Exporter as JaegerExporter;
use OpenTelemetry\Sdk\Trace\Clock;
use OpenTelemetry\Sdk\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\Sdk\Trace\SamplingResult;
use OpenTelemetry\Sdk\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\Sdk\Trace\TracerProvider;
use OpenTelemetry\Trace as API;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

$sampler = new AlwaysOnSampler();
$samplingResult = $sampler->shouldSample(
    null,
    md5((string) microtime(true)),
    substr(md5((string) microtime(true)), 16),
    'io.opentelemetry.example',
    API\SpanKind::KIND_INTERNAL
);

$exporter = new JaegerExporter(
    'My App name Web Server',
    'http://localhost:9412/api/v2/spans',
    null,
    new Psr18Client(),
    new Psr17Factory(),
    new Psr17Factory()
);

if (SamplingResult::RECORD_AND_SAMPLED === $samplingResult->getDecision()) {
    $tracer = (new TracerProvider())
        ->addSpanProcessor(new BatchSpanProcessor($exporter, Clock::get()))
        ->getTracer('io.opentelemetry.contrib.php');

    $request = Request::createFromGlobals();
    $span = $tracer->startAndActivateSpan($request->getUri());
}
    
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);

if (SamplingResult::RECORD_AND_SAMPLED === $samplingResult->getDecision()) {
    $tracer->endActiveSpan();
}
