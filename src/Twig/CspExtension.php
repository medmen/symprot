<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CspExtension extends AbstractExtension
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function getFunctions(): array
    {
        return [
            // Accept optional named argument "usage" for compatibility with other CSP Twig conventions
            new TwigFunction('csp_nonce', [$this, 'getNonce']),
        ];
    }

    public function getNonce(?string $usage = null): string
    {
        // Ignore $usage, nonce is the same regardless of usage
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return '';
        }
        return (string) $request->attributes->get('csp_nonce', '');
    }
}
