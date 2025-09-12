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
            new TwigFunction('csp_nonce', [$this, 'getNonce']),
        ];
    }

    public function getNonce(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return '';
        }
        return (string) $request->attributes->get('csp_nonce', '');
    }
}
