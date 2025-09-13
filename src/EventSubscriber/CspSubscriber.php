<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;

#[AsEventListener(event: KernelEvents::RESPONSE, method: 'onResponse', priority: -128)]
class CspSubscriber
{
    private UriSafeTokenGenerator $tokenGenerator;

    public function __construct(private readonly bool $cspEnabled = true)
    {
        $this->tokenGenerator = new UriSafeTokenGenerator();
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$this->cspEnabled) {
            return;
        }
        $request = $event->getRequest();
        $response = $event->getResponse();
        if (!$response instanceof Response) {
            return;
        }

        // Generate per-request nonce and expose it to Twig via request attributes
        $nonce = $request->attributes->get('csp_nonce');
        if (!$nonce) {
            $nonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
            $request->attributes->set('csp_nonce', $nonce);
        }

        // Build a reasonably strict CSP that disallows inline JS/CSS unless nonced
        $self = "'self'";
        $nonceToken = sprintf("'nonce-%s'", $nonce);

        $directives = [
            // base policy
            'default-src' => [$self],
            // scripts: allow self and nonce; allow 'strict-dynamic' so nonced root can load others (importmap)
            'script-src' => [$self, $nonceToken, "'strict-dynamic'"],
            // styles: allow self and nonce to support nonced critical styles
            'style-src' => [$self, $nonceToken],
            // images, fonts and data for svgs/icons
            'img-src' => [$self, 'data:'],
            'font-src' => [$self, 'data:'],
            // connections for fetch/XHR if needed (self)
            'connect-src' => [$self],
            // disallow embedding by default
            'frame-ancestors' => [$self],
            // object and baseURI restrictions
            'object-src' => ["'none'"],
            'base-uri' => [$self],
            // upgrade insecure
            'upgrade-insecure-requests' => [],
        ];

        $header = $this->buildCspHeader($directives);
        $response->headers->set('Content-Security-Policy', $header, true);
        // Also add a backward-compatible header for older UAs if desired
        $response->headers->set('X-Content-Security-Policy', $header, true);
    }

    private function buildCspHeader(array $directives): string
    {
        $parts = [];
        foreach ($directives as $name => $values) {
            if (empty($values)) {
                $parts[] = $name; // directives without values
                continue;
            }
            $parts[] = $name.' '.implode(' ', array_unique($values));
        }
        return implode('; ', $parts);
    }
}
