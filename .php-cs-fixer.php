<?php

$finder = (new PhpCsFixer\Finder())
	->in(__DIR__.'/src')
;
$config = new PhpCsFixer\Config();
return $config->setRules([
		'@Symfony' => true,
		'yoda_style' => false,
        'class_attributes_separation' => [
            'elements' => ['method' => 'one', 'property' => 'one', 'trait_import' => 'one']
        ]
])
	->setFinder($finder)
;
